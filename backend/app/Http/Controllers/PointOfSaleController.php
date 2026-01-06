<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Services\ProximityAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PointOfSaleController extends Controller
{
    protected $proximityService;

    public function __construct(ProximityAlertService $proximityService)
    {
        $this->proximityService = $proximityService;
    }

    private function flushPdvCache(): void
    {
        try {
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['pdv-index'])->flush();
            } else {
                Cache::flush();
            }
        } catch (\Throwable $e) {
            \Log::warning('PDV cache flush failed: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que la relation role est chargée
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Ne charger que les colonnes nécessaires et relations essentielles
        $query = PointOfSale::select([
            'id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode',
            'profil', 'region', 'prefecture', 'commune', 'ville', 'quartier',
            'status', 'created_by', 'validated_by', 'created_at', 'updated_at',
            'latitude', 'longitude', 'canton',
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
            // Admins can filter; dealer owners already scoped above
            if ($user->isAdmin()) {
                $query->where('organization_id', $request->organization_id);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_point', 'like', "%{$search}%")
                  ->orWhere('dealer_name', 'like', "%{$search}%")
                  ->orWhere('numero_flooz', 'like', "%{$search}%");
            });
        }

        // Filtres de qualité des données
        if ($request->has('incomplete_data') && $request->incomplete_data) {
            // PDV avec des champs obligatoires manquants
            $query->whereRaw('(
                firstname IS NULL OR firstname = "" OR
                lastname IS NULL OR lastname = "" OR
                id_description IS NULL OR id_description = "" OR
                id_number IS NULL OR id_number = "" OR
                nationality IS NULL OR nationality = "" OR
                profession IS NULL OR profession = ""
            )');
        }

        if ($request->has('no_gps') && $request->no_gps) {
            // PDV sans coordonnées GPS
            $query->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', '')
                  ->orWhere('longitude', '');
            });
        }

        if ($request->has('geo_inconsistency') && $request->geo_inconsistency) {
            // PDV avec incohérences géographiques
            // On doit vérifier chaque PDV individuellement avec validateRegionCoordinates
            // car cela utilise des polygones précis, pas juste des bounds rectangulaires
            $geoService = new \App\Services\GeoValidationService();
            
            // Récupérer tous les PDV avec GPS et région
            $tempQuery = clone $query;
            $pdvsToCheck = $tempQuery
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('region')
                ->get(['id', 'latitude', 'longitude', 'region']);
            
            $pdvIdsWithAlert = [];
            foreach ($pdvsToCheck as $pdv) {
                $validation = $geoService->validateRegionCoordinates(
                    (float) $pdv->latitude,
                    (float) $pdv->longitude,
                    $pdv->region
                );
                
                if ($validation['has_alert']) {
                    $pdvIdsWithAlert[] = $pdv->id;
                }
            }
            
            if (!empty($pdvIdsWithAlert)) {
                $query->whereIn('id', $pdvIdsWithAlert);
            } else {
                // Aucun PDV avec alerte, retourner vide
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->has('proximity_alert') && $request->proximity_alert) {
            // PDV trop proches d'autres PDV
            try {
                $organizationId = $request->organization_id ?? null;
                $proximityData = $this->proximityService->getAllProximityAlerts($user, $organizationId);
                
                $pdvIdsWithAlert = [];
                // L'API retourne ['clusters' => [...], 'count' => ..., etc.]
                if (!empty($proximityData['clusters'])) {
                    foreach ($proximityData['clusters'] as $cluster) {
                        if (isset($cluster['pdvs']) && is_array($cluster['pdvs'])) {
                            foreach ($cluster['pdvs'] as $pdv) {
                                if (isset($pdv['id'])) {
                                    $pdvIdsWithAlert[] = $pdv['id'];
                                }
                            }
                        }
                    }
                }
                
                if (!empty($pdvIdsWithAlert)) {
                    $query->whereIn('id', array_unique($pdvIdsWithAlert));
                } else {
                    // Aucun PDV avec alerte, retourner vide
                    $query->whereRaw('1 = 0');
                }
            } catch (\Exception $e) {
                \Log::error('Error filtering proximity alerts: ' . $e->getMessage());
                // En cas d'erreur, ne pas filtrer (retourner tous)
            }
        }

        // Pagination with performance optimization (cacheable)
        $perPage = $request->get('per_page', 50); // Default to 50 for better performance
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Limiter le per_page max à 100 pour éviter les surcharges
        if ($perPage > 100) {
            $perPage = 100;
        }
        
        // Cache layer: vary by user/org and query string to avoid cross-tenant leaks
        $cacheKeyParts = [
            'pdv-index',
            'user:' . $user->id,
            'org:' . ($user->organization_id ?? 'none'),
            'role:' . ($user->role->name ?? 'none'),
            'params:' . md5(json_encode($request->all()))
        ];
        $cacheKey = implode('|', $cacheKeyParts);

        $ttlSeconds = 300; // 5 minutes

        $cache = cache();

        if (method_exists($cache, 'tags')) {
            $paginator = $cache->tags(['pdv-index'])->remember($cacheKey, $ttlSeconds, function () use ($query, $sortBy, $sortOrder, $perPage) {
                return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
            });
        } else {
            $paginator = $cache->remember($cacheKey, $ttlSeconds, function () use ($query, $sortBy, $sortOrder, $perPage) {
                return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
            });
        }

        return response()->json($paginator);
    }

    /**
     * Get all points of sale for map view (optimized, no pagination)
     * Returns only essential fields for markers
     */
    public function forMap(Request $request)
    {
        $user = $request->user();
        
        // S'assurer que la relation role est chargée
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Sélectionner uniquement les champs nécessaires pour la carte
        $query = PointOfSale::select([
            'id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode',
            'profil', 'region', 'prefecture', 'ville', 'quartier',
            'status', 'latitude', 'longitude'
        ])->with(['organization:id,name']);

        // Filter based on user role
        if ($user->isAdmin()) {
            // Admins see all PDV
        } elseif ($user->isDealerOwner()) {
            $query->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            $query->where('created_by', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('region') && $request->region) {
            $query->where('region', $request->region);
        }

        if ($request->has('organization_id') && $request->organization_id) {
            $query->where('organization_id', $request->organization_id);
        }

        // Exclure les PDV sans coordonnées GPS valides
        $query->whereNotNull('latitude')
              ->whereNotNull('longitude')
              ->where('latitude', '!=', 0)
              ->where('longitude', '!=', 0);

        // Retourner tous les résultats sans pagination
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        if ($request->has('support_visibilite')) {
            $request->merge([
                'support_visibilite' => $this->normalizeSupportVisibilite($request->input('support_visibilite')),
            ]);
        }

        if ($request->has('etat_support') && $request->filled('etat_support')) {
            $request->merge([
                'etat_support' => strtoupper($request->input('etat_support')),
            ]);
        }

        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
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
            'support_visibilite' => 'nullable|string',
            'etat_support' => 'nullable|in:BON,ACCEPTABLE,MAUVAIS,DEFRAICHI',
            'numero_cagnt' => 'required|string',
        ], [
            'numero_flooz.unique' => 'Ce numéro Flooz est déjà utilisé par un autre point de vente.',
            'shortcode.unique' => 'Ce shortcode est déjà utilisé par un autre point de vente.',
            'organization_id.required' => 'L\'organisation est obligatoire.',
            'organization_id.exists' => 'L\'organisation sélectionnée n\'existe pas.',
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

        $this->flushPdvCache();

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
        $query = PointOfSale::with(['organization', 'creator', 'validator', 'idDocuments', 'photos', 'fiscalDocuments', 'tasks']);

        $pdv = $query->findOrFail($id);

        // Check access permissions
        if (!$user->canAccessPointOfSale($pdv)) {
            return response()->json(['message' => 'Forbidden - You do not have access to this PDV'], 403);
        }

        // has_active_task est maintenant calculé automatiquement via l'accessor du modèle

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

        // Logique de permission de modification:
        // - Admin: peut toujours modifier un PDV quel que soit son statut
        // - Commercial: peut modifier si PDV pending OU si une tâche est en révision demandée
        $canUpdate = false;
        
        if ($user->isAdmin()) {
            // Admin peut toujours modifier
            $canUpdate = true;
        } elseif ($pdv->status === 'pending') {
            $canUpdate = true;
        } else {
            // Commercial peut modifier seulement si une tâche est en révision demandée
            $canUpdate = $pdv->tasks()->where('status', 'revision_requested')->exists();
        }
        
        if (!$canUpdate) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier ce PDV'], 422);
        }

        if ($request->has('support_visibilite')) {
            $request->merge([
                'support_visibilite' => $this->normalizeSupportVisibilite($request->input('support_visibilite')),
            ]);
        }

        if ($request->has('etat_support') && $request->filled('etat_support')) {
            $request->merge([
                'etat_support' => strtoupper($request->input('etat_support')),
            ]);
        }

        $validated = $request->validate([
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
            'quartier' => 'nullable|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'gps_accuracy' => 'nullable|numeric',
            'support_visibilite' => 'nullable|string',
            'etat_support' => 'nullable|in:BON,ACCEPTABLE,MAUVAIS,DEFRAICHI',
            'numero_cagnt' => 'nullable|string',
            'type_activite' => 'nullable|string',
            'regime_fiscal' => 'nullable|string',
            'nif' => 'nullable|string',
            'autre_contact' => 'nullable|string',
        ]);

        $pdv->update($validated);

        $this->flushPdvCache();

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

        $this->flushPdvCache();

        // Notifier le créateur du PDV
        if ($pdv->creator) {
            DB::table('notifications')->insert([
                'user_id' => $pdv->creator->id,
                'type' => 'pdv_validated',
                'title' => 'PDV validé',
                'message' => 'Votre PDV "' . $pdv->nom_point . '" a été validé par ' . $user->name,
                'data' => json_encode([
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'url' => '/pdv/' . $pdv->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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

        $this->flushPdvCache();

        // Notifier le créateur du PDV
        if ($pdv->creator) {
            DB::table('notifications')->insert([
                'user_id' => $pdv->creator->id,
                'type' => 'pdv_rejected',
                'title' => 'PDV rejeté',
                'message' => 'Votre PDV "' . $pdv->nom_point . '" a été rejeté : ' . $request->rejection_reason,
                'data' => json_encode([
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'rejection_reason' => $request->rejection_reason,
                    'url' => '/pdv/' . $pdv->id,
                ]),
                'read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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

    public function getProximityAlerts(Request $request)
    {
        $user = $request->user();
        $result = $this->proximityService->getAllProximityAlerts($user);
        
        return response()->json($result);
    }

    public function checkUniqueness(Request $request)
    {
        $request->validate([
            'field' => 'required|in:numero_flooz,shortcode,profil',
            'value' => 'required|string',
            'exclude_id' => 'nullable|integer',
        ]);

        $query = PointOfSale::where($request->field, $request->value);
        
        // Exclure le PDV en cours d'édition si un ID est fourni
        if ($request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        
        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Cette valeur est déjà utilisée' : 'Valeur disponible',
        ]);
    }

    /**
     * Clear duplicate GPS coordinates
     * When two PDVs have the same coordinates, we can't know which one is correct
     * So we set both to null
     */
    public function clearDuplicateCoordinates(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Find all coordinates that appear more than once
        $duplicates = PointOfSale::selectRaw('ROUND(latitude, 6) as lat, ROUND(longitude, 6) as lng')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0)
            ->groupByRaw('ROUND(latitude, 6), ROUND(longitude, 6)')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        
        $clearedCount = 0;
        $affectedIds = [];
        
        foreach ($duplicates as $dup) {
            // Get all PDVs with these coordinates
            $pdvs = PointOfSale::whereRaw('ROUND(latitude, 6) = ?', [$dup->lat])
                ->whereRaw('ROUND(longitude, 6) = ?', [$dup->lng])
                ->get();
            
            foreach ($pdvs as $pdv) {
                $pdv->latitude = null;
                $pdv->longitude = null;
                $pdv->save();
                $clearedCount++;
                $affectedIds[] = $pdv->id;
            }
        }
        
        return response()->json([
            'message' => "Cleared coordinates for {$clearedCount} PDVs with duplicate GPS positions",
            'cleared_count' => $clearedCount,
            'affected_ids' => $affectedIds
        ]);
    }

    /**
     * Get statistics about PDVs without valid GPS coordinates
     */
    public function getGpsStats(Request $request)
    {
        $user = $request->user();
        
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        // Base query based on role
        $baseQuery = PointOfSale::query();
        
        if ($user->isAdmin()) {
            // Admin sees all
        } elseif ($user->isDealerOwner()) {
            $baseQuery->where('organization_id', $user->organization_id);
        } elseif ($user->isCommercial()) {
            $baseQuery->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('tasks', function($taskQuery) use ($user) {
                      $taskQuery->where('assigned_to', $user->id);
                  });
            });
        } elseif ($user->isDealerAgent()) {
            $baseQuery->where('created_by', $user->id);
        } else {
            $baseQuery->whereRaw('1 = 0');
        }
        
        // Total PDVs
        $total = (clone $baseQuery)->count();
        
        // PDVs without GPS
        $withoutGps = (clone $baseQuery)
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->count();
        
        // Get the list of PDVs without GPS (limited for performance)
        $pdvsWithoutGps = (clone $baseQuery)
            ->select(['id', 'nom_point', 'numero_flooz', 'region', 'ville', 'quartier', 'status', 'organization_id', 'created_by'])
            ->with(['organization:id,name', 'creator:id,name'])
            ->where(function($q) {
                $q->whereNull('latitude')
                  ->orWhereNull('longitude')
                  ->orWhere('latitude', 0)
                  ->orWhere('longitude', 0);
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        return response()->json([
            'total_pdv' => $total,
            'without_gps' => $withoutGps,
            'with_gps' => $total - $withoutGps,
            'percentage_without_gps' => $total > 0 ? round(($withoutGps / $total) * 100, 1) : 0,
            'pdvs_without_gps' => $pdvsWithoutGps
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

        $this->flushPdvCache();

        return response()->json(['message' => 'PDV deleted successfully']);
    }

    private function normalizeSupportVisibilite($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Si un tableau est reçu, l'aplatir en liste
        $parts = is_array($value) ? $value : [$value];
        $normalized = [];

        $synonyms = [
            'AUCUN' => null,
            'NONE' => null,
        ];

        $mapping = [
            'AUTOCOLLANT' => 'Autocollant',
            'POTENCE' => 'Potence',
            'CHEVALET' => 'Chevalet',
            'BUJET FLAGS' => 'Buget Flags',
            'BUDJET FLAGS' => 'Buget Flags',
            'BUDGET FLAGS' => 'Buget Flags',
            'PARASOLS' => 'Parasols',
            'PARASOL' => 'Parasols',
            'BEACH FLAGS' => 'Beach Flags',
            'BEACH FLAG' => 'Beach Flags',
            'ENSEIGNES LUMINEUSES' => 'Enseignes Lumineuses',
            'ENSEIGNE LUMINEUSE' => 'Enseignes Lumineuses',
            'DRAPELET' => 'Drapelet',
            'DRAPELET FLOOZ MONEY' => 'Drapelet',
            'DRAPEAU' => 'Drapelet',
        ];

        foreach ($parts as $entry) {
            if ($entry === null) {
                continue;
            }

            // Découper sur les séparateurs courants (+, virgule, point-virgule, barre, slash, "et")
            $split = preg_split('/[\+;,\|\/]+|\bet\b|\band\b/i', (string) $entry);
            foreach ($split as $rawPart) {
                $clean = trim($rawPart);
                if ($clean === '') {
                    continue;
                }

                $upper = strtoupper($clean);

                if (array_key_exists($upper, $synonyms) && $synonyms[$upper] === null) {
                    // AUCUN / NONE -> ignorer la liste entière
                    return null;
                }

                $mapped = $mapping[$upper] ?? null;
                if (!$mapped) {
                    // Formatage générique (titre)
                    $mapped = ucwords(strtolower($clean));
                }

                if (!in_array($mapped, $normalized, true)) {
                    $normalized[] = $mapped;
                }
            }
        }

        if (empty($normalized)) {
            return null;
        }

        return implode(', ', $normalized);
    }
}
