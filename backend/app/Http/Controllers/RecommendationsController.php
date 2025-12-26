<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RecommendationsController extends Controller
{
    /**
     * Get AI-powered recommendations for PDV and Dealers
     * Admin only - provides actionable insights with priority scoring
     */
    public function getRecommendations(Request $request)
    {
        $request->validate([
            'scope' => 'nullable|in:global,region,dealer,pdv',
            'entity_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $scope = $request->input('scope', 'global');
        $entityId = $request->input('entity_id');
        $limit = $request->input('limit', 10);

        $cacheKey = "recommendations_{$scope}_{$entityId}_{$limit}_" . auth()->id();
        
        return Cache::remember($cacheKey, 1800, function () use ($scope, $entityId, $limit) {
            $pdvRecommendations = $this->generatePdvRecommendations($scope, $entityId, $limit);
            $dealerRecommendations = $this->generateDealerRecommendations($scope, $entityId, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'pdv_recommendations' => $pdvRecommendations,
                    'dealer_recommendations' => $dealerRecommendations,
                    'summary' => [
                        'total_pdv_actions' => count($pdvRecommendations),
                        'total_dealer_actions' => count($dealerRecommendations),
                        'high_priority_count' => $this->countHighPriority($pdvRecommendations, $dealerRecommendations),
                    ],
                    'generated_at' => now()->toIso8601String(),
                ]
            ]);
        });
    }

    /**
     * Generate PDV-specific recommendations based on transaction patterns
     */
    private function generatePdvRecommendations($scope, $entityId, $limit)
    {
        $recommendations = [];
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $sixtyDaysAgo = $now->copy()->subDays(60);

        // Base query for PDV with recent transaction data
        $query = PointOfSale::query()
            ->where('status', 'validated')
            ->with(['organization', 'creator']);

        // Apply scope filtering
        if ($scope === 'region' && $entityId) {
            $query->where('region', $entityId);
        } elseif ($scope === 'dealer' && $entityId) {
            $query->where('organization_id', $entityId);
        } elseif ($scope === 'pdv' && $entityId) {
            $query->where('id', $entityId);
        }

        $pdvList = $query->limit(100)->get(); // Limit to first 100 PDV for performance
        
        \Log::info('PDV Recommendations Debug', [
            'total_pdv' => $pdvList->count(),
            'scope' => $scope,
            'date_range' => [$thirtyDaysAgo->toDateString(), $now->toDateString()]
        ]);

        foreach ($pdvList as $pdv) {
            // Get transaction stats for the PDV
            $recentStats = $this->getPdvTransactionStats($pdv->id, $thirtyDaysAgo, $now);
            $previousStats = $this->getPdvTransactionStats($pdv->id, $sixtyDaysAgo, $thirtyDaysAgo);

            \Log::info('PDV Stats', [
                'pdv_id' => $pdv->id,
                'pdv_name' => $pdv->nom_point,
                'recent_transactions' => $recentStats['transaction_count'],
                'recent_ca' => $recentStats['ca'],
                'previous_transactions' => $previousStats['transaction_count'],
                'previous_ca' => $previousStats['ca']
            ]);

            // 1. Inactive PDV - No transactions in 30 days
            if ($recentStats['transaction_count'] === 0 && $previousStats['transaction_count'] > 0) {
                $recommendations[] = [
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'pdv_numero' => $pdv->numero,
                    'region' => $pdv->region,
                    'dealer_name' => $pdv->organization->name ?? 'N/A',
                    'action_type' => 'reactivation',
                    'priority' => 'high',
                    'impact_score' => 85,
                    'title' => 'PDV inactif - Réactivation urgente',
                    'description' => "Ce PDV n'a enregistré aucune transaction depuis 30 jours malgré une activité précédente.",
                    'recommended_actions' => [
                        'Contacter l\'agent pour comprendre la raison de l\'inactivité',
                        'Vérifier l\'approvisionnement en liquidité',
                        'Organiser une visite terrain si nécessaire',
                        'Proposer une formation de remise à niveau'
                    ],
                    'expected_outcome' => 'Réactivation du PDV et reprise des transactions',
                    'previous_ca' => $previousStats['ca'],
                ];
            }

            // 1b. Very low activity - Less than 10 transactions in 30 days
            if ($recentStats['transaction_count'] > 0 && $recentStats['transaction_count'] < 10) {
                $recommendations[] = [
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'pdv_numero' => $pdv->numero,
                    'region' => $pdv->region,
                    'dealer_name' => $pdv->organization->name ?? 'N/A',
                    'action_type' => 'performance_improvement',
                    'priority' => 'medium',
                    'impact_score' => 70,
                    'title' => 'Activité très faible',
                    'description' => sprintf(
                        'Seulement %d transaction(s) sur les 30 derniers jours. CA de %s FCFA.',
                        $recentStats['transaction_count'],
                        number_format($recentStats['ca'], 0, ',', ' ')
                    ),
                    'recommended_actions' => [
                        'Analyser les raisons de la faible activité',
                        'Améliorer la visibilité du point de vente',
                        'Former l\'agent aux techniques de vente',
                        'Vérifier la concurrence locale'
                    ],
                    'expected_outcome' => 'Augmentation du nombre de transactions à 20+ par mois',
                    'transaction_count' => $recentStats['transaction_count'],
                    'current_ca' => $recentStats['ca'],
                ];
            }

            // 2. Declining CA - Drop > 20%
            if ($recentStats['ca'] > 0 && $previousStats['ca'] > 0) {
                $caDecline = (($previousStats['ca'] - $recentStats['ca']) / $previousStats['ca']) * 100;
                
                if ($caDecline > 20) {
                    $recommendations[] = [
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_point,
                        'pdv_numero' => $pdv->numero,
                        'region' => $pdv->region,
                        'dealer_name' => $pdv->organization->name ?? 'N/A',
                        'action_type' => 'performance_improvement',
                        'priority' => 'high',
                        'impact_score' => min(90, 60 + $caDecline),
                        'title' => 'Baisse significative du CA',
                        'description' => sprintf(
                            'Le CA a chuté de %.1f%% en 30 jours (de %s à %s).',
                            $caDecline,
                            number_format($previousStats['ca'], 0, ',', ' '),
                            number_format($recentStats['ca'], 0, ',', ' ')
                        ),
                        'recommended_actions' => [
                            'Analyser la concurrence locale',
                            'Enquêter sur des problèmes opérationnels',
                            'Vérifier la satisfaction de l\'agent',
                            'Augmenter les efforts marketing locaux'
                        ],
                        'expected_outcome' => 'Arrêter la baisse et stabiliser le CA',
                        'decline_percentage' => round($caDecline, 1),
                        'previous_ca' => $previousStats['ca'],
                        'current_ca' => $recentStats['ca'],
                    ];
                }
            }

            // 3. Low transaction volume but good conversion
            if ($recentStats['transaction_count'] > 0 && $recentStats['transaction_count'] < 100 && $recentStats['avg_transaction'] > 10000) {
                $recommendations[] = [
                    'pdv_id' => $pdv->id,
                    'pdv_name' => $pdv->nom_point,
                    'pdv_numero' => $pdv->numero,
                    'region' => $pdv->region,
                    'dealer_name' => $pdv->organization->name ?? 'N/A',
                    'action_type' => 'growth_opportunity',
                    'priority' => 'medium',
                    'impact_score' => 70,
                    'title' => 'Potentiel de croissance élevé',
                    'description' => sprintf(
                        'Transaction moyenne élevée (%s FCFA) mais volume faible (%d transactions). Potentiel d\'augmentation du CA.',
                        number_format($recentStats['avg_transaction'], 0, ',', ' '),
                        $recentStats['transaction_count']
                    ),
                    'recommended_actions' => [
                        'Encourager les dépôts réguliers',
                        'Former l\'agent aux techniques de fidélisation',
                        'Améliorer la visibilité du point de vente',
                        'Proposer des promotions locales'
                    ],
                    'expected_outcome' => 'Augmentation du nombre de transactions de 50-100%',
                    'avg_transaction' => $recentStats['avg_transaction'],
                    'transaction_count' => $recentStats['transaction_count'],
                ];
            }

            // 4. Unbalanced depot/retrait ratio
            if ($recentStats['transaction_count'] > 10) {
                $depotRatio = $recentStats['depot_count'] / $recentStats['transaction_count'];
                $retraitRatio = $recentStats['retrait_count'] / $recentStats['transaction_count'];
                
                if ($depotRatio < 0.2 || $depotRatio > 0.8) {
                    $recommendations[] = [
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_point,
                        'pdv_numero' => $pdv->numero,
                        'region' => $pdv->region,
                        'dealer_name' => $pdv->organization->name ?? 'N/A',
                        'action_type' => 'balance_optimization',
                        'priority' => 'medium',
                        'impact_score' => 60,
                        'title' => 'Déséquilibre dépôts/retraits',
                        'description' => sprintf(
                            'Ratio déséquilibré: %d%% dépôts, %d%% retraits. Risque de problème de liquidité.',
                            round($depotRatio * 100),
                            round($retraitRatio * 100)
                        ),
                        'recommended_actions' => $depotRatio > 0.8 ? [
                            'Encourager les retraits pour améliorer la rotation',
                            'Former l\'agent aux services de retrait',
                            'Communiquer sur la disponibilité des retraits'
                        ] : [
                            'Encourager les dépôts pour équilibrer',
                            'Assurer une liquidité suffisante',
                            'Former l\'agent aux services de dépôt'
                        ],
                        'expected_outcome' => 'Équilibrage du ratio vers 40/60',
                        'depot_ratio' => round($depotRatio * 100, 1),
                        'retrait_ratio' => round($retraitRatio * 100, 1),
                    ];
                }
            }

            // 5. High performer - Share best practices
            if ($recentStats['ca'] > 0 && $previousStats['ca'] > 0) {
                $caGrowth = (($recentStats['ca'] - $previousStats['ca']) / $previousStats['ca']) * 100;
                
                if ($caGrowth > 30 && $recentStats['ca'] > 200000) {
                    $recommendations[] = [
                        'pdv_id' => $pdv->id,
                        'pdv_name' => $pdv->nom_point,
                        'pdv_numero' => $pdv->numero,
                        'region' => $pdv->region,
                        'dealer_name' => $pdv->organization->name ?? 'N/A',
                        'action_type' => 'best_practice',
                        'priority' => 'low',
                        'impact_score' => 50,
                        'title' => 'PDV performant - Partager les bonnes pratiques',
                        'description' => sprintf(
                            'Croissance exceptionnelle de %.1f%% ! Identifier et documenter les facteurs de succès.',
                            $caGrowth
                        ),
                        'recommended_actions' => [
                            'Interviewer l\'agent sur ses méthodes',
                            'Documenter les bonnes pratiques',
                            'Organiser des sessions de partage avec d\'autres agents',
                            'Récompenser la performance'
                        ],
                        'expected_outcome' => 'Transfert de compétences vers d\'autres PDV',
                        'growth_percentage' => round($caGrowth, 1),
                        'current_ca' => $recentStats['ca'],
                    ];
                }
            }

            if (count($recommendations) >= $limit) {
                break;
            }
        }

        // Sort by impact score descending
        usort($recommendations, function ($a, $b) {
            return $b['impact_score'] <=> $a['impact_score'];
        });

        \Log::info('PDV Recommendations Final', [
            'total_recommendations' => count($recommendations),
            'limit' => $limit,
            'recommendations_sample' => array_slice($recommendations, 0, 3)
        ]);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Generate Dealer-specific recommendations
     */
    private function generateDealerRecommendations($scope, $entityId, $limit)
    {
        $recommendations = [];
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Get dealers with their PDV stats
        $query = Organization::query()
            ->withCount(['pointOfSales' => function ($q) {
                $q->where('status', 'validated');
            }]);

        if ($scope === 'dealer' && $entityId) {
            $query->where('id', $entityId);
        }

        $dealers = $query->having('point_of_sales_count', '>', 0)->get();

        foreach ($dealers as $dealer) {
            $dealerStats = $this->getDealerStats($dealer->id, $thirtyDaysAgo, $now);

            // 1. Low PDV activation rate
            $activationRate = $dealerStats['point_of_sales_count'] > 0 
                ? ($dealerStats['active_pdv_count'] / $dealerStats['point_of_sales_count']) * 100 
                : 0;

            if ($activationRate < 50 && $dealerStats['point_of_sales_count'] >= 3) {
                $recommendations[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->name,
                    'action_type' => 'activation',
                    'priority' => 'high',
                    'impact_score' => 80,
                    'title' => 'Taux d\'activation faible',
                    'description' => sprintf(
                        'Seulement %d%% des PDV (%d sur %d) sont actifs ce mois-ci.',
                        round($activationRate),
                        $dealerStats['active_pdv_count'],
                        $dealerStats['point_of_sales_count']
                    ),
                    'recommended_actions' => [
                        'Former les agents inactifs',
                        'Identifier les obstacles à l\'activité',
                        'Mettre en place un système de motivation',
                        'Organiser des visites terrain régulières'
                    ],
                    'expected_outcome' => 'Augmentation du taux d\'activation à 80%+',
                    'activation_rate' => round($activationRate, 1),
                    'active_pdv' => $dealerStats['active_pdv_count'],
                    'total_pdv' => $dealerStats['point_of_sales_count'],
                ];
            }

            // 2. Network expansion opportunity
            if ($dealerStats['avg_ca_per_pdv'] > 500000 && $dealerStats['point_of_sales_count'] < 15) {
                $recommendations[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->name,
                    'action_type' => 'expansion',
                    'priority' => 'medium',
                    'impact_score' => 75,
                    'title' => 'Opportunité d\'expansion du réseau',
                    'description' => sprintf(
                        'CA moyen par PDV élevé (%s FCFA). Le dealer peut gérer plus de points.',
                        number_format($dealerStats['avg_ca_per_pdv'], 0, ',', ' ')
                    ),
                    'recommended_actions' => [
                        'Identifier de nouvelles zones à couvrir',
                        'Recruter de nouveaux agents qualifiés',
                        'Ouvrir 2-3 nouveaux PDV dans les 3 mois',
                        'Assurer un accompagnement des nouveaux agents'
                    ],
                    'expected_outcome' => 'Augmentation du CA global de 40-60%',
                    'avg_ca_per_pdv' => $dealerStats['avg_ca_per_pdv'],
                    'current_pdv_count' => $dealerStats['point_of_sales_count'],
                ];
            }

            // 3. High regional concentration
            $regionConcentration = $this->getDealerRegionConcentration($dealer->id);
            if ($regionConcentration['max_region_percentage'] > 70 && $dealerStats['point_of_sales_count'] >= 3) {
                $recommendations[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->name,
                    'action_type' => 'diversification',
                    'priority' => 'low',
                    'impact_score' => 55,
                    'title' => 'Concentration géographique élevée',
                    'description' => sprintf(
                        '%d%% des PDV sont dans la région %s. Risque de dépendance régionale.',
                        round($regionConcentration['max_region_percentage']),
                        $regionConcentration['main_region']
                    ),
                    'recommended_actions' => [
                        'Diversifier vers d\'autres régions',
                        'Réduire le risque de concentration',
                        'Explorer de nouveaux marchés régionaux'
                    ],
                    'expected_outcome' => 'Résilience accrue face aux variations régionales',
                    'main_region' => $regionConcentration['main_region'],
                    'concentration' => round($regionConcentration['max_region_percentage'], 1),
                ];
            }

            // 4. Strong performance - Recognition
            if ($dealerStats['total_ca'] > 2000000 && $activationRate > 70) {
                $recommendations[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->name,
                    'action_type' => 'recognition',
                    'priority' => 'low',
                    'impact_score' => 40,
                    'title' => 'Performance excellente - Reconnaissance',
                    'description' => sprintf(
                        'CA total de %s FCFA avec %d%% d\'activation. Dealer exemplaire.',
                        number_format($dealerStats['total_ca'], 0, ',', ' '),
                        round($activationRate)
                    ),
                    'recommended_actions' => [
                        'Récompenser publiquement les résultats',
                        'Partager les méthodes de gestion',
                        'Proposer des opportunités d\'expansion premium',
                        'Inviter à mentorer d\'autres dealers'
                    ],
                    'expected_outcome' => 'Motivation maintenue et inspiration pour les autres',
                    'total_ca' => $dealerStats['total_ca'],
                    'activation_rate' => round($activationRate, 1),
                ];
            }

            if (count($recommendations) >= $limit) {
                break;
            }
        }

        // Sort by impact score descending
        usort($recommendations, function ($a, $b) {
            return $b['impact_score'] <=> $a['impact_score'];
        });

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Get transaction statistics for a specific PDV
     */
    private function getPdvTransactionStats($pdvId, $startDate, $endDate)
    {
        // Get PDV numero_flooz for joining with transactions table
        $pdv = PointOfSale::findOrFail($pdvId);
        
        $stats = DB::table('pdv_transactions')
            ->where('pdv_numero', $pdv->numero_flooz)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(count_depot + count_retrait) as transaction_count,
                SUM(retrait_keycost) as ca,
                SUM(count_depot) as depot_count,
                SUM(count_retrait) as retrait_count
            ')
            ->first();

        $transactionCount = $stats->transaction_count ?? 0;
        $ca = $stats->ca ?? 0;

        return [
            'transaction_count' => $transactionCount,
            'ca' => $ca,
            'avg_transaction' => $transactionCount > 0 ? $ca / $transactionCount : 0,
            'depot_count' => $stats->depot_count ?? 0,
            'retrait_count' => $stats->retrait_count ?? 0,
        ];
    }

    /**
     * Get aggregated statistics for a dealer
     */
    private function getDealerStats($dealerId, $startDate, $endDate)
    {
        // Get PDV numero_flooz for this dealer
        $pdvNumeros = PointOfSale::where('organization_id', $dealerId)
            ->where('status', 'validated')
            ->pluck('numero_flooz');

        $totalPdv = $pdvNumeros->count();

        if ($totalPdv === 0) {
            return [
                'point_of_sales_count' => 0,
                'active_pdv_count' => 0,
                'total_ca' => 0,
                'avg_ca_per_pdv' => 0,
            ];
        }

        // Get transaction stats
        $stats = DB::table('pdv_transactions')
            ->whereIn('pdv_numero', $pdvNumeros)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(DISTINCT pdv_numero) as active_pdv_count,
                SUM(retrait_keycost) as total_ca
            ')
            ->first();

        $activePdv = $stats->active_pdv_count ?? 0;
        $totalCa = $stats->total_ca ?? 0;
        $avgCaPerPdv = $activePdv > 0 ? $totalCa / $activePdv : 0;

        return [
            'point_of_sales_count' => $totalPdv,
            'active_pdv_count' => $activePdv,
            'total_ca' => $totalCa,
            'avg_ca_per_pdv' => $avgCaPerPdv,
        ];
    }

    /**
     * Get dealer's regional concentration
     */
    private function getDealerRegionConcentration($dealerId)
    {
        $regionStats = PointOfSale::where('organization_id', $dealerId)
            ->where('status', 'validated')
            ->select('region', DB::raw('COUNT(*) as count'))
            ->groupBy('region')
            ->orderByDesc('count')
            ->get();

        $total = $regionStats->sum('count');
        $mainRegion = $regionStats->first();

        return [
            'main_region' => $mainRegion->region ?? 'N/A',
            'max_region_percentage' => $total > 0 ? ($mainRegion->count / $total) * 100 : 0,
        ];
    }

    /**
     * Count high priority recommendations
     */
    private function countHighPriority($pdvRecs, $dealerRecs)
    {
        $allRecs = array_merge($pdvRecs, $dealerRecs);
        return count(array_filter($allRecs, function ($rec) {
            return $rec['priority'] === 'high';
        }));
    }
}
