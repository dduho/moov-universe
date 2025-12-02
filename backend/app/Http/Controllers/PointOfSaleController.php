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
        $query = PointOfSale::with(['organization', 'creator', 'validator']);

        // Filter based on user role
        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
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

        $perPage = $request->get('per_page', 15);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'dealer_name' => 'required|string',
            'numero_flooz' => 'required|string',
            'shortcode' => 'nullable|string',
            'nom_point' => 'required|string',
            'profil' => 'nullable|string',
            'type_activite' => 'nullable|string',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
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
            'commune' => 'nullable|string',
            'canton' => 'nullable|string',
            'ville' => 'nullable|string',
            'quartier' => 'nullable|string',
            'localisation' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
            'numero_proprietaire' => 'nullable|string',
            'autre_contact' => 'nullable|string',
            'nif' => 'nullable|string',
            'regime_fiscal' => 'nullable|string',
            'support_visibilite' => 'nullable|string',
            'etat_support' => 'nullable|in:BON,MAUVAIS',
            'numero_cagnt' => 'nullable|string',
        ]);

        $user = $request->user();

        // Check proximity
        $proximityCheck = $this->proximityService->checkProximity(
            $validated['latitude'],
            $validated['longitude']
        );

        $validated['organization_id'] = $user->organization_id;
        $validated['created_by'] = $user->id;
        $validated['status'] = 'pending';

        $pdv = PointOfSale::create($validated);

        return response()->json([
            'pdv' => $pdv->load(['organization', 'creator']),
            'proximity_alert' => $proximityCheck,
        ], 201);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();
        $query = PointOfSale::with(['organization', 'creator', 'validator']);

        if (!$user->isAdmin()) {
            $query->where('organization_id', $user->organization_id);
        }

        $pdv = $query->findOrFail($id);

        return response()->json($pdv);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $pdv = PointOfSale::findOrFail($id);

        // Check access
        if (!$user->isAdmin() && $pdv->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Forbidden'], 403);
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

    public function validate(Request $request, $id)
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

