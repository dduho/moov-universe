<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\PointOfSale;
use App\Models\User;
use App\Mail\TaskAssignedMail;
use App\Mail\TaskCompletedMail;
use App\Mail\TaskValidatedMail;
use App\Mail\TaskRevisionRequestedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


class TaskController extends Controller
{
    /**
     * Liste des tâches avec filtres selon le rôle
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = Task::with(['pointOfSale.organization', 'assignedUser', 'creator', 'validator']);

        // Filtres selon le rôle
        if (method_exists($user, 'isCommercial') && $user->isCommercial()) {
            // Commercial : uniquement ses tâches
            $query->where('assigned_to', $user->id);
        } elseif (method_exists($user, 'isDealerOwner') && $user->isDealerOwner()) {
            // Propriétaire dealer : tâches de son organisation
            $query->whereHas('pointOfSale', function($q) use ($user) {
                $q->where('organization_id', $user->organization_id);
            });
        }
        // Admin : tout voir (pas de filtre)

        // Filtres supplémentaires
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('point_of_sale_id')) {
            $query->where('point_of_sale_id', $request->point_of_sale_id);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($tasks);
    }

    /**
     * Créer une tâche (Admin uniquement)
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Seuls les administrateurs peuvent créer des tâches'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'point_of_sale_id' => 'required|exists:point_of_sales,id',
            'assigned_to' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Données invalides',
                'errors' => $validator->errors()
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Vérifier que le commercial appartient au même dealer que le PDV
            $pdv = PointOfSale::findOrFail($request->point_of_sale_id);
            $commercial = User::findOrFail($request->assigned_to);

            if ($commercial->organization_id !== $pdv->organization_id) {
                return response()->json([
                    'error' => 'Le commercial doit appartenir au même dealer que le PDV'
                ], 400);
            }

            if (!$commercial->isDealerAgent() && !$commercial->isCommercial()) {
                return response()->json([
                    'error' => 'L\'utilisateur sélectionné n\'est pas un commercial'
                ], 400);
            }

            // Créer la tâche
            $task = Task::create([
                'point_of_sale_id' => $request->point_of_sale_id,
                'assigned_to' => $request->assigned_to,
                'created_by' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'pending'
            ]);

            // Ajouter le tag "en_revision" au PDV
            $pdv->addTag('en_revision');

            // Notifier le commercial assigné via le système custom
            DB::table('notifications')->insert([
                'user_id' => $commercial->id,
                'type' => 'task_assigned',
                'title' => 'Nouvelle tâche assignée',
                'message' => 'Nouvelle tâche assignée : "' . $task->title . '" pour le PDV ' . ($pdv->nom_point ?? ''),
                'data' => json_encode([
                    'task_id' => $task->id,
                    'point_of_sale_id' => $task->point_of_sale_id,
                    'point_of_sale_name' => $pdv->nom_point ?? null,
                    'url' => '/pdv/' . $task->point_of_sale_id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Notifier le créateur que sa tâche a été créée
            if ($user->id !== $commercial->id) {
                DB::table('notifications')->insert([
                    'user_id' => $user->id,
                    'type' => 'task_assigned',
                    'title' => 'Tâche créée',
                    'message' => 'Vous avez créé la tâche "' . $task->title . '" assignée à ' . $commercial->name,
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'point_of_sale_id' => $task->point_of_sale_id,
                        'point_of_sale_name' => $pdv->nom_point ?? null,
                        'url' => '/pdv/' . $task->point_of_sale_id,
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Envoyer l'email au commercial assigné
            if ($commercial->email) {
                Mail::to($commercial->email)->send(new TaskAssignedMail($task));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tâche créée avec succès',
                'task' => $task->load(['pointOfSale', 'assignedUser', 'creator'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de la création de la tâche: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une tâche
     */
    public function show($id)
    {
        $user = auth()->user();
        $task = Task::with(['pointOfSale.organization', 'assignedUser', 'creator', 'validator'])->findOrFail($id);

        // Vérifier les permissions
        if ($user->isCommercial() && $task->assigned_to !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        if ($user->isDealerOwner() && $task->pointOfSale->organization_id !== $user->organization_id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        return response()->json($task);
    }

    /**
     * Marquer une tâche comme complétée (Commercial)
     */
    public function complete(Request $request, $id)
    {
        $user = auth()->user();
        $task = Task::findOrFail($id);

        // Seul le commercial assigné peut compléter
        if ($task->assigned_to !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        if ($task->status === 'validated') {
            return response()->json(['error' => 'Cette tâche est déjà validée'], 400);
        }

        DB::beginTransaction();

        try {
            $task->complete();

            // Notifier les administrateurs via le système custom
            $admins = User::whereHas('role', function($query) {
                $query->where('name', 'admin');
            })->get();
            
            foreach ($admins as $admin) {
                DB::table('notifications')->insert([
                    'user_id' => $admin->id,
                    'type' => 'task_completed',
                    'title' => 'Tâche complétée',
                    'message' => 'La tâche "' . $task->title . '" a été complétée par ' . $user->name,
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'point_of_sale_id' => $task->point_of_sale_id,
                        'url' => '/pdv/' . $task->point_of_sale_id,
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Envoyer l'email
                if ($admin->email) {
                    Mail::to($admin->email)->send(new TaskCompletedMail($task, $admin));
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tâche complétée et soumise pour validation',
                'task' => $task->load(['pointOfSale', 'assignedUser'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider une tâche (Admin)
     */
    public function validateTask(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Seuls les administrateurs peuvent valider des tâches'], 403);
        }

        $task = Task::findOrFail($id);

        if ($task->status !== 'completed') {
            return response()->json(['error' => 'Cette tâche n\'est pas prête à être validée'], 400);
        }

        DB::beginTransaction();

        try {
            $task->validate($user->id);

            // Notifier le commercial via le système custom
            if ($task->assignedUser) {
                DB::table('notifications')->insert([
                    'user_id' => $task->assignedUser->id,
                    'type' => 'task_validated',
                    'title' => 'Tâche validée',
                    'message' => 'Votre tâche "' . $task->title . '" a été validée',
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'point_of_sale_id' => $task->point_of_sale_id,
                        'url' => '/pdv/' . $task->point_of_sale_id,
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Envoyer l'email
                if ($task->assignedUser->email) {
                    Mail::to($task->assignedUser->email)->send(new TaskValidatedMail($task));
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tâche validée avec succès',
                'task' => $task->load(['pointOfSale', 'assignedUser', 'validator'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Demander une révision (Admin)
     */
    public function requestRevision(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Seuls les administrateurs peuvent demander une révision'], 403);
        }

        $validator = Validator::make($request->all(), [
            'feedback' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Le feedback est requis',
                'errors' => $validator->errors()
            ], 400);
        }

        $task = Task::findOrFail($id);

        if ($task->status !== 'completed') {
            return response()->json(['error' => 'Cette tâche n\'est pas prête à être révisée'], 400);
        }

        DB::beginTransaction();

        try {
            $task->requestRevision($request->feedback);

            // Notifier le commercial via le système custom
            if ($task->assignedUser) {
                DB::table('notifications')->insert([
                    'user_id' => $task->assignedUser->id,
                    'type' => 'task_revision_requested',
                    'title' => 'Révision demandée',
                    'message' => 'Une révision est demandée pour votre tâche "' . $task->title . '"',
                    'data' => json_encode([
                        'task_id' => $task->id,
                        'point_of_sale_id' => $task->point_of_sale_id,
                        'feedback' => $task->feedback,
                        'url' => '/pdv/' . $task->point_of_sale_id,
                    ]),
                    'read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Envoyer l'email
                if ($task->assignedUser->email) {
                    Mail::to($task->assignedUser->email)->send(new TaskRevisionRequestedMail($task));
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Révision demandée avec succès',
                'task' => $task->load(['pointOfSale', 'assignedUser'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une tâche (Admin uniquement)
     */
    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $task = Task::findOrFail($id);

        DB::beginTransaction();

        try {
            $pdv = $task->pointOfSale;
            $task->delete();

            // Vérifier s'il reste des tâches sur ce PDV
            $remainingTasks = $pdv->tasks()->count();
            if ($remainingTasks === 0) {
                $pdv->removeAllTags();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tâche supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les commerciaux d'un dealer (pour la sélection)
     */
    public function getCommercialsByDealer($pointOfSaleId)
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $pdv = PointOfSale::findOrFail($pointOfSaleId);
        
        $commercials = User::where('organization_id', $pdv->organization_id)
            ->whereHas('role', function($query) {
                $query->where('name', 'dealer_agent');
            })
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($commercials);
    }
}
