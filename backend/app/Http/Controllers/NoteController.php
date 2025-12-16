<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\PointOfSale;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * Récupérer les notes d'un PDV
     */
    public function index(Request $request, $pointOfSaleId)
    {
        $pointOfSale = PointOfSale::findOrFail($pointOfSaleId);
        
        $notes = $pointOfSale->notes()
            ->with('user:id,name')
            ->paginate($request->get('per_page', 10));
        
        return response()->json($notes);
    }

    /**
     * Créer une nouvelle note
     */
    public function store(Request $request, $pointOfSaleId)
    {
        $pointOfSale = PointOfSale::findOrFail($pointOfSaleId);
        
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'is_pinned' => 'boolean',
        ]);

        $note = $pointOfSale->notes()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        $note->load('user:id,name');

        // Notifier le créateur du PDV (s'il est différent de l'auteur de la note)
        $currentUser = $request->user();
        if ($pointOfSale->created_by && $pointOfSale->created_by !== $currentUser->id) {
            \DB::table('notifications')->insert([
                'user_id' => $pointOfSale->created_by,
                'type' => 'info',
                'title' => 'Nouvelle note sur votre PDV',
                'message' => $currentUser->name . ' a ajouté une note sur "' . $pointOfSale->nom_point . '"',
                'data' => json_encode([
                    'pdv_id' => $pointOfSale->id,
                    'pdv_name' => $pointOfSale->nom_point,
                    'note_id' => $note->id,
                    'url' => '/pdv/' . $pointOfSale->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json($note, 201);
    }

    /**
     * Mettre à jour une note
     */
    public function update(Request $request, $pointOfSaleId, $noteId)
    {
        $note = Note::where('point_of_sale_id', $pointOfSaleId)
            ->findOrFail($noteId);
        
        // Seul l'auteur ou un admin peut modifier
        $user = $request->user();
        if ($note->user_id !== $user->id && $user->role->name !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'content' => 'sometimes|string|max:2000',
            'is_pinned' => 'boolean',
        ]);

        $note->update($validated);
        $note->load('user:id,name');

        return response()->json($note);
    }

    /**
     * Supprimer une note
     */
    public function destroy(Request $request, $pointOfSaleId, $noteId)
    {
        $note = Note::where('point_of_sale_id', $pointOfSaleId)
            ->findOrFail($noteId);
        
        // Seul l'auteur ou un admin peut supprimer
        $user = $request->user();
        if ($note->user_id !== $user->id && $user->role->name !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Note supprimée avec succès']);
    }

    /**
     * Épingler/Désépingler une note
     */
    public function togglePin(Request $request, $pointOfSaleId, $noteId)
    {
        $note = Note::where('point_of_sale_id', $pointOfSaleId)
            ->findOrFail($noteId);
        
        // Seul l'auteur ou un admin peut épingler
        $user = $request->user();
        if ($note->user_id !== $user->id && $user->role->name !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $note->is_pinned = !$note->is_pinned;
        $note->save();
        $note->load('user:id,name');

        return response()->json($note);
    }
}
