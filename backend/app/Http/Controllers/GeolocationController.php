<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GeolocationController extends Controller
{
    /**
     * Get PDV geolocation data with CA/transaction stats for heatmap and clustering
     * Admin only - returns all PDV with coordinates + aggregated stats
     */
    public function getPdvGeoData(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $region = $request->input('region');
        $status = $request->input('status', 'validated');
        $minCa = $request->input('min_ca', 0);
        $maxCa = $request->input('max_ca');

        $cacheKey = "geo_pdv_{$startDate}_{$endDate}_{$region}_{$status}_{$minCa}_{$maxCa}";

        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate, $region, $status, $minCa, $maxCa) {
            $query = PointOfSale::query()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('latitude', '!=', 0)
                ->where('longitude', '!=', 0);

            if ($status) {
                $query->where('status', $status);
            }

            if ($region) {
                $query->where('region', $region);
            }

            $pdvs = $query->with('organization')->get();

            $geoData = [];
            foreach ($pdvs as $pdv) {
                // Get CA and transaction stats for the period
                $stats = DB::table('pdv_transactions')
                    ->where('pdv_numero', $pdv->numero_flooz)
                    ->whereBetween('transaction_date', [$startDate, $endDate])
                    ->selectRaw('
                        SUM(retrait_keycost) as total_ca,
                        SUM(count_depot + count_retrait) as total_transactions,
                        SUM(count_depot) as total_depot,
                        SUM(count_retrait) as total_retrait,
                        COUNT(DISTINCT transaction_date) as active_days
                    ')
                    ->first();

                $totalCa = $stats->total_ca ?? 0;
                $totalTransactions = $stats->total_transactions ?? 0;

                // Apply CA filters
                if ($minCa > 0 && $totalCa < $minCa) {
                    continue;
                }
                if ($maxCa && $totalCa > $maxCa) {
                    continue;
                }

                $geoData[] = [
                    'id' => $pdv->id,
                    'pdv_numero' => $pdv->numero_flooz,
                    'nom' => $pdv->nom_point,
                    'latitude' => (float) $pdv->latitude,
                    'longitude' => (float) $pdv->longitude,
                    'region' => $pdv->region,
                    'dealer_name' => $pdv->dealer_name,
                    'status' => $pdv->status,
                    'total_ca' => $totalCa,
                    'total_transactions' => $totalTransactions,
                    'total_depot' => $stats->total_depot ?? 0,
                    'total_retrait' => $stats->total_retrait ?? 0,
                    'active_days' => $stats->active_days ?? 0,
                    'avg_daily_ca' => $stats->active_days > 0 ? $totalCa / $stats->active_days : 0,
                ];
            }

            // Calculate aggregates for potential zones analysis
            $summary = [
                'total_pdv' => count($geoData),
                'total_ca' => array_sum(array_column($geoData, 'total_ca')),
                'total_transactions' => array_sum(array_column($geoData, 'total_transactions')),
                'avg_ca_per_pdv' => count($geoData) > 0 ? array_sum(array_column($geoData, 'total_ca')) / count($geoData) : 0,
                'regions' => array_values(array_unique(array_column($geoData, 'region'))),
            ];

            return response()->json([
                'pdvs' => $geoData,
                'summary' => $summary,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'generated_at' => now()->toDateTimeString(),
            ]);
        });
    }

    /**
     * Identify potential zones: areas with high PDV density but low average CA
     * Helps find underperforming zones that could be improved
     */
    public function getPotentialZones(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $cacheKey = "potential_zones_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            // Group PDV by region and analyze density vs performance
            $regionStats = DB::table('point_of_sales as p')
                ->leftJoin('pdv_transactions as t', function($join) use ($startDate, $endDate) {
                    $join->on('p.numero_flooz', '=', 't.pdv_numero')
                         ->whereBetween('t.transaction_date', [$startDate, $endDate]);
                })
                ->whereNotNull('p.latitude')
                ->whereNotNull('p.longitude')
                ->where('p.status', 'validated')
                ->whereNotNull('p.region')
                ->select('p.region')
                ->selectRaw('
                    COUNT(DISTINCT p.numero_flooz) as pdv_count,
                    AVG(p.latitude) as center_lat,
                    AVG(p.longitude) as center_lng,
                    SUM(t.retrait_keycost) as total_ca,
                    AVG(t.retrait_keycost) as avg_ca_per_transaction,
                    SUM(t.count_depot + t.count_retrait) as total_transactions
                ')
                ->groupBy('p.region')
                ->havingRaw('pdv_count > 5')
                ->get();

            $globalAvgCa = $regionStats->avg('avg_ca_per_transaction');

            $potentialZones = [];
            foreach ($regionStats as $region) {
                // Zone with high density (>5 PDV) but below-average CA = potential
                $caPerPdv = $region->pdv_count > 0 ? $region->total_ca / $region->pdv_count : 0;
                $avgCaRatio = $globalAvgCa > 0 ? $caPerPdv / $globalAvgCa : 0;

                if ($avgCaRatio < 0.7 && $region->pdv_count > 5) {
                    $potentialZones[] = [
                        'region' => $region->region,
                        'pdv_count' => $region->pdv_count,
                        'center_lat' => (float) $region->center_lat,
                        'center_lng' => (float) $region->center_lng,
                        'total_ca' => $region->total_ca ?? 0,
                        'ca_per_pdv' => $caPerPdv,
                        'performance_ratio' => round($avgCaRatio * 100, 1),
                        'potential_score' => round((1 - $avgCaRatio) * $region->pdv_count, 2),
                    ];
                }
            }

            // Sort by potential score (higher = more potential)
            usort($potentialZones, fn($a, $b) => $b['potential_score'] <=> $a['potential_score']);

            return response()->json([
                'potential_zones' => $potentialZones,
                'global_avg_ca' => $globalAvgCa,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'generated_at' => now()->toDateTimeString(),
            ]);
        });
    }
}
