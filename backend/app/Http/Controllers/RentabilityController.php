<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RentabilityController extends Controller
{
    /**
     * Get rentability analysis for PDVs, Dealers and Regions
     * Calculates ROI, margins, costs and revenue
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'scope' => 'nullable|in:global,region,dealer,pdv',
            'entity_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'group_by' => 'nullable|in:pdv,dealer,region',
            'sort_by' => 'nullable|in:roi,ca,margin,cost',
            'sort_order' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $scope = $request->input('scope', 'global');
        $entityId = $request->input('entity_id');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'pdv');
        $sortBy = $request->input('sort_by', 'roi');
        $sortOrder = $request->input('sort_order', 'desc');
        $limit = $request->input('limit', 50);

        // Check cache settings
        $cacheEnabled = SystemSetting::getValue('cache_rentability_enabled', true);
        $cacheTtl = SystemSetting::getValue('cache_rentability_ttl', 240); // 4 hours default

        $cacheKey = "rentability_{$scope}_{$entityId}_{$startDate}_{$endDate}_{$groupBy}_{$sortBy}_{$sortOrder}_{$limit}";
        $wasCached = Cache::tags(['rentability', 'analytics'])->has($cacheKey);

        if (!$cacheEnabled) {
            $result = $this->executeRentabilityAnalysis($scope, $entityId, $startDate, $endDate, $groupBy, $sortBy, $sortOrder, $limit);
            return $this->addCacheInfo($result, $cacheEnabled, $cacheTtl, false, $cacheKey);
        }

        $result = Cache::tags(['rentability', 'analytics'])->remember($cacheKey, $cacheTtl * 60, function () use ($scope, $entityId, $startDate, $endDate, $groupBy, $sortBy, $sortOrder, $limit) {
            return $this->executeRentabilityAnalysis($scope, $entityId, $startDate, $endDate, $groupBy, $sortBy, $sortOrder, $limit);
        });
        
        return $this->addCacheInfo($result, $cacheEnabled, $cacheTtl, $wasCached, $cacheKey);
    }

    /**
     * Execute rentability analysis
     */
    private function executeRentabilityAnalysis($scope, $entityId, $startDate, $endDate, $groupBy, $sortBy, $sortOrder, $limit)
    {
        $query = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->leftJoin('organizations as o', 'p.organization_id', '=', 'o.id')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->where('p.status', 'validated');

        // Apply scope filters
        if ($scope === 'dealer' && $entityId) {
            $query->where('p.organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $query->where('p.id', $entityId);
        } elseif ($scope === 'region' && $entityId) {
            $query->where('p.region', $entityId);
        }

        // Group by
        $selectFields = [
            'p.id as pdv_id',
            'p.nom_point as pdv_name',
            'p.numero_flooz as pdv_numero',
            'p.region',
            'o.id as dealer_id',
            'o.name as dealer_name',
        ];

        if ($groupBy === 'pdv') {
            $groupFields = ['p.id', 'p.nom_point', 'p.numero_flooz', 'p.region', 'p.created_at', 'o.id', 'o.name'];
        } elseif ($groupBy === 'dealer') {
            $selectFields = [
                'o.id as dealer_id',
                'o.name as dealer_name',
            ];
            $groupFields = ['o.id', 'o.name'];
        } else { // region
            $selectFields = [
                'p.region',
            ];
            $groupFields = ['p.region'];
        }

        $query->select(array_merge($selectFields, [
            DB::raw('SUM(t.retrait_keycost) as total_ca'), // Seul le RETRAIT_KEYCOST génère du CA
            DB::raw('SUM(t.count_depot) as total_depot_count'),
            DB::raw('SUM(t.count_retrait) as total_retrait_count'),
            DB::raw('SUM(t.sum_depot) as total_depot_amount'),
            DB::raw('SUM(t.sum_retrait) as total_retrait_amount'),
            DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as dealer_commissions'),
            DB::raw('SUM(t.pdv_depot_commission + t.pdv_retrait_commission) as pdv_commissions'),
            DB::raw('COUNT(DISTINCT t.transaction_date) as active_days'),
            DB::raw('COUNT(DISTINCT t.pdv_numero) as pdv_count'),
            DB::raw('COUNT(DISTINCT o.id) as dealer_count'),
        ]));

        $query->groupBy($groupFields);

        $results = $query->get();

        // Calculate rentability metrics avec le vrai modèle économique
        $rentabilityData = $results->map(function ($item) use ($groupBy, $startDate, $endDate) {
            $totalCa = $item->total_ca ?? 0; // RETRAIT_KEYCOST uniquement
            $dealerCommissions = $item->dealer_commissions ?? 0; // DEALER_DEPOT + DEALER_RETRAIT commissions
            $pdvCommissions = $item->pdv_commissions ?? 0; // PDV_DEPOT + PDV_RETRAIT commissions
            $totalCommissions = $dealerCommissions + $pdvCommissions;
            
            // Revenue Moov = CA généré - Toutes les commissions payées
            $revenue = $totalCa - $totalCommissions;
            
            // Pas de coûts d'activation/opérationnels réels selon le modèle
            $totalCost = $totalCommissions; // Les commissions sont le seul "coût"
            
            // Margin = Revenue (identique au revenue ici)
            $margin = $revenue;
            
            // Ratio de rentabilité = Revenue / Commissions * 100 (combien Moov gagne par FCFA de commission)
            $roi = $totalCommissions > 0 ? ($revenue / $totalCommissions) * 100 : 0;
            
            // Margin rate = Marge / CA * 100
            $marginRate = $totalCa > 0 ? ($margin / $totalCa) * 100 : 0;
            $data = [
                'total_ca' => round($totalCa, 2),
                'depot_count' => $item->total_depot_count ?? 0,
                'retrait_count' => $item->total_retrait_count ?? 0,
                'depot_amount' => round($item->total_depot_amount ?? 0, 2),
                'retrait_amount' => round($item->total_retrait_amount ?? 0, 2),
                'dealer_commissions' => round($dealerCommissions, 2),
                'pdv_commissions' => round($pdvCommissions, 2),
                'estimated_commission' => round($totalCommissions, 2), // Pour compatibilité
                'total_cost' => round($totalCost, 2),
                'revenue' => round($revenue, 2),
                'margin' => round($margin, 2),
                'margin_rate' => round($marginRate, 2),
                'roi' => round($roi, 2),
                'active_days' => $item->active_days ?? 0,
                'pdv_count' => $item->pdv_count ?? 1,
                'dealer_count' => $item->dealer_count ?? 0,
            ];

            if ($groupBy === 'pdv') {
                $data['pdv_id'] = $item->pdv_id;
                $data['pdv_name'] = $item->pdv_name;
                $data['pdv_numero'] = $item->pdv_numero;
                $data['region'] = $item->region;
                $data['dealer_id'] = $item->dealer_id;
                $data['dealer_name'] = $item->dealer_name;
                $data['name'] = $item->pdv_name; // Pour compatibilité
            } elseif ($groupBy === 'dealer') {
                $data['dealer_id'] = $item->dealer_id;
                $data['dealer_name'] = $item->dealer_name;
                $data['name'] = $item->dealer_name; // Pour compatibilité
            } else {
                $data['region'] = $item->region;
                $data['region_name'] = $item->region;
                $data['name'] = $item->region; // Pour compatibilité
            }

            return $data;
        });

        // Calculate summary statistics BEFORE filtering/limiting for accurate totals
        $summary = [
            'total_ca' => $rentabilityData->sum('total_ca'),
            'total_revenue' => $rentabilityData->sum('revenue'),
            'total_margin' => $rentabilityData->sum('margin'),
            'total_cost' => $rentabilityData->sum('total_cost'),
            'avg_roi' => $rentabilityData->avg('roi'),
            'avg_margin_rate' => $rentabilityData->avg('margin_rate'),
            'total_pdvs' => $rentabilityData->sum('pdv_count'),
            'profitable_count' => $rentabilityData->where('margin', '>', 0)->count(),
            'total_records' => $rentabilityData->count(),
        ];

        // Sort results with proper field mapping
        $sortFieldMapping = [
            'ca' => 'total_ca',
            'revenue' => 'revenue', 
            'margin' => 'margin_rate',
            'roi' => 'roi',
            'cost' => 'total_cost'
        ];
        
        $actualSortField = $sortFieldMapping[$sortBy] ?? $sortBy;
        
        $rentabilityData = $rentabilityData->sortBy(function ($item) use ($actualSortField) {
            return $item[$actualSortField];
        }, SORT_REGULAR, $sortOrder === 'desc');

        // Limit results
        $rentabilityData = $rentabilityData->take($limit)->values();

        // Top/Bottom performers
        $topPerformers = $rentabilityData->sortByDesc('roi')->take(5)->values();
        $bottomPerformers = $rentabilityData->sortBy('roi')->take(5)->values();

        return response()->json([
            'success' => true,
            'data' => $rentabilityData,
            'summary' => $summary,
            'top_performers' => $topPerformers,
            'bottom_performers' => $bottomPerformers,
            'parameters' => [
                'scope' => $scope,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy,
                'model' => 'CA = RETRAIT_KEYCOST, Revenue = CA - Commissions, Ratio = Revenue/Commissions × 100 (rentabilité par FCFA de commission)',
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Add cache information to response
     */
    private function addCacheInfo($response, $cacheEnabled, $cacheTtl, $wasCached, $cacheKey)
    {
        $data = $response->getData(true);
        $data['cache_info'] = [
            'enabled' => $cacheEnabled,
            'ttl_minutes' => $cacheTtl,
            'is_cached' => $wasCached,
            'cache_key' => $cacheKey,
        ];
        
        return response()->json($data);
    }

    /**
     * Clear rentability cache
     */
    public function clearCache()
    {
        Cache::tags(['rentability'])->flush();
        
        return response()->json([
            'success' => true,
            'message' => 'Cache de rentabilité vidé avec succès'
        ]);
    }
}
