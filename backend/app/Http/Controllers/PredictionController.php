<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PredictionController extends Controller
{
    /**
     * Générer des prédictions de performance pour les PDVs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generatePredictions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'forecast_days' => 'integer|min:1|max:365',
                'model_type' => 'in:linear,seasonal,advanced',
                'pdv_ids' => 'array',
                'pdv_ids.*' => 'integer|exists:point_of_sales,id',
                'region' => 'string|max:255',
                'confidence_level' => 'numeric|min:0.5|max:0.99'
            ]);

            $forecastDays = $request->get('forecast_days', 30);
            $modelType = $request->get('model_type', 'seasonal');
            $pdvIds = $request->get('pdv_ids');
            $region = $request->get('region');
            $confidenceLevel = $request->get('confidence_level', 0.85);

            $cacheKey = "predictions_" . md5(json_encode($request->all()));
            
            $result = Cache::remember($cacheKey, 1800, function () use ($forecastDays, $modelType, $pdvIds, $region, $confidenceLevel) {
                
                // 1. Récupérer les données historiques
                $historicalData = $this->getHistoricalData($pdvIds, $region);
                
                if (empty($historicalData)) {
                    return [
                        'forecasts' => [],
                        'insights' => ['Données insuffisantes pour générer des prédictions'],
                        'accuracy' => 0
                    ];
                }

                // 2. Appliquer l'algorithme de prédiction selon le type
                switch ($modelType) {
                    case 'linear':
                        $predictions = $this->linearRegression($historicalData, $forecastDays);
                        break;
                    case 'seasonal':
                        $predictions = $this->seasonalDecomposition($historicalData, $forecastDays);
                        break;
                    case 'advanced':
                        $predictions = $this->advancedForecasting($historicalData, $forecastDays);
                        break;
                    default:
                        $predictions = $this->seasonalDecomposition($historicalData, $forecastDays);
                }

                // 3. Calculer les intervalles de confiance
                $confidenceIntervals = $this->calculateConfidenceIntervals($predictions, $confidenceLevel);
                
                // 4. Générer des insights automatiques
                $insights = $this->generateInsights($historicalData, $predictions);
                
                // 5. Évaluer la précision du modèle
                $accuracy = $this->evaluateModelAccuracy($historicalData, $modelType);

                return [
                    'forecasts' => $predictions,
                    'confidence_intervals' => $confidenceIntervals,
                    'insights' => $insights,
                    'accuracy' => $accuracy,
                    'model_type' => $modelType
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'metadata' => [
                    'forecast_period' => $forecastDays,
                    'model_type' => $modelType,
                    'confidence_level' => $confidenceLevel,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des prédictions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des prédictions'
            ], 500);
        }
    }

    /**
     * Analyser les tendances saisonnières et cycliques
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzeTrends(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'period' => 'in:weekly,monthly,quarterly,yearly',
                'metrics' => 'array',
                'metrics.*' => 'in:roi,ca,revenue,margin_rate,commission_total',
                'start_date' => 'date',
                'end_date' => 'date|after:start_date',
                'group_by' => 'in:pdv,region,agent,organization'
            ]);

            $period = $request->get('period', 'monthly');
            $metrics = $request->get('metrics', ['roi', 'ca', 'revenue']);
            $startDate = $request->get('start_date', now()->subMonths(12)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            $groupBy = $request->get('group_by', 'pdv');

            $cacheKey = "trends_" . md5($period . implode(',', $metrics) . $startDate . $endDate . $groupBy);
            
            $result = Cache::remember($cacheKey, 1800, function () use ($period, $metrics, $startDate, $endDate, $groupBy) {
                
                // 1. Récupérer et agréger les données par période
                $trendData = $this->aggregateDataByPeriod($period, $metrics, $startDate, $endDate, $groupBy);
                
                // Si aucune donnée, retourner un résultat vide
                if (empty($trendData)) {
                    return [
                        'data' => [],
                        'analysis' => [],
                        'cyclical_patterns' => [],
                        'insights' => [['type' => 'info', 'metric' => 'all', 'message' => 'Aucune donnée disponible pour la période sélectionnée']]
                    ];
                }
                
                // 2. Détecter les tendances et saisonnalités
                $trendAnalysis = [];
                foreach ($metrics as $metric) {
                    $trendAnalysis[$metric] = [
                        'trend_direction' => $this->calculateTrendDirection($trendData, $metric),
                        'seasonality' => $this->detectSeasonality($trendData, $metric, $period),
                        'volatility' => $this->calculateVolatility($trendData, $metric), // Utilise la méthode existante
                        'growth_rate' => $this->calculateGrowthRate($trendData, $metric),
                        'anomalies' => $this->detectAnomalies($trendData, $metric)
                    ];
                }

                // 3. Identifier les patterns cycliques
                $cyclicalPatterns = $this->identifyCyclicalPatterns($trendData, $metrics);
                
                // 4. Générer des insights automatiques
                $insights = $this->generateTrendInsights($trendAnalysis, $cyclicalPatterns);

                return [
                    'data' => $trendData,
                    'analysis' => $trendAnalysis,
                    'cyclical_patterns' => $cyclicalPatterns,
                    'insights' => $insights
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'insights' => $result['insights']
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'analyse des tendances: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse des tendances'
            ], 500);
        }
    }

    /**
     * Générer des alertes prédictives
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generatePredictiveAlerts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'threshold_roi' => 'numeric|min:0',
                'threshold_decline' => 'numeric|min:0|max:100',
                'forecast_days' => 'integer|min:7|max:90',
                'alert_types' => 'array',
                'alert_types.*' => 'in:performance_decline,low_roi,seasonal_anomaly,revenue_drop',
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100'
            ]);

            $thresholdRoi = $request->get('threshold_roi', 50);
            $thresholdDecline = $request->get('threshold_decline', 20);
            $forecastDays = $request->get('forecast_days', 14);
            $alertTypes = $request->get('alert_types', ['performance_decline', 'low_roi', 'seasonal_anomaly']);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);

            // 1. Obtenir les prédictions pour la détection d'alertes
            $predictions = $this->getShortTermPredictions($forecastDays);
            
            // 2. Analyser les performances actuelles
            $currentPerformance = $this->getCurrentPerformanceMetrics();
            
            // 3. Générer les alertes selon les types demandés
            $alerts = [];
            $recommendations = [];

            foreach ($alertTypes as $alertType) {
                switch ($alertType) {
                    case 'performance_decline':
                        $declineAlerts = $this->detectPerformanceDecline($predictions, $currentPerformance, $thresholdDecline);
                        $alerts = array_merge($alerts, $declineAlerts);
                        break;
                        
                    case 'low_roi':
                        $roiAlerts = $this->detectLowROI($predictions, $currentPerformance, $thresholdRoi);
                        $alerts = array_merge($alerts, $roiAlerts);
                        break;
                        
                    case 'seasonal_anomaly':
                        $seasonalAlerts = $this->detectSeasonalAnomalies($predictions, $currentPerformance);
                        $alerts = array_merge($alerts, $seasonalAlerts);
                        break;
                        
                    case 'revenue_drop':
                        $revenueAlerts = $this->detectRevenueDrop($predictions, $currentPerformance);
                        $alerts = array_merge($alerts, $revenueAlerts);
                        break;
                }
            }

            // 4. Prioriser les alertes par criticité
            $alerts = $this->prioritizeAlerts($alerts);

            // 5. Pagination
            $totalAlerts = count($alerts);
            $totalPages = (int)ceil($totalAlerts / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedAlerts = array_slice($alerts, $offset, $perPage);

            // 6. Générer des recommandations pour les alertes paginées
            foreach ($paginatedAlerts as $alert) {
                $recommendations = array_merge($recommendations, $this->generateRecommendations($alert));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'alert_count' => $totalAlerts,
                    'critical_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
                    'forecast_period' => $forecastDays,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ],
                'alerts' => $paginatedAlerts,
                'recommendations' => $recommendations
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des alertes prédictives: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des alertes prédictives'
            ], 500);
        }
    }

    /**
     * Algorithme de régression linéaire simple
     */
    private function linearRegression(array $historicalData, int $forecastDays): array
    {
        $predictions = [];
        $n = count($historicalData);
        
        if ($n < 3) return $predictions;

        // Calculer la régression linéaire pour le ROI
        $xSum = array_sum(range(1, $n));
        $ySum = array_sum(array_column($historicalData, 'roi'));
        $xySum = 0;
        $xSquareSum = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $historicalData[$i]['roi'];
            $xySum += $x * $y;
            $xSquareSum += $x * $x;
        }

        $slope = ($n * $xySum - $xSum * $ySum) / ($n * $xSquareSum - $xSum * $xSum);
        $intercept = ($ySum - $slope * $xSum) / $n;

        // Générer les prédictions
        $baseDate = Carbon::parse($historicalData[count($historicalData) - 1]['date']);
        
        for ($i = 1; $i <= $forecastDays; $i++) {
            $predictedRoi = $intercept + $slope * ($n + $i);
            $predictedRoi = max(0, $predictedRoi); // ROI ne peut pas être négatif
            
            $predictions[] = [
                'date' => $baseDate->copy()->addDays($i)->format('Y-m-d'),
                'roi' => round($predictedRoi, 2),
                'ca' => $this->predictCA($historicalData, $i, $predictedRoi),
                'revenue' => $this->predictRevenue($historicalData, $i, $predictedRoi)
            ];
        }

        return $predictions;
    }

    /**
     * Algorithme de décomposition saisonnière
     */
    private function seasonalDecomposition(array $historicalData, int $forecastDays): array
    {
        $predictions = [];
        $n = count($historicalData);
        
        if ($n < 14) {
            // Pas assez de données pour la saisonnalité, utiliser la régression linéaire
            return $this->linearRegression($historicalData, $forecastDays);
        }

        // Extraire la tendance (moyenne mobile)
        $trendData = $this->extractTrend($historicalData);
        
        // Extraire la saisonnalité (pattern hebdomadaire)
        $seasonalPattern = $this->extractSeasonalPattern($historicalData);
        
        // Calculer la tendance future
        $lastTrend = end($trendData);
        $trendGrowth = $this->calculateTrendGrowth($trendData);
        
        $baseDate = Carbon::parse($historicalData[count($historicalData) - 1]['date']);
        
        for ($i = 1; $i <= $forecastDays; $i++) {
            $futureDate = $baseDate->copy()->addDays($i);
            $dayOfWeek = $futureDate->dayOfWeek;
            
            // Prédiction = Tendance + Saisonnalité
            $trendComponent = $lastTrend + ($trendGrowth * $i);
            $seasonalComponent = $seasonalPattern[$dayOfWeek] ?? 0;
            
            $predictedRoi = max(0, $trendComponent + $seasonalComponent);
            
            $predictions[] = [
                'date' => $futureDate->format('Y-m-d'),
                'roi' => round($predictedRoi, 2),
                'ca' => $this->predictCA($historicalData, $i, $predictedRoi),
                'revenue' => $this->predictRevenue($historicalData, $i, $predictedRoi),
                'trend_component' => round($trendComponent, 2),
                'seasonal_component' => round($seasonalComponent, 2)
            ];
        }

        return $predictions;
    }

    /**
     * Algorithme de prédiction avancé (combinaison de méthodes)
     */
    private function advancedForecasting(array $historicalData, int $forecastDays): array
    {
        // Combiner plusieurs méthodes pour plus de précision
        $linearPredictions = $this->linearRegression($historicalData, $forecastDays);
        $seasonalPredictions = $this->seasonalDecomposition($historicalData, $forecastDays);
        
        // Pondération: 30% linéaire + 70% saisonnier
        $predictions = [];
        
        for ($i = 0; $i < count($linearPredictions); $i++) {
            $predictions[] = [
                'date' => $linearPredictions[$i]['date'],
                'roi' => round(
                    ($linearPredictions[$i]['roi'] * 0.3) + 
                    ($seasonalPredictions[$i]['roi'] * 0.7), 
                    2
                ),
                'ca' => round(
                    ($linearPredictions[$i]['ca'] * 0.3) + 
                    ($seasonalPredictions[$i]['ca'] * 0.7), 
                    2
                ),
                'revenue' => round(
                    ($linearPredictions[$i]['revenue'] * 0.3) + 
                    ($seasonalPredictions[$i]['revenue'] * 0.7), 
                    2
                )
            ];
        }

        return $predictions;
    }

    /**
     * Récupérer les données historiques pour la prédiction
     */
    private function getHistoricalData(?array $pdvIds, ?string $region): array
    {
        $query = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->select([
                DB::raw('DATE(t.transaction_date) as date'),
                // ROI = (Revenue / CA) * 100
                DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as roi'),
                DB::raw('SUM(t.retrait_keycost) as total_ca'),
                DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as total_revenue'),
                DB::raw('SUM(t.count_depot + t.count_retrait) as transaction_count')
            ])
            ->where('t.transaction_date', '>=', now()->subDays(90))
            ->where('t.transaction_date', '<=', now());

        if ($pdvIds) {
            $query->whereIn('p.id', $pdvIds);
        }

        if ($region) {
            $query->where('p.region', $region);
        }

        $data = $query->groupBy(DB::raw('DATE(t.transaction_date)'))
                     ->orderBy('date')
                     ->get()
                     ->toArray();

        return array_map(function($item) {
            return [
                'date' => $item->date,
                'roi' => (float) $item->roi,
                'ca' => (float) $item->total_ca,
                'revenue' => (float) $item->total_revenue,
                'transaction_count' => (int) $item->transaction_count
            ];
        }, $data);
    }

    /**
     * Agréger les données par période pour l'analyse des tendances
     */
    private function aggregateDataByPeriod(string $period, array $metrics, string $startDate, string $endDate, string $groupBy): array
    {
        $query = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->where('t.transaction_date', '>=', $startDate)
            ->where('t.transaction_date', '<=', $endDate);

        // Sélectionner le champ de période selon le type
        $periodField = match($period) {
            'weekly' => DB::raw('YEARWEEK(t.transaction_date) as period'),
            'monthly' => DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as period'),
            'quarterly' => DB::raw('CONCAT(YEAR(t.transaction_date), "-Q", QUARTER(t.transaction_date)) as period'),
            'yearly' => DB::raw('YEAR(t.transaction_date) as period'),
            default => DB::raw('DATE_FORMAT(t.transaction_date, "%Y-%m") as period')
        };

        $selectFields = [$periodField];

        // Ajouter les champs de groupement
        if ($groupBy === 'region') {
            $selectFields[] = 'p.region as group_key';
        } elseif ($groupBy === 'pdv') {
            $selectFields[] = 'p.id as group_key';
            $selectFields[] = 'p.nom_point as group_name';
        }

        // Ajouter les métriques demandées
        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'roi':
                    $selectFields[] = DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as roi');
                    break;
                case 'ca':
                    $selectFields[] = DB::raw('SUM(t.retrait_keycost) as ca');
                    break;
                case 'revenue':
                    $selectFields[] = DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as revenue');
                    break;
                case 'margin_rate':
                    // Margin rate can be calculated as a percentage of PDV commissions over CA
                    $selectFields[] = DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.pdv_depot_commission + t.pdv_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as margin_rate');
                    break;
                case 'commission_total':
                    $selectFields[] = DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission + t.pdv_depot_commission + t.pdv_retrait_commission) as commission_total');
                    break;
            }
        }

        $query->select($selectFields);

        // Grouper par période et champ de groupement
        $groupByFields = ['period'];
        if ($groupBy === 'region') {
            $groupByFields[] = 'p.region';
        } elseif ($groupBy === 'pdv') {
            $groupByFields[] = 'p.id';
            $groupByFields[] = 'p.nom_point';
        }

        $data = $query->groupBy($groupByFields)
                     ->orderBy('period')
                     ->get()
                     ->toArray();

        return array_map(function($item) {
            return (array) $item;
        }, $data);
    }

    /**
     * Calculer les intervalles de confiance
     */
    private function calculateConfidenceIntervals(array $predictions, float $confidenceLevel): array
    {
        $intervals = [];
        $zScore = $this->getZScore($confidenceLevel);
        
        foreach ($predictions as $prediction) {
            // Estimation simple de l'écart-type basée sur la variabilité des données
            $standardError = $prediction['roi'] * 0.1; // 10% d'erreur standard estimée
            $margin = $zScore * $standardError;
            
            $intervals[] = [
                'date' => $prediction['date'],
                'lower' => max(0, $prediction['roi'] - $margin),
                'upper' => $prediction['roi'] + $margin
            ];
        }
        
        return $intervals;
    }

    /**
     * Obtenir le Z-score pour un niveau de confiance donné
     */
    private function getZScore(float $confidenceLevel): float
    {
        $zScores = [
            0.90 => 1.645,
            0.95 => 1.96,
            0.99 => 2.576,
            0.85 => 1.44
        ];
        
        return $zScores[$confidenceLevel] ?? 1.96;
    }

    /**
     * Générer des insights automatiques
     */
    private function generateInsights(array $historicalData, array $predictions): array
    {
        $insights = [];
        
        if (empty($historicalData) || empty($predictions)) {
            return ['Données insuffisantes pour générer des insights'];
        }

        // Analyser la tendance générale
        $currentROI = end($historicalData)['roi'];
        $futureROI = end($predictions)['roi'];
        $roiChange = (($futureROI - $currentROI) / $currentROI) * 100;

        if ($roiChange > 10) {
            $insights[] = "Tendance très positive: Le ROI devrait augmenter de " . round($roiChange, 1) . "% sur la période prédite.";
        } elseif ($roiChange > 5) {
            $insights[] = "Tendance positive: Le ROI devrait augmenter de " . round($roiChange, 1) . "% sur la période prédite.";
        } elseif ($roiChange < -10) {
            $insights[] = "Alerte: Le ROI pourrait chuter de " . round(abs($roiChange), 1) . "% sur la période prédite.";
        } elseif ($roiChange < -5) {
            $insights[] = "Vigilance: Le ROI pourrait diminuer de " . round(abs($roiChange), 1) . "% sur la période prédite.";
        } else {
            $insights[] = "Stabilité: Le ROI devrait rester relativement stable sur la période prédite.";
        }

        // Analyser la volatilité
        $roiValues = array_column($historicalData, 'roi');
        $volatility = $this->calculateVolatility($roiValues);
        
        if ($volatility > 20) {
            $insights[] = "Forte volatilité détectée dans les performances (écart-type: " . round($volatility, 1) . "%).";
        }

        return $insights;
    }

    /**
     * Calculer la volatilité (écart-type)
     */
    private function calculateVolatility(array $data, ?string $metric = null): float
    {
        if ($metric) {
            $values = array_column($data, $metric);
        } else {
            $values = $data;
        }
        
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        $variance = array_sum($squaredDiffs) / count($values);
        return sqrt($variance);
    }

    /**
     * Évaluer la précision du modèle
     */
    private function evaluateModelAccuracy(array $historicalData, string $modelType): float
    {
        // Simulation de précision basée sur la quantité et qualité des données
        $dataQuality = count($historicalData) / 90; // Score sur 90 jours max
        $modelAccuracy = [
            'linear' => 0.75,
            'seasonal' => 0.85,
            'advanced' => 0.92
        ];
        
        return min(0.95, $modelAccuracy[$modelType] * $dataQuality);
    }

    // Méthodes auxiliaires pour les prédictions CA et Revenue
    private function predictCA(array $historicalData, int $dayOffset, float $predictedRoi): float
    {
        $avgCA = array_sum(array_column($historicalData, 'ca')) / count($historicalData);
        $roiFactor = $predictedRoi / 100;
        return round($avgCA * (1 + $roiFactor * 0.1), 2);
    }

    private function predictRevenue(array $historicalData, int $dayOffset, float $predictedRoi): float
    {
        $avgRevenue = array_sum(array_column($historicalData, 'revenue')) / count($historicalData);
        $roiFactor = $predictedRoi / 100;
        return round($avgRevenue * (1 + $roiFactor * 0.15), 2);
    }

    // Méthodes pour l'analyse de tendances (à implémenter selon les besoins)
    private function extractTrend(array $data): array
    {
        // Implémentation de la moyenne mobile
        $window = min(7, count($data));
        $trend = [];
        
        for ($i = $window - 1; $i < count($data); $i++) {
            $sum = 0;
            for ($j = $i - $window + 1; $j <= $i; $j++) {
                $sum += $data[$j]['roi'];
            }
            $trend[] = $sum / $window;
        }
        
        return $trend;
    }

    private function extractSeasonalPattern(array $data): array
    {
        $pattern = array_fill(0, 7, 0); // 7 jours de la semaine
        $counts = array_fill(0, 7, 0);
        
        foreach ($data as $point) {
            $dayOfWeek = Carbon::parse($point['date'])->dayOfWeek;
            $pattern[$dayOfWeek] += $point['roi'];
            $counts[$dayOfWeek]++;
        }
        
        // Moyenne par jour de la semaine
        for ($i = 0; $i < 7; $i++) {
            if ($counts[$i] > 0) {
                $pattern[$i] = $pattern[$i] / $counts[$i];
            }
        }
        
        // Normaliser par rapport à la moyenne générale
        $overallMean = array_sum($pattern) / 7;
        for ($i = 0; $i < 7; $i++) {
            $pattern[$i] = $pattern[$i] - $overallMean;
        }
        
        return $pattern;
    }

    private function calculateTrendGrowth(array $trendData): float
    {
        if (count($trendData) < 2) return 0;
        
        $firstValue = $trendData[0];
        $lastValue = end($trendData);
        $periods = count($trendData) - 1;
        
        return ($lastValue - $firstValue) / $periods;
    }

    /**
     * Analyser les corrélations entre métriques
     */
    public function analyzeCorrelations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'metrics' => 'array|min:2',
                'metrics.*' => 'in:roi,ca,revenue,margin_rate,commission_total',
                'timeframe' => 'in:30d,60d,90d',
                'group_by' => 'in:pdv,region,agent'
            ]);

            $metrics = $request->get('metrics', ['roi', 'ca', 'revenue']);
            $timeframe = $request->get('timeframe', '90d');
            $groupBy = $request->get('group_by', 'pdv');

            $days = (int) str_replace('d', '', $timeframe);
            $correlationData = $this->getCorrelationData($metrics, $days, $groupBy);
            $correlationMatrix = $this->calculateCorrelationMatrix($correlationData, $metrics);
            $insights = $this->generateCorrelationInsights($correlationMatrix);

            return response()->json([
                'success' => true,
                'data' => $correlationMatrix,
                'insights' => $insights
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'analyse des corrélations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse des corrélations'
            ], 500);
        }
    }

    /**
     * Générer des recommandations d'optimisation
     */
    public function getOptimizationRecommendations(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'pdv_id' => 'integer|exists:point_of_sales,id',
                'optimization_type' => 'in:roi,revenue,margin',
                'analysis_depth' => 'in:basic,standard,advanced',
                'include_benchmarks' => 'boolean'
            ]);

            $pdvId = $request->get('pdv_id');
            $optimizationType = $request->get('optimization_type', 'roi');
            $analysisDepth = $request->get('analysis_depth', 'standard');
            $includeBenchmarks = $request->get('include_benchmarks', true);

            // Analyser les performances actuelles
            $currentPerformance = $this->analyzeCurrentPerformance($pdvId, $optimizationType);
            
            // Identifier les opportunités d'amélioration
            $opportunities = $this->identifyOptimizationOpportunities($currentPerformance, $optimizationType);
            
            // Générer des recommandations spécifiques
            $recommendations = $this->generateSpecificRecommendations($opportunities, $analysisDepth);
            
            // Ajouter des benchmarks si demandé
            $benchmarks = $includeBenchmarks ? $this->getBenchmarkData($pdvId, $optimizationType) : [];

            return response()->json([
                'success' => true,
                'data' => $currentPerformance,
                'recommendations' => $recommendations,
                'benchmarks' => $benchmarks
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des recommandations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des recommandations'
            ], 500);
        }
    }

    /**
     * Simulation de scénarios "What-if"
     */
    public function runScenarioSimulation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'changes' => 'required|array',
                'forecast_days' => 'integer|min:7|max:365',
                'confidence_interval' => 'numeric|min:0.8|max:0.99',
                'simulation_type' => 'in:optimistic,realistic,conservative'
            ]);

            $changes = $request->get('changes');
            $forecastDays = $request->get('forecast_days', 30);
            $confidenceInterval = $request->get('confidence_interval', 0.95);
            $simulationType = $request->get('simulation_type', 'realistic');

            // Obtenir les données de base
            $baselineData = $this->getBaselineData($forecastDays);
            
            // Appliquer les changements simulés
            $simulatedData = $this->applyScenarioChanges($baselineData, $changes, $simulationType);
            
            // Calculer l'impact comparatif
            $comparison = $this->compareScenarios($baselineData, $simulatedData);
            
            // Générer des insights sur le scénario
            $insights = $this->generateScenarioInsights($comparison, $changes);

            return response()->json([
                'success' => true,
                'data' => $simulatedData,
                'comparison' => $comparison,
                'insights' => $insights
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la simulation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la simulation'
            ], 500);
        }
    }

    // Méthodes de support pour les alertes prédictives
    private function getShortTermPredictions(int $forecastDays): array
    {
        // Utiliser la méthode de prédiction existante avec des paramètres adaptés
        $historicalData = $this->getHistoricalData(null, null);
        return $this->seasonalDecomposition($historicalData, $forecastDays);
    }

    private function getCurrentPerformanceMetrics(): array
    {
        $currentData = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->select([
                'p.id as pdv_id',
                'p.nom_point as pdv_name',
                'p.region',
                DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as current_roi'),
                DB::raw('SUM(t.retrait_keycost) as total_ca'),
                DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as total_revenue'),
                DB::raw('SUM(t.count_depot + t.count_retrait) as transaction_count')
            ])
            ->where('t.transaction_date', '>=', now()->subDays(7))
            ->groupBy('p.id', 'p.nom_point', 'p.region')
            ->get()
            ->toArray();

        return array_map(function($item) {
            return [
                'pdv_id' => $item->pdv_id,
                'pdv_name' => $item->pdv_name,
                'region' => $item->region,
                'current_roi' => (float) $item->current_roi,
                'total_ca' => (float) $item->total_ca,
                'total_revenue' => (float) $item->total_revenue,
                'transaction_count' => (int) $item->transaction_count
            ];
        }, $currentData);
    }

    private function detectPerformanceDecline(array $predictions, array $currentPerformance, float $thresholdDecline): array
    {
        $alerts = [];
        
        foreach ($currentPerformance as $pdv) {
            $currentROI = $pdv['current_roi'];
            
            // Trouver les prédictions pour ce PDV (simulation simple)
            $futureROI = $this->estimateFutureROI($predictions, $pdv);
            
            if ($currentROI > 0) {
                $decline = (($currentROI - $futureROI) / $currentROI) * 100;
                
                if ($decline > $thresholdDecline) {
                    $alerts[] = [
                        'id' => 'decline_' . $pdv['pdv_id'] . '_' . time(),
                        'type' => 'performance_decline',
                        'severity' => $decline > 30 ? 'critical' : 'warning',
                        'pdv_id' => $pdv['pdv_id'],
                        'pdv_name' => $pdv['pdv_name'],
                        'title' => "Baisse de performance prévue",
                        'description' => "Le ROI pourrait chuter de " . number_format($decline, 1) . "% au cours des prochains jours",
                        'current_value' => $currentROI,
                        'predicted_value' => $futureROI,
                        'decline_percentage' => $decline,
                        'created_at' => now()->toISOString()
                    ];
                }
            }
        }
        
        return $alerts;
    }

    private function detectLowROI(array $predictions, array $currentPerformance, float $thresholdRoi): array
    {
        $alerts = [];
        
        foreach ($currentPerformance as $pdv) {
            $futureROI = $this->estimateFutureROI($predictions, $pdv);
            
            if ($futureROI < $thresholdRoi) {
                $alerts[] = [
                    'id' => 'low_roi_' . $pdv['pdv_id'] . '_' . time(),
                    'type' => 'low_roi',
                    'severity' => $futureROI < ($thresholdRoi * 0.5) ? 'critical' : 'warning',
                    'pdv_id' => $pdv['pdv_id'],
                    'pdv_name' => $pdv['pdv_name'],
                    'title' => "ROI faible prévu",
                    'description' => "Le ROI pourrait descendre à " . number_format($futureROI, 1) . "%, en dessous du seuil de " . $thresholdRoi . "%",
                    'predicted_value' => $futureROI,
                    'threshold' => $thresholdRoi,
                    'created_at' => now()->toISOString()
                ];
            }
        }
        
        return $alerts;
    }

    private function detectSeasonalAnomalies(array $predictions, array $currentPerformance): array
    {
        $alerts = [];
        
        // Analyser les anomalies saisonnières basées sur les patterns historiques
        $seasonalNorms = $this->calculateSeasonalNorms();
        
        foreach ($currentPerformance as $pdv) {
            $expectedROI = $seasonalNorms[date('W')] ?? 0; // Semaine de l'année
            $currentROI = $pdv['current_roi'];
            
            if ($currentROI > 0 && $expectedROI > 0) {
                $deviation = abs($currentROI - $expectedROI) / $expectedROI * 100;
                
                if ($deviation > 25) { // Déviation de plus de 25%
                    $alerts[] = [
                        'id' => 'seasonal_' . $pdv['pdv_id'] . '_' . time(),
                        'type' => 'seasonal_anomaly',
                        'severity' => $deviation > 50 ? 'critical' : 'warning',
                        'pdv_id' => $pdv['pdv_id'],
                        'pdv_name' => $pdv['pdv_name'],
                        'title' => "Anomalie saisonnière détectée",
                        'description' => "Performance inhabituelle par rapport à la tendance saisonnière normale",
                        'current_value' => $currentROI,
                        'expected_value' => $expectedROI,
                        'deviation_percentage' => $deviation,
                        'created_at' => now()->toISOString()
                    ];
                }
            }
        }
        
        return $alerts;
    }

    private function detectRevenueDrop(array $predictions, array $currentPerformance): array
    {
        $alerts = [];
        
        foreach ($currentPerformance as $pdv) {
            if ($pdv['transaction_count'] < 3) {
                // Très peu de transactions récentes
                $alerts[] = [
                    'id' => 'revenue_drop_' . $pdv['pdv_id'] . '_' . time(),
                    'type' => 'revenue_drop',
                    'severity' => $pdv['transaction_count'] == 0 ? 'critical' : 'warning',
                    'pdv_id' => $pdv['pdv_id'],
                    'pdv_name' => $pdv['pdv_name'],
                    'title' => "Chute d'activité détectée",
                    'description' => "Seulement {$pdv['transaction_count']} transaction(s) cette semaine",
                    'transaction_count' => $pdv['transaction_count'],
                    'created_at' => now()->toISOString()
                ];
            }
        }
        
        return $alerts;
    }

    private function generateRecommendations(array $alert): array
    {
        $recommendations = [];
        
        switch ($alert['type']) {
            case 'performance_decline':
                $recommendations[] = [
                    'id' => 'rec_' . $alert['id'],
                    'title' => 'Actions pour freiner la baisse',
                    'description' => 'Optimiser les processus et revoir la stratégie commerciale',
                    'actions' => [
                        'Analyser les causes de la baisse de performance',
                        'Revoir les tarifs et commissions',
                        'Former l\'équipe sur les meilleures pratiques',
                        'Intensifier le suivi client'
                    ],
                    'difficulty' => 'Moyen',
                    'impact' => [
                        'roi' => '5-15',
                        'revenue' => '10-20'
                    ]
                ];
                break;
                
            case 'low_roi':
                $recommendations[] = [
                    'id' => 'rec_' . $alert['id'],
                    'title' => 'Amélioration du ROI',
                    'description' => 'Optimiser la rentabilité et réduire les coûts',
                    'actions' => [
                        'Négocier de meilleurs taux de commission',
                        'Réduire les coûts opérationnels',
                        'Cibler les transactions à plus forte valeur ajoutée',
                        'Améliorer l\'efficacité des processus'
                    ],
                    'difficulty' => 'Élevé',
                    'impact' => [
                        'roi' => '10-25',
                        'revenue' => '5-15'
                    ]
                ];
                break;
                
            case 'revenue_drop':
                $recommendations[] = [
                    'id' => 'rec_' . $alert['id'],
                    'title' => 'Relancer l\'activité',
                    'description' => 'Stimuler les transactions et fidéliser la clientèle',
                    'actions' => [
                        'Lancer une campagne promotionnelle',
                        'Contacter les clients inactifs',
                        'Améliorer la visibilité du point de vente',
                        'Diversifier l\'offre de services'
                    ],
                    'difficulty' => 'Facile',
                    'impact' => [
                        'roi' => '0-10',
                        'revenue' => '15-30'
                    ]
                ];
                break;
        }
        
        return $recommendations;
    }

    private function prioritizeAlerts(array $alerts): array
    {
        usort($alerts, function($a, $b) {
            $severityOrder = ['critical' => 3, 'warning' => 2, 'info' => 1];
            return ($severityOrder[$b['severity']] ?? 0) - ($severityOrder[$a['severity']] ?? 0);
        });
        
        return $alerts;
    }

    // Méthodes utilitaires
    private function estimateFutureROI(array $predictions, array $pdv): float
    {
        if (empty($predictions)) {
            return $pdv['current_roi'] * 0.9; // Estimation conservative
        }
        
        // Moyenne des prédictions pondérée par la performance actuelle du PDV
        $avgPredictedROI = array_sum(array_column($predictions, 'roi')) / count($predictions);
        $pdvFactor = min(1.2, max(0.8, $pdv['current_roi'] / 50)); // Facteur basé sur le ROI actuel
        
        return $avgPredictedROI * $pdvFactor;
    }

    private function calculateSeasonalNorms(): array
    {
        // Calculer les normes saisonnières par semaine de l'année
        $norms = [];
        
        for ($week = 1; $week <= 52; $week++) {
            // Simulation de normes saisonnières basées sur des patterns typiques
            $baseROI = 45;
            $seasonal = sin(($week / 52) * 2 * M_PI) * 10; // Variation saisonnière
            $norms[$week] = $baseROI + $seasonal;
        }
        
        return $norms;
    }

    // Méthodes pour corrélations (implémentation simplifiée)
    private function getCorrelationData(array $metrics, int $days, string $groupBy): array
    {
        $query = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz');

        $selectFields = ['p.id as pdv_id'];
        
        foreach ($metrics as $metric) {
            switch ($metric) {
                case 'roi':
                    $selectFields[] = DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as roi');
                    break;
                case 'ca':
                    $selectFields[] = DB::raw('SUM(t.retrait_keycost) as ca');
                    break;
                case 'revenue':
                    $selectFields[] = DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as revenue');
                    break;
                case 'margin_rate':
                    $selectFields[] = DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.pdv_depot_commission + t.pdv_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as margin_rate');
                    break;
                case 'commission_total':
                    $selectFields[] = DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission + t.pdv_depot_commission + t.pdv_retrait_commission) as commission_total');
                    break;
            }
        }

        return $query->select($selectFields)
                    ->where('t.transaction_date', '>=', now()->subDays($days))
                    ->groupBy('p.id')
                    ->get()
                    ->toArray();
    }

    private function calculateCorrelationMatrix(array $data, array $metrics): array
    {
        $matrix = [];
        
        foreach ($metrics as $metric1) {
            $matrix[$metric1] = [];
            foreach ($metrics as $metric2) {
                if ($metric1 === $metric2) {
                    $matrix[$metric1][$metric2] = 1.0;
                } else {
                    $correlation = $this->calculatePearsonCorrelation($data, $metric1, $metric2);
                    $matrix[$metric1][$metric2] = $correlation;
                }
            }
        }
        
        return $matrix;
    }

    private function calculatePearsonCorrelation(array $data, string $metric1, string $metric2): float
    {
        $values1 = array_column($data, $metric1);
        $values2 = array_column($data, $metric2);
        
        if (count($values1) < 2 || count($values2) < 2) return 0;
        
        $mean1 = array_sum($values1) / count($values1);
        $mean2 = array_sum($values2) / count($values2);
        
        $numerator = 0;
        $sumSquares1 = 0;
        $sumSquares2 = 0;
        
        for ($i = 0; $i < count($values1); $i++) {
            $diff1 = $values1[$i] - $mean1;
            $diff2 = $values2[$i] - $mean2;
            
            $numerator += $diff1 * $diff2;
            $sumSquares1 += $diff1 * $diff1;
            $sumSquares2 += $diff2 * $diff2;
        }
        
        $denominator = sqrt($sumSquares1 * $sumSquares2);
        
        return $denominator == 0 ? 0 : $numerator / $denominator;
    }

    private function generateCorrelationInsights(array $correlationMatrix): array
    {
        $insights = [];
        
        foreach ($correlationMatrix as $metric1 => $correlations) {
            foreach ($correlations as $metric2 => $correlation) {
                if ($metric1 !== $metric2 && abs($correlation) > 0.7) {
                    $strength = abs($correlation) > 0.9 ? 'très forte' : 'forte';
                    $direction = $correlation > 0 ? 'positive' : 'négative';
                    
                    $insights[] = "Corrélation $strength $direction entre $metric1 et $metric2 (r = " . round($correlation, 3) . ")";
                }
            }
        }
        
        return $insights;
    }

    // Placeholder methods for recommendations and simulation
    private function analyzeCurrentPerformance($pdvId, string $optimizationType): array
    {
        $query = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->select([
                DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as avg_roi'),
                DB::raw('SUM(t.retrait_keycost) as total_ca'),
                DB::raw('SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as total_revenue'),
                DB::raw('COUNT(DISTINCT DATE(t.transaction_date)) as active_days'),
                DB::raw('AVG(t.count_depot + t.count_retrait) as avg_transactions')
            ])
            ->where('t.transaction_date', '>=', now()->subDays(30));

        if ($pdvId) {
            $query->where('p.id', $pdvId);
        }

        $data = $query->first();

        return [
            'avg_roi' => round((float)($data->avg_roi ?? 0), 2),
            'total_ca' => (float)($data->total_ca ?? 0),
            'total_revenue' => (float)($data->total_revenue ?? 0),
            'active_days' => (int)($data->active_days ?? 0),
            'avg_transactions' => round((float)($data->avg_transactions ?? 0), 2)
        ];
    }

    private function identifyOptimizationOpportunities(array $currentPerformance, string $optimizationType): array
    {
        $opportunities = [];

        // Analyser selon le type d'optimisation
        switch ($optimizationType) {
            case 'roi':
                if ($currentPerformance['avg_roi'] < 60) {
                    $opportunities[] = [
                        'type' => 'roi_improvement',
                        'priority' => 'high',
                        'current_value' => $currentPerformance['avg_roi'],
                        'target_value' => 75,
                        'potential_gain' => (75 - $currentPerformance['avg_roi'])
                    ];
                }
                break;

            case 'revenue':
                $avgRevenuePerDay = $currentPerformance['active_days'] > 0 
                    ? $currentPerformance['total_revenue'] / $currentPerformance['active_days']
                    : 0;
                
                if ($avgRevenuePerDay < 100000) {
                    $opportunities[] = [
                        'type' => 'revenue_growth',
                        'priority' => 'medium',
                        'current_value' => $avgRevenuePerDay,
                        'target_value' => 150000,
                        'potential_gain' => 50000
                    ];
                }
                break;

            case 'margin':
                $marginRate = $currentPerformance['total_ca'] > 0
                    ? ($currentPerformance['total_revenue'] / $currentPerformance['total_ca']) * 100
                    : 0;
                
                if ($marginRate < 5) {
                    $opportunities[] = [
                        'type' => 'margin_optimization',
                        'priority' => 'high',
                        'current_value' => $marginRate,
                        'target_value' => 7,
                        'potential_gain' => 2
                    ];
                }
                break;
        }

        return $opportunities;
    }

    private function generateSpecificRecommendations(array $opportunities, string $analysisDepth): array
    {
        $recommendations = [];

        foreach ($opportunities as $opportunity) {
            switch ($opportunity['type']) {
                case 'roi_improvement':
                    $recommendations[] = [
                        'id' => 'rec_' . uniqid(),
                        'title' => 'Améliorer le ROI',
                        'description' => "Votre ROI actuel est de {$opportunity['current_value']}%. Objectif recommandé: {$opportunity['target_value']}%.",
                        'actions' => [
                            'Augmenter les volumes de transactions sur les créneaux rentables',
                            'Négocier de meilleures commissions avec Flooz',
                            'Réduire les frais opérationnels du point de vente',
                            'Former le personnel pour maximiser les ventes'
                        ],
                        'priority' => $opportunity['priority'],
                        'potential_impact' => 'Gain potentiel: +' . round($opportunity['potential_gain'], 1) . '% de ROI',
                        'timeframe' => '30-60 jours',
                        'effort' => 'Moyen'
                    ];
                    break;

                case 'revenue_growth':
                    $recommendations[] = [
                        'id' => 'rec_' . uniqid(),
                        'title' => 'Augmenter les revenus',
                        'description' => "Revenue journalier moyen: " . number_format($opportunity['current_value'], 0, ',', ' ') . " XOF. Objectif: " . number_format($opportunity['target_value'], 0, ',', ' ') . " XOF.",
                        'actions' => [
                            'Étendre les heures d\'ouverture aux périodes de forte demande',
                            'Promouvoir les services Flooz auprès de nouveaux clients',
                            'Diversifier les services offerts (dépôt + retrait)',
                            'Améliorer la visibilité du point de vente'
                        ],
                        'priority' => $opportunity['priority'],
                        'potential_impact' => 'Gain potentiel: +' . number_format($opportunity['potential_gain'], 0, ',', ' ') . ' XOF/jour',
                        'timeframe' => '2-3 mois',
                        'effort' => 'Élevé'
                    ];
                    break;

                case 'margin_optimization':
                    $recommendations[] = [
                        'id' => 'rec_' . uniqid(),
                        'title' => 'Optimiser la marge',
                        'description' => "Marge actuelle: {$opportunity['current_value']}%. Objectif: {$opportunity['target_value']}%.",
                        'actions' => [
                            'Analyser et réduire les coûts fixes',
                            'Optimiser la gestion de la trésorerie',
                            'Négocier les tarifs avec les fournisseurs',
                            'Automatiser les processus pour réduire les erreurs'
                        ],
                        'priority' => $opportunity['priority'],
                        'potential_impact' => 'Gain potentiel: +' . round($opportunity['potential_gain'], 1) . '% de marge',
                        'timeframe' => '1-2 mois',
                        'effort' => 'Faible'
                    ];
                    break;
            }
        }

        // Ajouter des recommandations générales selon la profondeur d'analyse
        if ($analysisDepth === 'advanced' || $analysisDepth === 'standard') {
            $recommendations[] = [
                'id' => 'rec_general_' . uniqid(),
                'title' => 'Analyse de la concurrence',
                'description' => 'Étudiez les performances des PDV similaires dans votre région.',
                'actions' => [
                    'Identifier les PDV les plus performants',
                    'Analyser leurs pratiques et stratégies',
                    'Adapter les meilleures pratiques à votre contexte'
                ],
                'priority' => 'medium',
                'potential_impact' => 'Amélioration globale des performances',
                'timeframe' => 'Continue',
                'effort' => 'Faible'
            ];
        }

        return $recommendations;
    }

    private function getBenchmarkData($pdvId, string $optimizationType): array
    {
        // Obtenir les benchmarks globaux (tous les PDV)
        $globalBenchmark = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->select([
                DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as avg_roi'),
                DB::raw('AVG(t.retrait_keycost) as avg_ca'),
                DB::raw('AVG(t.dealer_depot_commission + t.dealer_retrait_commission) as avg_revenue')
            ])
            ->where('t.transaction_date', '>=', now()->subDays(30))
            ->first();

        // Obtenir les performances du PDV spécifique si fourni
        $pdvPerformance = null;
        if ($pdvId) {
            $pdvPerformance = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
                ->select([
                    DB::raw('AVG(CASE WHEN t.retrait_keycost > 0 THEN ((t.dealer_depot_commission + t.dealer_retrait_commission) / t.retrait_keycost) * 100 ELSE 0 END) as avg_roi'),
                    DB::raw('AVG(t.retrait_keycost) as avg_ca'),
                    DB::raw('AVG(t.dealer_depot_commission + t.dealer_retrait_commission) as avg_revenue')
                ])
                ->where('p.id', $pdvId)
                ->where('t.transaction_date', '>=', now()->subDays(30))
                ->first();
        }

        $benchmarks = [
            'global' => [
                'avg_roi' => round((float)($globalBenchmark->avg_roi ?? 0), 2),
                'avg_ca' => round((float)($globalBenchmark->avg_ca ?? 0), 0),
                'avg_revenue' => round((float)($globalBenchmark->avg_revenue ?? 0), 0)
            ]
        ];

        if ($pdvPerformance) {
            $benchmarks['pdv'] = [
                'avg_roi' => round((float)($pdvPerformance->avg_roi ?? 0), 2),
                'avg_ca' => round((float)($pdvPerformance->avg_ca ?? 0), 0),
                'avg_revenue' => round((float)($pdvPerformance->avg_revenue ?? 0), 0)
            ];

            // Calculer la comparaison
            $benchmarks['comparison'] = [
                'roi_vs_average' => round($benchmarks['pdv']['avg_roi'] - $benchmarks['global']['avg_roi'], 2),
                'ca_vs_average' => round($benchmarks['pdv']['avg_ca'] - $benchmarks['global']['avg_ca'], 0),
                'revenue_vs_average' => round($benchmarks['pdv']['avg_revenue'] - $benchmarks['global']['avg_revenue'], 0)
            ];
        }

        return $benchmarks;
    }

    private function getBaselineData(int $forecastDays): array
    {
        return ['placeholder' => 'baseline'];
    }

    private function applyScenarioChanges(array $baselineData, array $changes, string $simulationType): array
    {
        return ['placeholder' => 'simulated'];
    }

    private function compareScenarios(array $baselineData, array $simulatedData): array
    {
        return ['placeholder' => 'comparison'];
    }

    private function generateScenarioInsights(array $comparison, array $changes): array
    {
        return ['placeholder' => 'insights'];
    }

    /**
     * Calculer la direction de la tendance (hausse/baisse/stable)
     */
    private function calculateTrendDirection(array $trendData, string $metric): string
    {
        if (empty($trendData)) {
            return 'stable';
        }

        $values = array_column($trendData, $metric);
        $values = array_filter($values, function($v) { return $v !== null; });
        
        if (count($values) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($values, 0, (int)(count($values) / 2));
        $secondHalf = array_slice($values, (int)(count($values) / 2));

        $avgFirst = array_sum($firstHalf) / count($firstHalf);
        $avgSecond = array_sum($secondHalf) / count($secondHalf);

        $change = (($avgSecond - $avgFirst) / max($avgFirst, 1)) * 100;

        if ($change > 10) return 'hausse';
        if ($change < -10) return 'baisse';
        return 'stable';
    }

    /**
     * Détecter la saisonnalité dans les données
     */
    private function detectSeasonality(array $trendData, string $metric, string $period): ?array
    {
        if (count($trendData) < 12) {
            return null;
        }

        $values = array_column($trendData, $metric);
        $labels = array_column($trendData, 'period');
        
        // Calculer les variations mensuelles/périodiques
        $periodAvg = [];
        foreach ($values as $i => $value) {
            if ($value === null || !isset($labels[$i])) continue;
            
            $periodKey = $period === 'monthly' ? (int)substr($labels[$i], 5, 2) : $labels[$i];
            
            if (!isset($periodAvg[$periodKey])) {
                $periodAvg[$periodKey] = ['sum' => 0, 'count' => 0];
            }
            
            $periodAvg[$periodKey]['sum'] += $value;
            $periodAvg[$periodKey]['count']++;
        }

        $seasonalityData = [];
        foreach ($periodAvg as $period => $data) {
            $seasonalityData[$period] = $data['count'] > 0 ? $data['sum'] / $data['count'] : 0;
        }

        return count($seasonalityData) > 0 ? $seasonalityData : null;
    }

    /**
     * Calculer le taux de croissance
     */
    private function calculateGrowthRate(array $trendData, string $metric): float
    {
        if (count($trendData) < 2) {
            return 0;
        }

        $values = array_column($trendData, $metric);
        $values = array_filter($values, function($v) { return $v !== null; });
        
        if (count($values) < 2) {
            return 0;
        }

        $first = reset($values);
        $last = end($values);

        if ($first == 0) {
            return 0;
        }

        return (($last - $first) / $first) * 100;
    }

    /**
     * Détecter les anomalies dans les données
     */
    private function detectAnomalies(array $trendData, string $metric): array
    {
        if (empty($trendData)) {
            return [];
        }

        $values = array_column($trendData, $metric);
        $labels = array_column($trendData, 'period');
        $values = array_filter($values, function($v) { return $v !== null; });
        
        if (count($values) < 3) {
            return [];
        }

        $mean = array_sum($values) / count($values);
        $stdDev = sqrt(array_sum(array_map(function($v) use ($mean) {
            return pow($v - $mean, 2);
        }, $values)) / count($values));

        $anomalies = [];
        foreach ($trendData as $i => $data) {
            $value = $data[$metric] ?? null;
            $periodLabel = $labels[$i] ?? $data['period'] ?? 'unknown';
            
            if ($value === null) continue;
            
            $zScore = $stdDev > 0 ? abs(($value - $mean) / $stdDev) : 0;
            
            if ($zScore > 2.5) {
                $anomalies[] = [
                    'period' => $periodLabel,
                    'value' => $value,
                    'z_score' => round($zScore, 2),
                    'type' => $value > $mean ? 'pic' : 'creux'
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Identifier les patterns cycliques
     */
    private function identifyCyclicalPatterns(array $trendData, array $metrics): array
    {
        $patterns = [];
        
        foreach ($metrics as $metric) {
            $values = array_column($trendData, $metric);
            $values = array_filter($values, function($v) { return $v !== null; });
            
            if (count($values) < 6) {
                continue;
            }

            // Détection simple de cycles par analyse de pics
            $peaks = [];
            $troughs = [];
            
            for ($i = 1; $i < count($values) - 1; $i++) {
                if ($values[$i] > $values[$i-1] && $values[$i] > $values[$i+1]) {
                    $peaks[] = $i;
                } elseif ($values[$i] < $values[$i-1] && $values[$i] < $values[$i+1]) {
                    $troughs[] = $i;
                }
            }

            if (count($peaks) > 1) {
                $avgPeakDistance = 0;
                for ($i = 1; $i < count($peaks); $i++) {
                    $avgPeakDistance += $peaks[$i] - $peaks[$i-1];
                }
                $avgPeakDistance = $avgPeakDistance / (count($peaks) - 1);
                
                $patterns[$metric] = [
                    'detected' => true,
                    'cycle_length' => round($avgPeakDistance),
                    'peak_count' => count($peaks),
                    'trough_count' => count($troughs)
                ];
            } else {
                $patterns[$metric] = ['detected' => false];
            }
        }

        return $patterns;
    }

    /**
     * Générer des insights automatiques sur les tendances
     */
    private function generateTrendInsights(array $trendAnalysis, array $cyclicalPatterns): array
    {
        $insights = [];

        foreach ($trendAnalysis as $metric => $analysis) {
            // Insight sur la direction
            if ($analysis['trend_direction'] === 'hausse') {
                $insights[] = [
                    'type' => 'positive',
                    'metric' => $metric,
                    'message' => "Tendance à la hausse détectée pour $metric avec un taux de croissance de " . round($analysis['growth_rate'], 1) . "%"
                ];
            } elseif ($analysis['trend_direction'] === 'baisse') {
                $insights[] = [
                    'type' => 'warning',
                    'metric' => $metric,
                    'message' => "Tendance à la baisse détectée pour $metric avec un déclin de " . round(abs($analysis['growth_rate']), 1) . "%"
                ];
            }

            // Insight sur la volatilité
            if ($analysis['volatility'] > 50) {
                $insights[] = [
                    'type' => 'alert',
                    'metric' => $metric,
                    'message' => "Volatilité élevée détectée pour $metric (" . round($analysis['volatility'], 1) . "%). Performances imprévisibles."
                ];
            }

            // Insight sur les anomalies
            if (count($analysis['anomalies']) > 0) {
                $insights[] = [
                    'type' => 'info',
                    'metric' => $metric,
                    'message' => count($analysis['anomalies']) . " anomalie(s) détectée(s) pour $metric"
                ];
            }

            // Insight sur les patterns cycliques
            if (isset($cyclicalPatterns[$metric]) && $cyclicalPatterns[$metric]['detected']) {
                $insights[] = [
                    'type' => 'info',
                    'metric' => $metric,
                    'message' => "Pattern cyclique détecté pour $metric avec une période de " . $cyclicalPatterns[$metric]['cycle_length'] . " unités"
                ];
            }
        }

        return $insights;
    }
}