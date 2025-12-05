<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Services\ProximityAlertService;
use Illuminate\Http\Request;

class PointOfSaleController extends Controller
{
    protected $proximityService;

    public function __construct(ProximityAlertService $proximityService)
    {
        $this->proximityService = $proximityService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Ne charger que les colonnes nécessaires et relations essentielles
        $query = PointOfSale::select([
            'id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode',
            'profil', 'region', 'prefecture', 'commune', 'ville', 'quartier',
            'status', 'created_by', 'validated_by', 'created_at', 'updated_at',
            'latitude', 'longitude', 'dealer_name', 'canton',
            // Informations gérant
            'firstname', 'lastname', 'gender', 'sexe_gerant', 'date_of_birth',
            // Documents
            'id_description', 'id_number', 'id_expiry_date', 'nationality', 'profession',
            // Fiscalité
            'nif', 'regime_fiscal',
            // Contacts
            'numero_proprietaire', 'autre_contact',
            // Visibilité et autres
            'support_visibilite', 'etat_support', 'numero_cagnt', 'type_activite', 'localisation'
        ])->with(['organization:id,name', 'creator:id,name']);
        
        // Ne pas charger validator et uploads dans la liste (trop lourd)

        // Filter based on user role
        if ($user->isAdmin()) {
            // Admins see all PDV
        } elseif ($user->isDealerOwner()) {
            // Dealer owners see all PDV in their organization
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            // Commercials see only their own PDV + PDV with tasks assigned to them
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            // Dealer agents see only their own PDV
            $query->where('created_by', $user->id);
        } else {
            // No access for other roles
            $query->whereRaw('1 = 0');
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('region')) {
            $query->where('region', $request->region);
        }

        if ($request->has('prefecture')) {
            $query->where('prefecture', $request->prefecture);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_point', 'like', "%{$search}%")
                  ->orWhere('dealer_name', 'like', "%{$search}%")
                  ->orWhere('numero_flooz', 'like', "%{$search}%");
            });
        }

        // Pagination with performance optimization
        $perPage = $request->get('per_page', 50); // Default to 50 for better performance
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Limiter le per_page max à 100 pour éviter les surcharges
        if ($perPage > 100) {
            $perPage = 100;
        }
        
        return response()->json($query->orderBy($sortBy, $sortOrder)->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'dealer_name' => 'required|string',
            'numero_flooz' => 'required|string|unique:point_of_sales,numero_flooz',
            'shortcode' => 'required|string|unique:point_of_sales,shortcode',
            'nom_point' => 'required|string',
            'profil' => 'required|string',
            'type_activite' => 'nullable|string',
            'firstname' => 'nullable|string',
            'lastname' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'id_description' => 'nullable|string',
            'id_number' => 'nullable|string',
            'id_expiry_date' => 'nullable|date',
            'nationality' => 'nullable|string',
            'profession' => 'nullable|string',
            'sexe_gerant' => 'nullable|in:M,F',
            'region' => 'required|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'prefecture' => 'required|string',
            'commune' => 'required|string',
            'canton' => 'nullable|string',
            'ville' => 'required|string',
            'quartier' => 'required|string',
            'localisation' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
            'numero_proprietaire' => 'required|string',
            'autre_contact' => 'nullable|string',
            'nif' => 'nullable|string',
            'regime_fiscal' => 'nullable|string',
            'support_visibilite' => 'required|string',
            'etat_support' => 'nullable|in:BON,MAUVAIS',
            'numero_cagnt' => 'required|string',
        ], [
            'numero_flooz.unique' => 'Ce numéro Flooz est déjà utilisé par un autre point de vente.',
            'shortcode.unique' => 'Ce shortcode est déjà utilisé par un autre point de vente.',
            'organization_id.required' => 'L\'organisation est obligatoire.',
            'organization_id.exists' => 'L\'organisation sélectionnée n\'existe pas.',
            'dealer_name.required' => 'Le nom du dealer est obligatoire.',
            'numero_flooz.required' => 'Le numéro Flooz est obligatoire.',
            'shortcode.required' => 'Le shortcode est obligatoire.',
            'nom_point.required' => 'Le nom du point de vente est obligatoire.',
            'profil.required' => 'Le profil est obligatoire.',
            'region.required' => 'La région est obligatoire.',
            'region.in' => 'La région sélectionnée n\'est pas valide.',
            'prefecture.required' => 'La préfecture est obligatoire.',
            'commune.required' => 'La commune est obligatoire.',
            'ville.required' => 'La ville est obligatoire.',
            'quartier.required' => 'Le quartier est obligatoire.',
            'latitude.required' => 'La latitude est obligatoire.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.required' => 'La longitude est obligatoire.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
            'numero_proprietaire.required' => 'Le numéro du propriétaire est obligatoire.',
            'support_visibilite.required' => 'Le support de visibilité est obligatoire.',
            'numero_cagnt.required' => 'Le numéro CAGNT est obligatoire.',
        ]);

        $user = $request->user();

        // Check proximity
        $proximityCheck = $this->proximityService->checkProximity(
            $validated['latitude'],
            $validated['longitude']
        );

        // For non-admin users, force their organization_id
        if (!$user->isAdmin()) {
            $validated['organization_id'] = $user->organization_id;
        }
        
        $validated['created_by'] = $user->id;
        $validated['status'] = 'pending';

        $pdv = PointOfSale::create($validated);

        // Attach uploaded files
        if ($request->has('owner_id_document_ids')) {
            foreach ($request->owner_id_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'type' => 'id_document',
                ]);
            }
        }

        if ($request->has('photo_ids')) {
            foreach ($request->photo_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'type' => 'photo',
                ]);
            }
        }

        if ($request->has('fiscal_document_ids')) {
            foreach ($request->fiscal_document_ids as $uploadId) {
                $pdv->uploads()->create([
                    'upload_id' => $uploadId,
                    'type' => 'fiscal_document',
                ]);
            }
        }

        return response()->json([
            'pdv' => $pdv->load(['organization', 'creator', 'idDocuments', 'photos', 'fiscalDocuments']),
            'proximity_alert' => $proximityCheck,
        ], 201);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::with(['organization', 'creator', 'validator', 'idDocuments', 'photos', 'fiscalDocuments']);

        $pdv = $query->findOrFail($id);

        // Check access permissions
        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        // Check proximity if PDV has coordinates
        $proximityCheck = null;
        if ($pdv->latitude && $pdv->longitude) {
            $proximityCheck = $this->proximityService->checkProximity(
                $pdv->latitude,
                $pdv->longitude,
                $pdv->id
            );
        }

        return response()->json([
            'pdv' => $pdv,
            'proximity_alert' => $proximityCheck,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $pdv = PointOfSale::findOrFail($id);

        // Check access permissions
        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        // Only allow updates if pending
        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'Can only update pending PDVs'], 422);
        }

        $validated = $request->validate([
            'dealer_name' => 'sometimes|string',
            'numero_flooz' => 'sometimes|string',
            'shortcode' => 'nullable|string',
            'nom_point' => 'sometimes|string',
            'profil' => 'nullable|string',
            'type_activite' => 'nullable|string',
            'firstname' => 'sometimes|string',
            'lastname' => 'sometimes|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'region' => 'sometimes|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'prefecture' => 'sometimes|string',
            'commune' => 'nullable|string',
            'ville' => 'nullable|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
        ]);

        $pdv->update($validated);

        return response()->json($pdv->load(['organization', 'creator']));
    }

    public function validatePdv(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can validate'], 403);
        }

        $pdv = PointOfSale::findOrFail($id);

        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'PDV is not pending'], 422);
        }

        $pdv->update([
            'status' => 'validated',
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        return response()->json($pdv->load(['organization', 'creator', 'validator']));
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can reject'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $pdv = PointOfSale::findOrFail($id);

        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'PDV is not pending'], 422);
        }

        $pdv->update([
            'status' => 'rejected',
            'validated_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json($pdv->load(['organization', 'creator', 'validator']));
    }

    public function checkProximity(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'exclude_id' => 'nullable|integer',
        ]);

        $result = $this->proximityService->checkProximity(
            $request->latitude,
            $request->longitude,
            $request->exclude_id
        );

        return response()->json($result);
    }

    public function checkUniqueness(Request $request)
    {
        $request->validate([
            'field' => 'required|in:numero_flooz,shortcode,profil',
            'value' => 'required|string',
        ]);

        $exists = PointOfSale::where($request->field, $request->value)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Cette valeur est déjà utilisée' : 'Valeur disponible',
        ]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $pdv = PointOfSale::findOrFail($id);

        // Only creator or admin can delete
        if (!$user->isAdmin() && $pdv->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only allow deletion if pending
        if ($pdv->status !== 'pending') {
            return response()->json(['message' => 'Can only delete pending PDVs'], 422);
        }

        $pdv->delete();

        return response()->json(['message' => 'PDV deleted successfully']);
    }
}

