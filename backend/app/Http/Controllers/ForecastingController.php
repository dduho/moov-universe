<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ForecastingController extends Controller
{
    /**
     * Obtenir les prévisions CA pour le mois en cours
     * Admin uniquement
     */
    public function getForecast(Request $request)
    {
        try {
            // Vérifier que l'utilisateur est admin
            if (!$request->user()->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $request->validate([
                'scope' => 'nullable|in:global,region,dealer,pdv',
                'entity_id' => 'nullable|string',
            ]);

            $scope = $request->input('scope', 'global');
            $entityId = $request->input('entity_id');

            // Cache de 1 heure (moins pour global car coûteux)
            $cacheTime = $scope === 'global' ? 7200 : 3600; // 2h pour global, 1h pour le reste
            $cacheKey = "forecast_{$scope}_{$entityId}_" . now()->format('Y-m-d-H');

            return Cache::remember($cacheKey, $cacheTime, function () use ($scope, $entityId) {
                $now = Carbon::now();

                // Pour scope global, limiter les données pour performance
                $forecastData = $this->calculateForecast($scope, $entityId, $now);
                
                // Limiter les requêtes coûteuses pour scope global
                if ($scope === 'global') {
                    return response()->json([
                        'forecast' => $forecastData,
                        'high_potential_pdv' => [], // Désactivé pour global (trop lourd)
                        'underperforming_pdv' => [], // Désactivé pour global (trop lourd)
                        'growth_opportunities' => $this->identifyGrowthOpportunities($now),
                        'generated_at' => $now->toIso8601String(),
                        'note' => 'Analyses détaillées des PDV disponibles uniquement par région/dealer/PDV',
                    ]);
                }

                return response()->json([
                    'forecast' => $forecastData,
                    'high_potential_pdv' => $this->identifyHighPotentialPdv($now),
                    'underperforming_pdv' => $this->identifyUnderperformingPdv($now),
                    'growth_opportunities' => $this->identifyGrowthOpportunities($now),
                    'generated_at' => $now->toIso8601String(),
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Forecasting error: ' . $e->getMessage(), [
                'scope' => $request->input('scope'),
                'entity_id' => $request->input('entity_id'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Une erreur est survenue lors du calcul des prévisions',
                'message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Calculer les prévisions de CA avec régression linéaire
     */
    private function calculateForecast($scope, $entityId, $now)
    {
        try {
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd = $now->copy()->endOfMonth();
            $daysInMonth = $monthEnd->day;
            $daysPassed = $now->day;
            $daysRemaining = $daysInMonth - $daysPassed;

            // Récupérer les données du mois en cours
            $query = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$monthStart, $now]);

            // Appliquer les filtres selon le scope
            if ($scope === 'region' && $entityId) {
                $query->join('point_of_sales as p', 'pdv_transactions.pdv_numero', '=', 'p.numero_flooz')
                      ->where('p.region', $entityId);
            } elseif ($scope === 'dealer' && $entityId) {
                $query->join('point_of_sales as p', 'pdv_transactions.pdv_numero', '=', 'p.numero_flooz')
                      ->where('p.dealer_name', $entityId);
            } elseif ($scope === 'pdv' && $entityId) {
                $query->where('pdv_numero', $entityId);
            }

            // Pour scope global, utiliser uniquement les agrégats sans récupérer chaque PDV
            // Limiter à un échantillon représentatif si trop de données
            if ($scope === 'global') {
                // Requête optimisée : agrégation directe sans détail par PDV
                $query->selectRaw('
                    DATE(transaction_date) as date,
                    SUM(retrait_keycost) as ca,
                    SUM(count_depot + count_retrait) as transactions
                ')
                ->groupBy('date')
                ->orderBy('date');
            } else {
                $query->selectRaw('
                    DATE(transaction_date) as date,
                    SUM(retrait_keycost) as ca,
                    SUM(count_depot + count_retrait) as transactions
                ')
                ->groupBy('date')
                ->orderBy('date');
            }

            // Données journalières du mois
            $dailyData = $query->get();

            if ($dailyData->isEmpty()) {
                return [
                    'method' => 'insufficient_data',
                    'current_ca' => 0,
                    'projected_ca' => 0,
                    'confidence' => 0,
                    'daily_average' => 0,
                    'days_remaining' => $daysRemaining,
                ];
            }

        // Calculer la moyenne mobile et la tendance
        $caData = $dailyData->pluck('ca')->toArray();
        $transactionData = $dailyData->pluck('transactions')->toArray();
        
        $currentCa = array_sum($caData);
        $currentTransactions = array_sum($transactionData);
        $dailyAverage = $currentCa / count($caData);

        // Régression linéaire simple pour détecter la tendance
        $trend = $this->calculateLinearTrend($caData);
        
        // Prédiction basée sur la tendance
        $projectedDailyAverage = $dailyAverage + ($trend * count($caData));
        $projectedCa = $currentCa + ($projectedDailyAverage * $daysRemaining);

        // Calcul de la confiance (basé sur la stabilité)
        $variance = $this->calculateVariance($caData);
        $confidence = $this->calculateConfidence($variance, count($caData));

        // Comparaison avec le mois précédent
        $lastMonth = $this->getLastMonthCa($scope, $entityId, $now);
        $growthRate = $lastMonth > 0 
            ? (($projectedCa - $lastMonth) / $lastMonth) * 100 
            : 0;

        return [
            'method' => 'linear_regression',
            'scope' => $scope,
            'entity_id' => $entityId,
            'period' => [
                'month' => $now->format('F Y'),
                'days_passed' => $daysPassed,
                'days_remaining' => $daysRemaining,
                'days_total' => $daysInMonth,
                'progress_percentage' => round(($daysPassed / $daysInMonth) * 100, 2),
            ],
            'current' => [
                'ca' => round($currentCa, 2),
                'transactions' => $currentTransactions,
                'daily_average' => round($dailyAverage, 2),
            ],
            'projected' => [
                'ca' => round($projectedCa, 2),
                'daily_average_adjusted' => round($projectedDailyAverage, 2),
                'confidence_level' => round($confidence, 2),
                'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
                'trend_value' => round($trend, 2),
            ],
            'comparison' => [
                'last_month_ca' => round($lastMonth, 2),
                'growth_rate' => round($growthRate, 2),
                'growth_status' => $growthRate > 5 ? 'strong' : ($growthRate > 0 ? 'moderate' : 'negative'),
            ],
            'message' => $this->generateForecastMessage(
                $projectedCa, 
                $dailyAverage, 
                $daysRemaining, 
                $confidence,
                $growthRate
            ),
        ];
        } catch (\Exception $e) {
            \Log::error('Calculate forecast error: ' . $e->getMessage(), [
                'scope' => $scope,
                'entity_id' => $entityId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return minimal data on error
            return [
                'method' => 'error',
                'current_ca' => 0,
                'projected_ca' => 0,
                'confidence' => 0,
                'daily_average' => 0,
                'days_remaining' => 0,
                'error' => 'Erreur lors du calcul des prévisions'
            ];
        }
    }

    /**
     * Régression linéaire simple (tendance)
     */
    private function calculateLinearTrend($data)
    {
        $n = count($data);
        if ($n < 2) return 0;

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $data[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return 0;

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        
        return $slope;
    }

    /**
     * Calculer la variance (stabilité des données)
     */
    private function calculateVariance($data)
    {
        $n = count($data);
        if ($n === 0) return 0;

        $mean = array_sum($data) / $n;
        $variance = 0;

        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }

        return $variance / $n;
    }

    /**
     * Calculer le niveau de confiance (0-100)
     */
    private function calculateConfidence($variance, $dataPoints)
    {
        // Plus de points de données = plus de confiance
        $dataConfidence = min(100, ($dataPoints / 30) * 100);
        
        // Moins de variance = plus de confiance
        $varianceConfidence = $variance > 0 
            ? max(0, 100 - (log($variance + 1) * 10)) 
            : 100;

        // Moyenne pondérée
        return ($dataConfidence * 0.4) + ($varianceConfidence * 0.6);
    }

    /**
     * Obtenir le CA du mois précédent
     */
    private function getLastMonthCa($scope, $entityId, $now)
    {
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $query = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd]);

        if ($scope === 'region' && $entityId) {
            $query->join('point_of_sales as p', 'pdv_transactions.pdv_numero', '=', 'p.numero_flooz')
                  ->where('p.region', $entityId);
        } elseif ($scope === 'dealer' && $entityId) {
            $query->join('point_of_sales as p', 'pdv_transactions.pdv_numero', '=', 'p.numero_flooz')
                  ->where('p.dealer_name', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $query->where('pdv_numero', $entityId);
        }

        return $query->sum('retrait_keycost') ?? 0;
    }

    /**
     * Générer un message de prévision
     */
    private function generateForecastMessage($projected, $dailyAvg, $daysRemaining, $confidence, $growthRate)
    {
        $confidenceText = $confidence > 80 ? 'forte confiance' : ($confidence > 60 ? 'confiance modérée' : 'faible confiance');
        $growthText = $growthRate > 5 ? 'croissance forte' : ($growthRate > 0 ? 'croissance modérée' : 'décroissance');

        return sprintf(
            "À ce rythme (%s FCFA/jour), le CA projeté est de %s FCFA d'ici fin de mois (%d jours restants). %s (%s%%) avec %s.",
            number_format($dailyAvg, 0, ',', ' '),
            number_format($projected, 0, ',', ' '),
            $daysRemaining,
            ucfirst($growthText),
            number_format($growthRate, 1),
            $confidenceText
        );
    }

    /**
     * Identifier les PDV à haut potentiel
     */
    private function identifyHighPotentialPdv($now)
    {
        $last30Days = $now->copy()->subDays(30);

        // PDV avec croissance CA >20% sur 30 jours
        $pdvs = DB::table('pdv_transactions as t1')
            ->join('point_of_sales as p', 't1.pdv_numero', '=', 'p.numero_flooz')
            ->select('t1.pdv_numero', 'p.nom_point', 'p.region', 'p.dealer_name')
            ->selectRaw('
                SUM(CASE WHEN t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_recent,
                SUM(CASE WHEN t1.transaction_date < ? THEN t1.retrait_keycost ELSE 0 END) as ca_previous,
                AVG(t1.retrait_keycost) as ca_daily_avg
            ', [$now->copy()->subDays(15), $now->copy()->subDays(15)])
            ->where('t1.transaction_date', '>=', $last30Days)
            ->groupBy('t1.pdv_numero', 'p.nom_point', 'p.region', 'p.dealer_name')
            ->havingRaw('ca_recent > 0 AND ca_previous > 0')
            ->havingRaw('((ca_recent - ca_previous) / ca_previous) > 0.20')
            ->orderByRaw('((ca_recent - ca_previous) / ca_previous) DESC')
            ->limit(10)
            ->get();

        return $pdvs->map(function ($pdv) {
            $growthRate = (($pdv->ca_recent - $pdv->ca_previous) / $pdv->ca_previous) * 100;
            return [
                'pdv_numero' => $pdv->pdv_numero,
                'nom_point' => $pdv->nom_point,
                'region' => $pdv->region,
                'dealer_name' => $pdv->dealer_name,
                'growth_rate' => round($growthRate, 2),
                'ca_recent' => round($pdv->ca_recent, 2),
                'ca_previous' => round($pdv->ca_previous, 2),
                'daily_average' => round($pdv->ca_daily_avg, 2),
                'potential_score' => min(100, round($growthRate + 50, 2)),
            ];
        });
    }

    /**
     * Identifier les PDV sous-performants nécessitant attention
     */
    private function identifyUnderperformingPdv($now)
    {
        $last30Days = $now->copy()->subDays(30);

        // PDV avec chute CA >30% ou CA très faible
        $pdvs = DB::table('pdv_transactions as t1')
            ->join('point_of_sales as p', 't1.pdv_numero', '=', 'p.numero_flooz')
            ->select('t1.pdv_numero', 'p.nom_point', 'p.region', 'p.dealer_name', 'p.created_at')
            ->selectRaw('
                SUM(CASE WHEN t1.transaction_date >= ? THEN t1.retrait_keycost ELSE 0 END) as ca_recent,
                SUM(CASE WHEN t1.transaction_date < ? THEN t1.retrait_keycost ELSE 0 END) as ca_previous,
                AVG(t1.retrait_keycost) as ca_daily_avg,
                COUNT(DISTINCT t1.transaction_date) as active_days
            ', [$now->copy()->subDays(15), $now->copy()->subDays(15)])
            ->where('t1.transaction_date', '>=', $last30Days)
            ->groupBy('t1.pdv_numero', 'p.nom_point', 'p.region', 'p.dealer_name', 'p.created_at')
            ->havingRaw('ca_previous > 0 AND ((ca_recent - ca_previous) / ca_previous) < -0.30')
            ->orderByRaw('((ca_recent - ca_previous) / ca_previous) ASC')
            ->limit(10)
            ->get();

        return $pdvs->map(function ($pdv) use ($now) {
            $declineRate = (($pdv->ca_recent - $pdv->ca_previous) / $pdv->ca_previous) * 100;
            return [
                'pdv_numero' => $pdv->pdv_numero,
                'nom_point' => $pdv->nom_point,
                'region' => $pdv->region,
                'dealer_name' => $pdv->dealer_name,
                'decline_rate' => round($declineRate, 2),
                'ca_recent' => round($pdv->ca_recent, 2),
                'ca_previous' => round($pdv->ca_previous, 2),
                'daily_average' => round($pdv->ca_daily_avg, 2),
                'active_days' => $pdv->active_days,
                'risk_level' => $declineRate < -50 ? 'high' : 'medium',
                'age_days' => Carbon::parse($pdv->created_at)->diffInDays($now),
            ];
        });
    }

    /**
     * Identifier les opportunités de croissance par région/dealer
     */
    private function identifyGrowthOpportunities($now)
    {
        $last30Days = $now->copy()->subDays(30);

        // Pré-calculer le total de PDV par région pour éviter les sous-requêtes
        $pdvCountsByRegion = DB::table('point_of_sales')
            ->select('region', DB::raw('COUNT(*) as total'))
            ->where('status', 'validated')
            ->whereNotNull('region')
            ->groupBy('region')
            ->pluck('total', 'region');

        // Régions avec taux d'activation PDV faible mais potentiel élevé
        $regions = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->select('p.region')
            ->selectRaw('
                COUNT(DISTINCT t.pdv_numero) as pdv_actifs,
                SUM(t.retrait_keycost) as ca_total,
                AVG(t.retrait_keycost) as ca_avg_per_active_pdv
            ')
            ->whereBetween('t.transaction_date', [$last30Days, $now])
            ->whereNotNull('p.region')
            ->groupBy('p.region')
            ->limit(20) // Limiter à 20 régions max
            ->get();

        return $regions->map(function ($region) use ($pdvCountsByRegion) {
            $pdvTotal = $pdvCountsByRegion[$region->region] ?? 1;
            $activationRate = ($region->pdv_actifs / $pdvTotal) * 100;
            $potentialCa = $region->ca_avg_per_active_pdv * $pdvTotal;
            $caGap = $potentialCa - $region->ca_total;

            return [
                'region' => $region->region,
                'pdv_actifs' => $region->pdv_actifs,
                'pdv_total' => $pdvTotal,
                'activation_rate' => round($activationRate, 2),
                'ca_total' => round($region->ca_total, 2),
                'ca_potential' => round($potentialCa, 2),
                'ca_gap' => round($caGap, 2),
                'opportunity_score' => round((100 - $activationRate) + ($caGap / 10000), 2),
                'recommendation' => $activationRate < 50 
                    ? 'Activer les PDV dormants' 
                    : 'Améliorer performance des PDV actifs',
            ];
        })->sortByDesc('opportunity_score')->values()->take(5);
    }
}
