<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FraudDetectionController extends Controller
{
    /**
     * Detect fraud patterns and return flagged transactions/PDVs
     */
    public function detect(Request $request)
    {
        $scope = $request->input('scope', 'global'); // global, dealer, pdv
        $entityId = $request->input('entity_id');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $cacheKey = "fraud_detection_{$scope}_{$entityId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 1800, function () use ($scope, $entityId, $startDate, $endDate) {
            $alerts = [];

            // 1. Split deposit fraud - Main fraud pattern (high depot count vs retrait)
            $alerts = array_merge($alerts, $this->detectSplitDepositFraud($scope, $entityId, $startDate, $endDate));

            // 2. Off-hours large transactions (>500k FCFA outside 8am-8pm)
            $alerts = array_merge($alerts, $this->detectOffHoursLargeTransactions($scope, $entityId, $startDate, $endDate));

            // 3. Sudden activity spikes (>3x average daily volume)
            $alerts = array_merge($alerts, $this->detectActivitySpikes($scope, $entityId, $startDate, $endDate));

            // 4. PDV earning more commission than generating CA (commission fraud)
            $alerts = array_merge($alerts, $this->detectCommissionOverCa($scope, $entityId, $startDate, $endDate));

            // Calculate risk scores
            foreach ($alerts as &$alert) {
                $alert['risk_score'] = $this->calculateRiskScore($alert);
                $alert['risk_level'] = $this->getRiskLevel($alert['risk_score']);
            }

            // Sort by risk score descending
            usort($alerts, function ($a, $b) {
                return $b['risk_score'] <=> $a['risk_score'];
            });

            $summary = [
                'total_alerts' => count($alerts),
                'high_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'high')),
                'medium_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'medium')),
                'low_risk' => count(array_filter($alerts, fn($a) => $a['risk_level'] === 'low')),
                'total_flagged_amount' => array_sum(array_column($alerts, 'flagged_amount')),
            ];

            return response()->json([
                'summary' => $summary,
                'alerts' => $alerts, // Return all alerts so filters stay consistent with summary counts
                'generated_at' => now()->toDateTimeString(),
            ]);
        });
    }

    /**
     * Detect split deposit fraud - PDV multiplying deposits to gain more commissions
     * Key indicators: High depot count relative to retrait count
     */
    private function detectSplitDepositFraud($scope, $entityId, $startDate, $endDate)
    {
        $alerts = [];

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        $pdvs = $pdvQuery->with('organization')->get();

        // Get top 20 performers to establish benchmark
        $topPerformers = [];
        foreach ($pdvs as $pdv) {
            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(count_depot) as total_depot,
                    SUM(count_retrait) as total_retrait,
                    SUM(sum_depot) as total_depot_amount,
                    COUNT(DISTINCT transaction_date) as active_days
                ')
                ->first();

            if ($stats && ($stats->total_depot + $stats->total_retrait) >= 50) {
                $ratio = $stats->total_depot / max($stats->total_retrait, 1);
                $topPerformers[] = [
                    'pdv' => $pdv,
                    'stats' => $stats,
                    'ratio' => $ratio,
                    'total_transactions' => $stats->total_depot + $stats->total_retrait
                ];
            }
        }

        // Sort by total transactions and take top 20
        usort($topPerformers, function($a, $b) {
            return $b['total_transactions'] <=> $a['total_transactions'];
        });
        $topPerformers = array_slice($topPerformers, 0, 20);

        if (count($topPerformers) < 10) {
            return $alerts; // Not enough data for comparison
        }

        // Calculate benchmark ratio from top performers
        $benchmarkRatio = array_sum(array_column($topPerformers, 'ratio')) / count($topPerformers);

        // Now check all PDVs against this benchmark
        foreach ($pdvs as $pdv) {
            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(count_depot) as total_depot,
                    SUM(count_retrait) as total_retrait,
                    SUM(sum_depot) as total_depot_amount,
                    AVG(sum_depot / NULLIF(count_depot, 0)) as avg_depot_amount,
                    COUNT(DISTINCT transaction_date) as active_days
                ')
                ->first();

            if ($stats && ($stats->total_depot + $stats->total_retrait) >= 50) {
                $ratio = $stats->total_depot / max($stats->total_retrait, 1);
                
                // Flag if depot count is abnormally high compared to benchmark
                // Ratio > 3x benchmark = suspicious
                if ($ratio > ($benchmarkRatio * 3) && $stats->total_depot > 100) {
                    $avgDepotAmount = $stats->avg_depot_amount ?? 0;
                    $severity = 'high';
                    
                    // Extra suspicious if average deposit amount is low (splits)
                    if ($avgDepotAmount > 0 && $avgDepotAmount < 5000) {
                        $severity = 'critical';
                    }

                    $alerts[] = [
                        'type' => 'split_deposit_fraud',
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_pdv,
                        'pdv_numero' => $pdv->numero_flooz,
                        'dealer_name' => $pdv->organization->name ?? 'Unknown',
                        'region' => $pdv->region,
                        'date' => $endDate,
                        'flagged_amount' => 0,
                        'description' => sprintf(
                            "Le PDV a effectué %d dépôts contre %d retraits sur la période du %s au %s (ratio: %.1f vs benchmark top 20 PDV: %.1f). Montant moyen par dépôt: %s FCFA. Suspicion de split deposits pour multiplier les commissions.",
                            $stats->total_depot,
                            $stats->total_retrait,
                            Carbon::parse($startDate)->format('d/m/Y'),
                            Carbon::parse($endDate)->format('d/m/Y'),
                            $ratio,
                            $benchmarkRatio,
                            $avgDepotAmount > 0 ? number_format($avgDepotAmount, 0) : 'N/A'
                        ),
                        'severity_factors' => [
                            'depot_count' => $stats->total_depot,
                            'retrait_count' => $stats->total_retrait,
                            'ratio' => $ratio,
                            'benchmark_ratio' => $benchmarkRatio,
                            'ratio_multiplier' => round($ratio / max($benchmarkRatio, 0.1), 2),
                            'avg_depot_amount' => $avgDepotAmount,
                            'is_small_splits' => $avgDepotAmount > 0 && $avgDepotAmount < 5000,
                            'severity' => $severity,
                        ],
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Detect off-hours large transactions
     */
    private function detectOffHoursLargeTransactions($scope, $entityId, $startDate, $endDate)
    {
        $alerts = [];
        $threshold = 500000; // 500k FCFA

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        $pdvNumeros = $pdvQuery->pluck('numero_flooz', 'id');

        // Since pdv_transactions is daily aggregated data, we can't detect exact hours
        // Instead, we'll look for unusual weekend/off-day high volume
        foreach ($pdvNumeros as $pdvId => $numeroFlooz) {
            $suspiciousPatterns = DB::table('pdv_transactions')
                ->where('pdv_numero', $numeroFlooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->whereRaw('DAYOFWEEK(transaction_date) IN (1, 7)') // Sunday=1, Saturday=7
                ->where(function ($q) use ($threshold) {
                    $q->where('sum_retrait', '>', $threshold)
                      ->orWhere('sum_depot', '>', $threshold);
                })
                ->get();

            foreach ($suspiciousPatterns as $pattern) {
                $pdv = PointOfSale::find($pdvId);
                $alerts[] = [
                    'type' => 'off_hours_large_transaction',
                    'pdv_id' => $pdvId,
                    'pdv_name' => $pdv->nom_pdv ?? 'Unknown',
                    'pdv_numero' => $numeroFlooz,
                    'dealer_name' => $pdv->organization->name ?? 'Unknown',
                    'region' => $pdv->region ?? 'Unknown',
                    'date' => $pattern->transaction_date,
                    'flagged_amount' => max($pattern->sum_retrait, $pattern->sum_depot),
                    'description' => "Transactions importantes le weekend: " . number_format(max($pattern->sum_retrait, $pattern->sum_depot)) . " FCFA",
                    'severity_factors' => [
                        'weekend_high_volume' => true,
                        'amount_exceeds_threshold' => true,
                    ],
                ];
            }
        }

        return $alerts;
    }

    /**
     * Detect sudden activity spikes
     */
    private function detectActivitySpikes($scope, $entityId, $startDate, $endDate)
    {
        $alerts = [];

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        $pdvs = $pdvQuery->with('organization')->get();

        foreach ($pdvs as $pdv) {
            // Get daily transaction volumes
            $dailyVolumes = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    transaction_date,
                    (count_depot + count_retrait) as total_transactions,
                    (sum_depot + sum_retrait) as total_amount
                ')
                ->get();

            if ($dailyVolumes->count() > 7) {
                $avgVolume = $dailyVolumes->avg('total_transactions');
                $avgAmount = $dailyVolumes->avg('total_amount');

                foreach ($dailyVolumes as $day) {
                    if ($day->total_transactions > ($avgVolume * 3) && $avgVolume > 5) {
                        $alerts[] = [
                            'type' => 'activity_spike',
                            'pdv_id' => $pdv->id,
                            'pdv_name' => $pdv->nom_pdv,
                            'pdv_numero' => $pdv->numero_flooz,
                            'dealer_name' => $pdv->organization->name ?? 'Unknown',
                            'region' => $pdv->region,
                            'date' => $day->transaction_date,
                            'flagged_amount' => $day->total_amount,
                            // Note: moyenne calculée uniquement sur ce PDV pour la période analysée
                            'description' => "Pic d'activité anormal: " . $day->total_transactions . " transactions (moyenne de ce PDV: " . round($avgVolume) . ")",
                            'severity_factors' => [
                                'spike_multiplier' => round($day->total_transactions / max($avgVolume, 1), 2),
                                'amount' => $day->total_amount,
                            ],
                        ];
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * Detect PDV earning more in commissions than generating CA
     * This indicates excessive deposits (free for customer) vs retraits (generate CA)
     */
    private function detectCommissionOverCa($scope, $entityId, $startDate, $endDate)
    {
        $alerts = [];

        $pdvQuery = PointOfSale::where('status', 'validated');
        if ($scope === 'dealer' && $entityId) {
            $pdvQuery->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $pdvQuery->where('id', $entityId);
        }
        $pdvs = $pdvQuery->with('organization')->get();

        foreach ($pdvs as $pdv) {
            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdv->numero_flooz)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(count_depot) as total_depot,
                    SUM(retrait_keycost) as total_ca,
                    SUM(sum_depot) as total_depot_amount
                ')
                ->first();

            if ($stats && $stats->total_ca > 0) {
                // Cast to numeric to avoid null/strings edge cases
                $totalDepot = (int) ($stats->total_depot ?? 0);
                $totalCa = (float) ($stats->total_ca ?? 0);

                // Ignore very low volumes (ex: 4 dépôts / 91 FCFA) qui ne sont pas suspects
                if ($totalDepot <= 25 || $totalCa < 1000) {
                    continue;
                }

                // Estimate commission: ~100 FCFA per depot transaction (adjust based on actual commission structure)
                $estimatedCommission = $totalDepot * 100;
                $commissionToCaRatio = $estimatedCommission / $totalCa;

                // Flag if estimated commission >= 100% of CA (ratio >= 2, meaning commission equals or exceeds 100% of CA)
                if ($commissionToCaRatio >= 2) {
                    $alerts[] = [
                        'type' => 'commission_over_ca',
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_pdv,
                        'pdv_numero' => $pdv->numero_flooz,
                        'dealer_name' => $pdv->organization->name ?? 'Unknown',
                        'region' => $pdv->region,
                        'date' => $endDate,
                        'flagged_amount' => $estimatedCommission - $stats->total_ca,
                        'description' => sprintf(
                            "Le PDV a effectué %d dépôts (commission estimée: %s FCFA) contre un CA de %s FCFA sur la période du %s au %s. Les commissions dépassent le CA de %.0f%%. Suspicion de fraude aux commissions.",
                            $totalDepot,
                            number_format($estimatedCommission, 0),
                            number_format($totalCa, 0),
                            Carbon::parse($startDate)->format('d/m/Y'),
                            Carbon::parse($endDate)->format('d/m/Y'),
                            ($commissionToCaRatio - 1) * 100
                        ),
                        'severity_factors' => [
                            'depot_count' => $totalDepot,
                            'estimated_commission' => $estimatedCommission,
                            'total_ca' => $totalCa,
                            'commission_to_ca_ratio' => round($commissionToCaRatio, 2),
                            'excess_percent' => round(($commissionToCaRatio - 1) * 100, 1),
                        ]
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * Calculate risk score (0-100)
     */
    private function calculateRiskScore($alert)
    {
        $score = 0;

        switch ($alert['type']) {
            case 'split_deposit_fraud':
                // This is THE main fraud pattern
                $ratioMultiplier = $alert['severity_factors']['ratio_multiplier'] ?? 1;
                $isSmallSplits = $alert['severity_factors']['is_small_splits'] ?? false;
                
                $score = 70; // Base high score
                // Pondération objective sur le ratio dépôts/retraits
                // Seuil à 3x benchmark, puis progression linéaire jusqu'à +40
                $bonus = max(0, min(40, (($ratioMultiplier - 3) / 2) * 10));
                $score += $bonus;
                
                if ($isSmallSplits) {
                    $score += 10; // Extra suspicious if small amounts
                }
                break;
            case 'commission_over_ca':
                // Low priority: cas à surveiller sans alerter fort
                $score = 25; // Base faible
                if (isset($alert['severity_factors']['commission_to_ca_ratio'])) {
                    $ratio = $alert['severity_factors']['commission_to_ca_ratio'];
                    // Bonus modéré, plafonné pour rester en low/medium
                    $score += min(12, ($ratio - 1) * 6);
                }
                break;
            case 'off_hours_large_transaction':
                $score = 50; // Medium risk
                if ($alert['flagged_amount'] > 1000000) $score += 15;
                break;

            case 'activity_spike':
                $multiplier = $alert['severity_factors']['spike_multiplier'] ?? 1;
                $score = min(35 + ($multiplier * 8), 70);
                break;
        }

        return round(min(max($score, 0), 100));
    }

    /**
     * Get risk level based on score
     */
    private function getRiskLevel($score)
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }
}
