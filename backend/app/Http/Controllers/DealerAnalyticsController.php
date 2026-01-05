<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;
use Carbon\Carbon;

class DealerAnalyticsController extends Controller
{
    /**
     * Récupérer les analytics pour un dealer (dealer-owner uniquement)
     * Affiche les commissions dealer, retenues, et données GIVE
     */
    public function getAnalytics(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur est un dealer-owner
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $period = $request->input('period', 'month');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        $now = Carbon::now();
        
        [$startDate, $endDate] = $this->getPeriodDates($period, $now, $year, $month, $week);
        [$prevStartDate, $prevEndDate] = $this->getPreviousPeriodDates($period, $startDate, $endDate, $year, $month, $week);

        // Récupérer les paramètres de cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);

        $cacheKey = "dealer_analytics_{$organizationId}_{$period}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        // Utiliser le cache uniquement si activé
        if ($cacheEnabled) {
            $data = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $period, $startDate, $endDate, $prevStartDate, $prevEndDate) {
                $currentKPI = $this->calculateKPI($organizationId, $startDate, $endDate);
                $previousKPI = $this->calculateKPI($organizationId, $prevStartDate, $prevEndDate);
                
                return [
                    'period' => $period,
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                    'kpi' => $this->addComparisons($currentKPI, $previousKPI),
                    'commissions_by_pdv' => $this->getCommissionsByPdv($organizationId, $startDate, $endDate),
                    'evolution' => $this->getEvolution($organizationId, $period, $startDate, $endDate),
                    'give_network_stats' => $this->getGiveNetworkStats($organizationId, $startDate, $endDate),
                ];
            });
        } else {
            // Sans cache, calculer directement
            $currentKPI = $this->calculateKPI($organizationId, $startDate, $endDate);
            $previousKPI = $this->calculateKPI($organizationId, $prevStartDate, $prevEndDate);
            
            $data = [
                'period' => $period,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'kpi' => $this->addComparisons($currentKPI, $previousKPI),
                'commissions_by_pdv' => $this->getCommissionsByPdv($organizationId, $startDate, $endDate),
                'evolution' => $this->getEvolution($organizationId, $period, $startDate, $endDate),
                'give_network_stats' => $this->getGiveNetworkStats($organizationId, $startDate, $endDate),
            ];
        }

        return response()->json($data);
    }

    /**
     * Récupérer les revenus mensuels pour un dealer (année sélectionnée)
     */
    public function getMonthlyRevenue(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $year = $request->input('year', date('Y'));
        
        // Récupérer les paramètres de cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);

        $cacheKey = "dealer_monthly_revenue_{$organizationId}_{$year}";
        
        if ($cacheEnabled) {
            $data = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $year) {
                return $this->calculateMonthlyRevenue($organizationId, $year);
            });
        } else {
            $data = $this->calculateMonthlyRevenue($organizationId, $year);
        }

        return response()->json($data);
    }

    /**
     * Calculer les revenus mensuels (méthode interne)
     */
    private function calculateMonthlyRevenue($organizationId, $year)
    {
        $monthlyData = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->where('p.organization_id', $organizationId)
            ->whereYear('t.transaction_date', $year)
            ->selectRaw('
                MONTH(t.transaction_date) as month,
                SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commissions_total,
                SUM(t.dealer_depot_commission) as commissions_depot,
                SUM(t.dealer_retrait_commission) as commissions_retrait,
                SUM(t.dealer_depot_retenue + t.dealer_retrait_retenue) as retenues_total,
                SUM(t.dealer_depot_retenue) as retenues_depot,
                SUM(t.dealer_retrait_retenue) as retenues_retrait,
                SUM(t.count_depot + t.count_retrait) as total_transactions
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Initialiser tous les mois avec des valeurs 0
        $result = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthData = $monthlyData->firstWhere('month', $m);
            $result[] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->format('M Y'),
                'commissions_total' => $monthData ? (float) $monthData->commissions_total : 0,
                'commissions_depot' => $monthData ? (float) $monthData->commissions_depot : 0,
                'commissions_retrait' => $monthData ? (float) $monthData->commissions_retrait : 0,
                'retenues_total' => $monthData ? (float) $monthData->retenues_total : 0,
                'retenues_depot' => $monthData ? (float) $monthData->retenues_depot : 0,
                'retenues_retrait' => $monthData ? (float) $monthData->retenues_retrait : 0,
                'total_transactions' => $monthData ? $monthData->total_transactions : 0,
            ];
        }

        return $result;
    }

    /**
     * Calculer les KPI principaux pour un dealer
     */
    private function calculateKPI($organizationId, $startDate, $endDate)
    {
        $kpi = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->where('p.organization_id', $organizationId)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commissions_total,
                SUM(t.dealer_depot_commission) as commissions_depot,
                SUM(t.dealer_retrait_commission) as commissions_retrait,
                SUM(t.dealer_depot_retenue + t.dealer_retrait_retenue) as retenues_total,
                SUM(t.dealer_depot_retenue) as retenues_depot,
                SUM(t.dealer_retrait_retenue) as retenues_retrait,
                SUM(t.count_depot + t.count_retrait) as total_transactions,
                SUM(t.count_depot) as total_depots,
                SUM(t.count_retrait) as total_retraits,
                SUM(t.sum_depot + t.sum_retrait) as volume_total,
                COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_actifs,
                SUM(t.count_give_send) as give_send_count,
                SUM(t.sum_give_send) as give_send_amount,
                SUM(t.count_give_receive) as give_receive_count,
                SUM(t.sum_give_receive) as give_receive_amount
            ')
            ->first();

        if (!$kpi || $kpi->total_transactions == 0) {
            return [
                'commissions' => [
                    'total' => 0,
                    'depot' => 0,
                    'retrait' => 0,
                    'moyenne_par_transaction' => 0,
                ],
                'retenues' => [
                    'total' => 0,
                    'depot' => 0,
                    'retrait' => 0,
                ],
                'transactions' => [
                    'total' => 0,
                    'depots' => 0,
                    'retraits' => 0,
                ],
                'volume_total' => 0,
                'pdv_actifs' => 0,
                'give_transfers' => [
                    'envoyes' => [
                        'count' => 0,
                        'amount' => 0,
                    ],
                    'recus' => [
                        'count' => 0,
                        'amount' => 0,
                    ],
                ],
            ];
        }

        return [
            'commissions' => [
                'total' => (float) $kpi->commissions_total,
                'depot' => (float) $kpi->commissions_depot,
                'retrait' => (float) $kpi->commissions_retrait,
                'moyenne_par_transaction' => $kpi->total_transactions > 0 
                    ? round($kpi->commissions_total / $kpi->total_transactions, 2) 
                    : 0,
            ],
            'retenues' => [
                'total' => (float) $kpi->retenues_total,
                'depot' => (float) $kpi->retenues_depot,
                'retrait' => (float) $kpi->retenues_retrait,
            ],
            'transactions' => [
                'total' => $kpi->total_transactions,
                'depots' => $kpi->total_depots,
                'retraits' => $kpi->total_retraits,
            ],
            'volume_total' => (float) $kpi->volume_total,
            'pdv_actifs' => $kpi->pdv_actifs,
            'give_transfers' => [
                'envoyes' => [
                    'count' => $kpi->give_send_count,
                    'amount' => (float) $kpi->give_send_amount,
                ],
                'recus' => [
                    'count' => $kpi->give_receive_count,
                    'amount' => (float) $kpi->give_receive_amount,
                ],
            ],
        ];
    }

    /**
     * Récupérer les commissions générées par chaque PDV
     */
    private function getCommissionsByPdv($organizationId, $startDate, $endDate, $limit = 20)
    {
        return DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->where('p.organization_id', $organizationId)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->select('t.pdv_numero', 'p.nom_point', 'p.region', 'p.prefecture')
            ->selectRaw('
                SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commissions_generees,
                SUM(t.dealer_depot_commission) as commissions_depot,
                SUM(t.dealer_retrait_commission) as commissions_retrait,
                SUM(t.dealer_depot_retenue + t.dealer_retrait_retenue) as retenues_total,
                SUM(t.count_depot + t.count_retrait) as total_transactions
            ')
            ->groupBy('t.pdv_numero', 'p.nom_point', 'p.region', 'p.prefecture')
            ->orderByDesc('commissions_generees')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'pdv_numero' => $item->pdv_numero,
                    'nom_point' => $item->nom_point,
                    'region' => $item->region,
                    'prefecture' => $item->prefecture,
                    'commissions_generees' => round($item->commissions_generees, 2),
                    'commissions_depot' => round($item->commissions_depot, 2),
                    'commissions_retrait' => round($item->commissions_retrait, 2),
                    'retenues_total' => round($item->retenues_total, 2),
                    'total_transactions' => $item->total_transactions,
                    'commission_par_transaction' => $item->total_transactions > 0 
                        ? round($item->commissions_generees / $item->total_transactions, 2) 
                        : 0,
                ];
            })
            ->values();
    }

    /**
     * Récupérer l'évolution des commissions et retenues dans le temps
     */
    private function getEvolution($organizationId, $period, $startDate, $endDate)
    {
        // Déterminer le format de groupement selon la période
        $groupBy = match($period) {
            'historical_year' => 'month',          // Grouper par mois pour une année complète
            'month', 'quarter' => 'month',         // Grouper par mois pour les périodes mensuelles/trimestrielles courantes
            'historical_month', 'historical_week' => 'day',  // Grouper par jour pour mois/semaine historiques
            'day' => 'hour',
            'week' => 'day',
            default => 'day',
        };

        if ($groupBy === 'month') {
            // Groupement par mois pour l'année complète
            $evolution = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
                ->where('p.organization_id', $organizationId)
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE_FORMAT(t.transaction_date, '%Y-%m') as period,
                    SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commissions,
                    SUM(t.dealer_depot_retenue + t.dealer_retrait_retenue) as retenues,
                    SUM(t.count_depot + t.count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_actifs
                ")
                ->groupBy(DB::raw("DATE_FORMAT(t.transaction_date, '%Y-%m')"))
                ->orderBy('period')
                ->get();

            return $evolution->map(function ($item) {
                $date = Carbon::createFromFormat('Y-m', $item->period);
                return [
                    'date' => $item->period,
                    'label' => $date->format('M Y'),  // Jan 2024
                    'commissions' => round($item->commissions, 2),
                    'retenues' => round($item->retenues, 2),
                    'transactions' => $item->transactions,
                    'pdv_actifs' => $item->pdv_actifs,
                ];
            })->values();
        } else {
            // Groupement par jour (ou heure pour 'day')
            $evolution = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
                ->where('p.organization_id', $organizationId)
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE(t.transaction_date) as date,
                    SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commissions,
                    SUM(t.dealer_depot_retenue + t.dealer_retrait_retenue) as retenues,
                    SUM(t.count_depot + t.count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_actifs
                ")
                ->groupBy(DB::raw('DATE(t.transaction_date)'))
                ->orderBy('date')
                ->get();

            return $evolution->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'label' => Carbon::parse($item->date)->format('d M'),  // 15 Jan
                    'commissions' => round($item->commissions, 2),
                    'retenues' => round($item->retenues, 2),
                    'transactions' => $item->transactions,
                    'pdv_actifs' => $item->pdv_actifs,
                ];
            })->values();
        }
    }

    /**
     * Récupérer les statistiques détaillées des GIVE (réseau vs hors réseau)
     */
    private function getGiveNetworkStats($organizationId, $startDate, $endDate)
    {
        $stats = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->where('p.organization_id', $organizationId)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(t.count_give_send) as total_send_count,
                SUM(t.sum_give_send) as total_send_amount,
                SUM(t.count_give_send_in_network) as send_in_network_count,
                SUM(t.sum_give_send_in_network) as send_in_network_amount,
                SUM(t.count_give_send_out_network) as send_out_network_count,
                SUM(t.sum_give_send_out_network) as send_out_network_amount,
                SUM(t.count_give_receive) as total_receive_count,
                SUM(t.sum_give_receive) as total_receive_amount,
                SUM(t.count_give_receive_in_network) as receive_in_network_count,
                SUM(t.sum_give_receive_in_network) as receive_in_network_amount,
                SUM(t.count_give_receive_out_network) as receive_out_network_count,
                SUM(t.sum_give_receive_out_network) as receive_out_network_amount
            ')
            ->first();

        return [
            'envoyes' => [
                'total' => [
                    'count' => $stats->total_send_count ?? 0,
                    'amount' => (float) ($stats->total_send_amount ?? 0),
                ],
                'in_network' => [
                    'count' => $stats->send_in_network_count ?? 0,
                    'amount' => (float) ($stats->send_in_network_amount ?? 0),
                    'percentage' => $stats->total_send_count > 0 
                        ? round(($stats->send_in_network_count / $stats->total_send_count) * 100, 1) 
                        : 0,
                ],
                'out_network' => [
                    'count' => $stats->send_out_network_count ?? 0,
                    'amount' => (float) ($stats->send_out_network_amount ?? 0),
                    'percentage' => $stats->total_send_count > 0 
                        ? round(($stats->send_out_network_count / $stats->total_send_count) * 100, 1) 
                        : 0,
                ],
            ],
            'recus' => [
                'total' => [
                    'count' => $stats->total_receive_count ?? 0,
                    'amount' => (float) ($stats->total_receive_amount ?? 0),
                ],
                'in_network' => [
                    'count' => $stats->receive_in_network_count ?? 0,
                    'amount' => (float) ($stats->receive_in_network_amount ?? 0),
                    'percentage' => $stats->total_receive_count > 0 
                        ? round(($stats->receive_in_network_count / $stats->total_receive_count) * 100, 1) 
                        : 0,
                ],
                'out_network' => [
                    'count' => $stats->receive_out_network_count ?? 0,
                    'amount' => (float) ($stats->receive_out_network_amount ?? 0),
                    'percentage' => $stats->total_receive_count > 0 
                        ? round(($stats->receive_out_network_count / $stats->total_receive_count) * 100, 1) 
                        : 0,
                ],
            ],
            'balance' => [
                'count' => ($stats->total_receive_count ?? 0) - ($stats->total_send_count ?? 0),
                'amount' => (float) (($stats->total_receive_amount ?? 0) - ($stats->total_send_amount ?? 0)),
            ],
        ];
    }

    /**
     * Ajouter les comparaisons avec la période précédente
     */
    private function addComparisons($currentKPI, $previousKPI)
    {
        // Fonction helper pour calculer le pourcentage de variation
        $calculateChange = function($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100 : 0;
            }
            return round((($current - $previous) / $previous) * 100, 1);
        };

        // Ajouter les comparaisons pour les commissions
        if (isset($currentKPI['commissions'])) {
            $currentKPI['commissions']['comparison'] = [
                'total' => $calculateChange(
                    $currentKPI['commissions']['total'],
                    $previousKPI['commissions']['total'] ?? 0
                ),
                'depot' => $calculateChange(
                    $currentKPI['commissions']['depot'],
                    $previousKPI['commissions']['depot'] ?? 0
                ),
                'retrait' => $calculateChange(
                    $currentKPI['commissions']['retrait'],
                    $previousKPI['commissions']['retrait'] ?? 0
                ),
            ];
        }

        // Ajouter les comparaisons pour les retenues
        if (isset($currentKPI['retenues'])) {
            $currentKPI['retenues']['comparison'] = [
                'total' => $calculateChange(
                    $currentKPI['retenues']['total'],
                    $previousKPI['retenues']['total'] ?? 0
                ),
            ];
        }

        // Ajouter les comparaisons pour les transactions
        if (isset($currentKPI['transactions'])) {
            $currentKPI['transactions']['comparison'] = [
                'total' => $calculateChange(
                    $currentKPI['transactions']['total'],
                    $previousKPI['transactions']['total'] ?? 0
                ),
            ];
        }

        // Ajouter la comparaison pour les PDV actifs
        if (isset($currentKPI['pdv_actifs'])) {
            $currentKPI['pdv_actifs_comparison'] = $calculateChange(
                $currentKPI['pdv_actifs'],
                $previousKPI['pdv_actifs'] ?? 0
            );
        }

        return $currentKPI;
    }

    /**
     * Obtenir les dates de la période précédente pour comparaison
     */
    private function getPreviousPeriodDates($period, Carbon $startDate, Carbon $endDate, $year = null, $month = null, $week = null)
    {
        $duration = $startDate->diffInDays($endDate) + 1;

        return match($period) {
            'day' => [
                $startDate->copy()->subDay()->startOfDay(),
                $startDate->copy()->subDay()->endOfDay()
            ],
            'week' => [
                $startDate->copy()->subWeek()->startOfDay(),
                $endDate->copy()->subWeek()->endOfDay()
            ],
            'month' => [
                $startDate->copy()->subMonth()->startOfDay(),
                $endDate->copy()->subMonth()->endOfDay()
            ],
            'quarter' => [
                $startDate->copy()->subQuarter()->startOfDay(),
                $endDate->copy()->subQuarter()->endOfDay()
            ],
            'historical_year' => [
                Carbon::create($year - 1, 1, 1)->startOfDay(),
                Carbon::create($year - 1, 12, 31)->endOfDay()
            ],
            'historical_month' => [
                $month == 1 
                    ? Carbon::create($year - 1, 12, 1)->startOfDay()
                    : Carbon::create($year, $month - 1, 1)->startOfDay(),
                $month == 1
                    ? Carbon::create($year - 1, 12, 1)->endOfMonth()->endOfDay()
                    : Carbon::create($year, $month - 1, 1)->endOfMonth()->endOfDay()
            ],
            'historical_week' => [
                $startDate->copy()->subWeek()->startOfDay(),
                $endDate->copy()->subWeek()->endOfDay()
            ],
            default => [
                $startDate->copy()->subDays($duration)->startOfDay(),
                $endDate->copy()->subDays($duration)->endOfDay()
            ],
        };
    }

    /**
     * Déterminer les dates de début et de fin selon la période
     */
    private function getPeriodDates($period, Carbon $now, $year = null, $month = null, $week = null)
    {
        // Pour l'année courante, on borne à J-1 pour éviter d'inclure la journée en cours
        $yesterdayEnd = $now->copy()->subDay()->endOfDay();
        
        return match($period) {
            // Jours du mois courant
            'day' => [
                $now->copy()->startOfMonth(),
                $yesterdayEnd
            ],
            // Semaines de l'année courante
            'week' => [
                $now->copy()->startOfYear(),
                $yesterdayEnd
            ],
            // Mois courant jusqu'à J-1 (mois complet si mois précédent)
            'month' => [
                $now->copy()->startOfMonth(),
                $yesterdayEnd
            ],
            // Trimestre courant jusqu'à J-1
            'quarter' => [
                $yesterdayEnd->copy()->startOfQuarter(),
                $yesterdayEnd
            ],
            'historical_year' => [
                Carbon::create($year, 1, 1)->startOfDay(),
                Carbon::create($year, 12, 31)->endOfDay()
            ],
            'historical_month' => [
                Carbon::create($year, $month, 1)->startOfDay(),
                Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()
            ],
            'historical_week' => $this->getHistoricalWeekDates($year, $week),
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Obtenir les dates de début et fin d'une semaine historique
     */
    private function getHistoricalWeekDates($year, $weekNumber)
    {
        $jan4 = Carbon::create($year, 1, 4);
        $dayOfWeek = $jan4->dayOfWeek === 0 ? 7 : $jan4->dayOfWeek;
        $weekStart = $jan4->copy()->subDays($dayOfWeek - 1)->addWeeks($weekNumber - 1);
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        
        return [$weekStart->startOfDay(), $weekEnd];
    }

    /**
     * Récupérer uniquement les KPI
     */
    public function getKpi(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $period = $request->input('period', 'month');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        
        [$startDate, $endDate] = $this->getPeriodDates($period, Carbon::now(), $year, $month, $week);
        [$prevStartDate, $prevEndDate] = $this->getPreviousPeriodDates($period, $startDate, $endDate, $year, $month, $week);
        
        // Gestion du cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);
        $cacheKey = "dealer_kpi_{$organizationId}_{$period}_" . $startDate->format('Y-m-d') . "_" . $endDate->format('Y-m-d');
        
        if ($cacheEnabled) {
            $kpiData = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $startDate, $endDate, $prevStartDate, $prevEndDate) {
                $currentKPI = $this->calculateKPI($organizationId, $startDate, $endDate);
                $previousKPI = $this->calculateKPI($organizationId, $prevStartDate, $prevEndDate);
                return $this->addComparisons($currentKPI, $previousKPI);
            });
        } else {
            $currentKPI = $this->calculateKPI($organizationId, $startDate, $endDate);
            $previousKPI = $this->calculateKPI($organizationId, $prevStartDate, $prevEndDate);
            $kpiData = $this->addComparisons($currentKPI, $previousKPI);
        }
        
        return response()->json([
            'kpi' => $kpiData,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Récupérer uniquement l'évolution
     */
    public function getEvolutionData(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $period = $request->input('period', 'month');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        
        [$startDate, $endDate] = $this->getPeriodDates($period, Carbon::now(), $year, $month, $week);
        
        // Gestion du cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);
        $cacheKey = "dealer_evolution_{$organizationId}_{$period}_" . $startDate->format('Y-m-d') . "_" . $endDate->format('Y-m-d');
        
        if ($cacheEnabled) {
            $evolution = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $period, $startDate, $endDate) {
                return $this->getEvolution($organizationId, $period, $startDate, $endDate);
            });
        } else {
            $evolution = $this->getEvolution($organizationId, $period, $startDate, $endDate);
        }
        
        return response()->json([
            'evolution' => $evolution,
        ]);
    }

    /**
     * Récupérer uniquement les top PDV
     */
    public function getTopPdv(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $period = $request->input('period', 'month');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        
        [$startDate, $endDate] = $this->getPeriodDates($period, Carbon::now(), $year, $month, $week);
        
        // Gestion du cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);
        $cacheKey = "dealer_top_pdv_{$organizationId}_{$period}_" . $startDate->format('Y-m-d') . "_" . $endDate->format('Y-m-d');
        
        if ($cacheEnabled) {
            $topPdv = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $startDate, $endDate) {
                return $this->getCommissionsByPdv($organizationId, $startDate, $endDate);
            });
        } else {
            $topPdv = $this->getCommissionsByPdv($organizationId, $startDate, $endDate);
        }
        
        return response()->json([
            'commissions_by_pdv' => $topPdv,
        ]);
    }

    /**
     * Récupérer uniquement les stats GIVE
     */
    public function getGiveStats(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('dealer_owner')) {
            return response()->json(['message' => 'Accès refusé. Réservé aux dealer-owners.'], 403);
        }

        $organizationId = $user->organization_id;
        if (!$organizationId) {
            return response()->json(['message' => 'Aucune organisation associée.'], 400);
        }

        $period = $request->input('period', 'month');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        
        [$startDate, $endDate] = $this->getPeriodDates($period, Carbon::now(), $year, $month, $week);
        
        // Gestion du cache
        $cacheEnabled = SystemSetting::getValue('cache_dealer_analytics_enabled', true);
        $cacheTtl = (int) SystemSetting::getValue('cache_dealer_analytics_ttl', 60);
        $cacheKey = "dealer_give_stats_{$organizationId}_{$period}_" . $startDate->format('Y-m-d') . "_" . $endDate->format('Y-m-d');
        
        if ($cacheEnabled) {
            $giveStats = Cache::remember($cacheKey, $cacheTtl * 60, function () use ($organizationId, $startDate, $endDate) {
                return $this->getGiveNetworkStats($organizationId, $startDate, $endDate);
            });
        } else {
            $giveStats = $this->getGiveNetworkStats($organizationId, $startDate, $endDate);
        }
        
        return response()->json([
            'give_network_stats' => $giveStats,
        ]);
    }
}
