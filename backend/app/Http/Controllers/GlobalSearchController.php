<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GlobalSearchController extends Controller
{
    /**
     * Recherche globale multi-entités avec cache
     * 
     * Recherche dans : PDV, Dealers, Régions, Préfectures, Communes, Villes
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'type' => 'nullable|in:all,pdv,dealer,region,prefecture,commune,ville',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|in:pending,validated,rejected',
            'filters.region' => 'nullable|in:MARITIME,PLATEAUX,CENTRALE,KARA,SAVANES',
            'filters.prefecture' => 'nullable|string|max:255',
            'filters.dealer_id' => 'nullable|exists:organizations,id',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'limit' => 'nullable|integer|min:5|max:50',
        ]);

        $query = trim($request->input('query'));
        $type = $request->input('type', 'all');
        $filters = $request->input('filters', []);
        $limit = $request->input('limit', 10);
        
        $user = $request->user();

        // Cache key basé sur tous les paramètres
        $cacheKey = "global_search:" . md5(json_encode([
            'query' => $query,
            'type' => $type,
            'filters' => $filters,
            'limit' => $limit,
            'user_id' => $user->id,
            'org_id' => $user->organization_id,
        ]));

        return Cache::remember($cacheKey, 600, function () use ($query, $type, $filters, $limit, $user) {
            $results = [
                'query' => $query,
                'type' => $type,
                'pdv' => [],
                'dealers' => [],
                'regions' => [],
                'prefectures' => [],
                'communes' => [],
                'villes' => [],
                'total_results' => 0,
            ];

            // PDV Search
            if ($type === 'all' || $type === 'pdv') {
                $results['pdv'] = $this->searchPdvs($query, $filters, $limit, $user);
            }

            // Dealer Search
            if ($type === 'all' || $type === 'dealer') {
                $results['dealers'] = $this->searchDealers($query, $filters, $limit, $user);
            }

            // Geographic Search
            if ($type === 'all' || in_array($type, ['region', 'prefecture', 'commune', 'ville'])) {
                $geoResults = $this->searchGeographic($query, $type, $filters, $limit, $user);
                $results = array_merge($results, $geoResults);
            }

            // Calculate total
            $results['total_results'] = 
                count($results['pdv']) + 
                count($results['dealers']) + 
                count($results['regions']) + 
                count($results['prefectures']) + 
                count($results['communes']) + 
                count($results['villes']);

            return response()->json($results);
        });
    }

    /**
     * Recherche de PDV
     */
    private function searchPdvs(string $query, array $filters, int $limit, User $user): array
    {
        $pdvQuery = PointOfSale::query()
            ->select([
                'id', 'numero', 'nom_point', 'numero_flooz', 'shortcode',
                'region', 'prefecture', 'commune', 'ville', 'quartier',
                'status', 'created_at', 'organization_id', 'latitude', 'longitude'
            ])
            ->with(['organization:id,name,code']);

        // Apply user role filters
        if ($user->isDealerOwner()) {
            $pdvQuery->where('organization_id', $user->organization_id);
        } elseif ($user->isDealerAgent()) {
            $pdvQuery->where('created_by', $user->id);
        }

        // Search in multiple fields
        $pdvQuery->where(function ($q) use ($query) {
            $q->where('nom_point', 'LIKE', "%{$query}%")
              ->orWhere('numero_flooz', 'LIKE', "%{$query}%")
              ->orWhere('shortcode', 'LIKE', "%{$query}%")
              ->orWhere('firstname', 'LIKE', "%{$query}%")
              ->orWhere('lastname', 'LIKE', "%{$query}%")
              ->orWhere('ville', 'LIKE', "%{$query}%")
              ->orWhere('quartier', 'LIKE', "%{$query}%");
        });

        // Apply additional filters
        if (!empty($filters['status'])) {
            $pdvQuery->where('status', $filters['status']);
        }
        if (!empty($filters['region'])) {
            $pdvQuery->where('region', $filters['region']);
        }
        if (!empty($filters['prefecture'])) {
            $pdvQuery->where('prefecture', $filters['prefecture']);
        }
        if (!empty($filters['dealer_id'])) {
            $pdvQuery->where('organization_id', $filters['dealer_id']);
        }
        if (!empty($filters['date_from'])) {
            $pdvQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $pdvQuery->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $pdvQuery->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($pdv) {
                return [
                    'id' => $pdv->id,
                    'type' => 'pdv',
                    'numero' => $pdv->numero,
                    'nom_point' => $pdv->nom_point,
                    'numero_flooz' => $pdv->numero_flooz,
                    'shortcode' => $pdv->shortcode,
                    'dealer_name' => $pdv->organization->name ?? 'N/A',
                    'organization' => $pdv->organization ? [
                        'id' => $pdv->organization->id,
                        'name' => $pdv->organization->name,
                        'code' => $pdv->organization->code,
                    ] : null,
                    'location' => [
                        'region' => $pdv->region,
                        'prefecture' => $pdv->prefecture,
                        'commune' => $pdv->commune,
                        'ville' => $pdv->ville,
                        'quartier' => $pdv->quartier,
                        'latitude' => $pdv->latitude,
                        'longitude' => $pdv->longitude,
                    ],
                    'status' => $pdv->status,
                    'created_at' => $pdv->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Recherche de Dealers (Organizations)
     */
    private function searchDealers(string $query, array $filters, int $limit, User $user): array
    {
        $dealerQuery = Organization::query()
            ->select(['id', 'name', 'code', 'phone', 'email', 'address', 'is_active', 'created_at'])
            ->where('is_active', true);

        // Admin only feature - dealers can't search other dealers
        if (!$user->isAdmin()) {
            return [];
        }

        // Search in dealer fields
        $dealerQuery->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('code', 'LIKE', "%{$query}%")
              ->orWhere('email', 'LIKE', "%{$query}%")
              ->orWhere('phone', 'LIKE', "%{$query}%");
        });

        $dealers = $dealerQuery->orderBy('name')
            ->limit($limit)
            ->get();

        // Get PDV stats for each dealer
        return $dealers->map(function ($dealer) {
            $stats = DB::table('point_of_sales')
                ->where('organization_id', $dealer->id)
                ->selectRaw('
                    COUNT(*) as total_pdv,
                    SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated_pdv,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_pdv
                ')
                ->first();

            return [
                'id' => $dealer->id,
                'type' => 'dealer',
                'name' => $dealer->name,
                'code' => $dealer->code,
                'phone' => $dealer->phone,
                'email' => $dealer->email,
                'address' => $dealer->address,
                'created_at' => $dealer->created_at,
                'stats' => [
                    'total_pdv' => $stats->total_pdv ?? 0,
                    'validated_pdv' => $stats->validated_pdv ?? 0,
                    'pending_pdv' => $stats->pending_pdv ?? 0,
                ],
            ];
        })->toArray();
    }

    /**
     * Recherche géographique (Régions, Préfectures, Communes, Villes)
     */
    private function searchGeographic(string $query, string $type, array $filters, int $limit, User $user): array
    {
        $results = [
            'regions' => [],
            'prefectures' => [],
            'communes' => [],
            'villes' => [],
        ];

        $baseQuery = PointOfSale::query();

        // Apply user role filters
        if ($user->isDealerOwner()) {
            $baseQuery->where('organization_id', $user->organization_id);
        } elseif ($user->isDealerAgent()) {
            $baseQuery->where('created_by', $user->id);
        }

        // Apply additional filters
        if (!empty($filters['status'])) {
            $baseQuery->where('status', $filters['status']);
        }
        if (!empty($filters['dealer_id']) && $user->isAdmin()) {
            $baseQuery->where('organization_id', $filters['dealer_id']);
        }

        // Régions
        if ($type === 'all' || $type === 'region') {
            $regions = ['MARITIME', 'PLATEAUX', 'CENTRALE', 'KARA', 'SAVANES'];
            $matchingRegions = array_filter($regions, function ($region) use ($query) {
                return stripos($region, $query) !== false;
            });

            foreach ($matchingRegions as $region) {
                $stats = (clone $baseQuery)
                    ->where('region', $region)
                    ->selectRaw('
                        COUNT(*) as total_pdv,
                        SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated_pdv,
                        COUNT(DISTINCT prefecture) as prefectures_count
                    ')
                    ->first();

                $results['regions'][] = [
                    'type' => 'region',
                    'name' => $region,
                    'stats' => [
                        'total_pdv' => $stats->total_pdv ?? 0,
                        'validated_pdv' => $stats->validated_pdv ?? 0,
                        'prefectures_count' => $stats->prefectures_count ?? 0,
                    ],
                ];
            }
        }

        // Préfectures
        if ($type === 'all' || $type === 'prefecture') {
            $prefectures = (clone $baseQuery)
                ->whereNotNull('prefecture')
                ->where('prefecture', 'LIKE', "%{$query}%")
                ->groupBy('prefecture', 'region')
                ->selectRaw('
                    prefecture,
                    region,
                    COUNT(*) as total_pdv,
                    SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated_pdv
                ')
                ->orderBy('prefecture')
                ->limit($limit)
                ->get();

            $results['prefectures'] = $prefectures->map(function ($pref) {
                return [
                    'type' => 'prefecture',
                    'name' => $pref->prefecture,
                    'region' => $pref->region,
                    'stats' => [
                        'total_pdv' => $pref->total_pdv,
                        'validated_pdv' => $pref->validated_pdv,
                    ],
                ];
            })->toArray();
        }

        // Communes
        if ($type === 'all' || $type === 'commune') {
            $communes = (clone $baseQuery)
                ->whereNotNull('commune')
                ->where('commune', 'LIKE', "%{$query}%")
                ->groupBy('commune', 'prefecture', 'region')
                ->selectRaw('
                    commune,
                    prefecture,
                    region,
                    COUNT(*) as total_pdv,
                    SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated_pdv
                ')
                ->orderBy('commune')
                ->limit($limit)
                ->get();

            $results['communes'] = $communes->map(function ($comm) {
                return [
                    'type' => 'commune',
                    'name' => $comm->commune,
                    'prefecture' => $comm->prefecture,
                    'region' => $comm->region,
                    'stats' => [
                        'total_pdv' => $comm->total_pdv,
                        'validated_pdv' => $comm->validated_pdv,
                    ],
                ];
            })->toArray();
        }

        // Villes
        if ($type === 'all' || $type === 'ville') {
            $villes = (clone $baseQuery)
                ->whereNotNull('ville')
                ->where('ville', 'LIKE', "%{$query}%")
                ->groupBy('ville', 'commune', 'prefecture', 'region')
                ->selectRaw('
                    ville,
                    commune,
                    prefecture,
                    region,
                    COUNT(*) as total_pdv,
                    SUM(CASE WHEN status = "validated" THEN 1 ELSE 0 END) as validated_pdv
                ')
                ->orderBy('ville')
                ->limit($limit)
                ->get();

            $results['villes'] = $villes->map(function ($ville) {
                return [
                    'type' => 'ville',
                    'name' => $ville->ville,
                    'commune' => $ville->commune,
                    'prefecture' => $ville->prefecture,
                    'region' => $ville->region,
                    'stats' => [
                        'total_pdv' => $ville->total_pdv,
                        'validated_pdv' => $ville->validated_pdv,
                    ],
                ];
            })->toArray();
        }

        return $results;
    }

    /**
     * Suggestions de recherche rapide (autocomplete)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:3|max:20',
        ]);

        $query = trim($request->input('query'));
        $limit = $request->input('limit', 8);
        $user = $request->user();

        $cacheKey = "search_suggestions:" . md5($query . $user->id . $limit);

        return Cache::remember($cacheKey, 300, function () use ($query, $limit, $user) {
            $suggestions = [];

            // Top 3 PDV matches
            $pdvQuery = PointOfSale::query()
                ->select(['id', 'nom_point', 'numero_flooz', 'region', 'ville'])
                ->where('status', 'validated');

            if ($user->isDealerOwner()) {
                $pdvQuery->where('organization_id', $user->organization_id);
            } elseif ($user->isDealerAgent()) {
                $pdvQuery->where('created_by', $user->id);
            }

            $pdvs = $pdvQuery->where(function ($q) use ($query) {
                    $q->where('nom_point', 'LIKE', "%{$query}%")
                      ->orWhere('numero_flooz', 'LIKE', "%{$query}%")
                      ->orWhere('ville', 'LIKE', "%{$query}%");
                })
                ->limit(3)
                ->get();

            foreach ($pdvs as $pdv) {
                $suggestions[] = [
                    'type' => 'pdv',
                    'id' => $pdv->id,
                    'label' => $pdv->nom_point,
                    'subtitle' => $pdv->numero_flooz . ' - ' . $pdv->ville . ', ' . $pdv->region,
                    'url' => "/pdv/{$pdv->id}",
                ];
            }

            // Top 2 Dealer matches (admin only)
            if ($user->isAdmin()) {
                $dealers = Organization::query()
                    ->select(['id', 'name', 'code'])
                    ->where('is_active', true)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                          ->orWhere('code', 'LIKE', "%{$query}%");
                    })
                    ->limit(2)
                    ->get();

                foreach ($dealers as $dealer) {
                    $suggestions[] = [
                        'type' => 'dealer',
                        'id' => $dealer->id,
                        'label' => $dealer->name,
                        'subtitle' => 'Code: ' . $dealer->code,
                        'url' => "/dealers/{$dealer->id}",
                    ];
                }
            }

            // Geographic suggestions (max 3)
            $geoSuggestions = [];
            
            // Prefectures
            $prefectures = PointOfSale::query()
                ->whereNotNull('prefecture')
                ->where('prefecture', 'LIKE', "%{$query}%")
                ->distinct()
                ->pluck('prefecture')
                ->take(2);

            foreach ($prefectures as $prefecture) {
                $geoSuggestions[] = [
                    'type' => 'prefecture',
                    'label' => $prefecture,
                    'subtitle' => 'Préfecture',
                    'url' => "/pdv/list?prefecture={$prefecture}",
                ];
            }

            // Villes
            if (count($geoSuggestions) < 3) {
                $villes = PointOfSale::query()
                    ->whereNotNull('ville')
                    ->where('ville', 'LIKE', "%{$query}%")
                    ->distinct()
                    ->pluck('ville')
                    ->take(3 - count($geoSuggestions));

                foreach ($villes as $ville) {
                    $geoSuggestions[] = [
                        'type' => 'ville',
                        'label' => $ville,
                        'subtitle' => 'Ville',
                        'url' => "/pdv/list?ville={$ville}",
                    ];
                }
            }

            $suggestions = array_merge($suggestions, $geoSuggestions);

            return response()->json([
                'query' => $query,
                'suggestions' => array_slice($suggestions, 0, $limit),
            ]);
        });
    }
}
