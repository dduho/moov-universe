<?php

namespace App\Http\Controllers;

use App\Models\PdvTransaction;
use App\Models\PointOfSale;
use App\Models\DailyAnalyticsCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionAnalyticsController extends Controller
{
    /**
     * Récupérer les analytics globales des transactions
     */
    public function getAnalytics(Request $request)
    {
        $period = $request->input('period', 'month'); // day, week, month, quarter
        $now = Carbon::now();
        
        // Définir la fenêtre temporelle selon la période sélectionnée
        [$startDate, $endDate] = $this->getPeriodDates($period, $now);

        // Clé de cache unique par période et dates
        $cacheKey = "analytics_{$period}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        // Cache de 1 heure (3600s) - les données changent peu fréquemment
        // Le cache sera invalidé automatiquement lors de l'import de nouvelles transactions
        $data = Cache::remember($cacheKey, 3600, function () use ($period, $startDate, $endDate) {
            return [
                'period' => $period,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'kpi' => $this->calculateKPI($startDate, $endDate),
                'top_pdv' => $this->getTopPDV($startDate, $endDate, 10),
                'top_dealers' => $this->getTopDealers($startDate, $endDate, 10),
                'evolution' => $this->getEvolution($period, $startDate, $endDate),
                'distribution' => $this->getDistribution($startDate, $endDate),
            ];
        });

        return response()->json($data);
    }

    /**
     * Calculer les KPI principaux avec requête SQL optimisée
     * Utilise le cache quotidien quand disponible pour les périodes > 1 jour
     */
    private function calculateKPI($startDate, $endDate)
    {
        // Pour une seule journée, utiliser le cache quotidien si disponible
        $diffInDays = $startDate->diffInDays($endDate);
        
        if ($diffInDays === 0) {
            $cached = DailyAnalyticsCache::whereDate('date', $startDate)->first();
            if ($cached) {
                return [
                    'chiffre_affaire' => (float) $cached->total_ca,
                    'total_transactions' => $cached->total_transactions,
                    'volume_total' => (float) $cached->total_volume,
                    'pdv_actifs' => $cached->pdv_actifs,
                    'transactions_detail' => [
                        'depots' => [
                            'count' => $cached->total_depots,
                            'amount' => (float) $cached->total_depots_amount,
                            'average' => $cached->total_depots > 0 
                                ? round($cached->total_depots_amount / $cached->total_depots, 2) 
                                : 0,
                        ],
                        'retraits' => [
                            'count' => $cached->total_retraits,
                            'amount' => (float) $cached->total_retraits_amount,
                            'average' => $cached->total_retraits > 0 
                                ? round($cached->total_retraits_amount / $cached->total_retraits, 2) 
                                : 0,
                        ],
                        'transfers_envoyes' => [
                            'count' => $cached->total_transfers,
                            'amount' => (float) $cached->total_transfers_amount,
                        ],
                        'transfers_recus' => [
                            'count' => 0,
                            'amount' => 0,
                        ],
                    ],
                    'commissions' => [
                        'pdv' => (float) $cached->total_commission_pdv,
                        'dealers' => (float) $cached->total_commission_dealers,
                        'total' => (float) ($cached->total_commission_pdv + $cached->total_commission_dealers),
                    ],
                ];
            }
        }

        // Sinon, calculer avec la requête SQL
        $kpi = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(retrait_keycost) as total_ca,
                SUM(count_depot + count_retrait) as total_transactions,
                SUM(sum_depot + sum_retrait) as total_volume,
                COUNT(DISTINCT CASE WHEN count_depot > 0 OR count_retrait > 0 THEN pdv_numero END) as pdv_actifs,
                SUM(count_depot) as total_depot_count,
                SUM(sum_depot) as total_depot_amount,
                SUM(count_retrait) as total_retrait_count,
                SUM(sum_retrait) as total_retrait_amount,
                SUM(count_give_send) as total_transfers_sent,
                SUM(sum_give_send) as total_transfers_sent_amount,
                SUM(count_give_receive) as total_transfers_received,
                SUM(sum_give_receive) as total_transfers_received_amount,
                SUM(pdv_depot_commission + pdv_retrait_commission) as total_commission_pdv,
                SUM(dealer_depot_commission + dealer_retrait_commission) as total_commission_dealers
            ')
            ->first();

        return [
            'chiffre_affaire' => round($kpi->total_ca ?? 0, 2),
            'total_transactions' => $kpi->total_transactions ?? 0,
            'volume_total' => round($kpi->total_volume ?? 0, 2),
            'pdv_actifs' => $kpi->pdv_actifs ?? 0,
            'transactions_detail' => [
                'depots' => [
                    'count' => $kpi->total_depot_count ?? 0,
                    'amount' => round($kpi->total_depot_amount ?? 0, 2),
                    'average' => ($kpi->total_depot_count ?? 0) > 0 
                        ? round($kpi->total_depot_amount / $kpi->total_depot_count, 2) 
                        : 0,
                ],
                'retraits' => [
                    'count' => $kpi->total_retrait_count ?? 0,
                    'amount' => round($kpi->total_retrait_amount ?? 0, 2),
                    'average' => ($kpi->total_retrait_count ?? 0) > 0 
                        ? round($kpi->total_retrait_amount / $kpi->total_retrait_count, 2) 
                        : 0,
                ],
                'transfers_envoyes' => [
                    'count' => $kpi->total_transfers_sent ?? 0,
                    'amount' => round($kpi->total_transfers_sent_amount ?? 0, 2),
                ],
                'transfers_recus' => [
                    'count' => $kpi->total_transfers_received ?? 0,
                    'amount' => round($kpi->total_transfers_received_amount ?? 0, 2),
                ],
            ],
            'commissions' => [
                'pdv' => round($kpi->total_commission_pdv ?? 0, 2),
                'dealers' => round($kpi->total_commission_dealers ?? 0, 2),
                'total' => round(($kpi->total_commission_pdv ?? 0) + ($kpi->total_commission_dealers ?? 0), 2),
            ],
        ];
    }

    /**
     * Récupérer les meilleurs PDV par CA (optimisé avec SQL)
     */
    private function getTopPDV($startDate, $endDate, $limit = 10)
    {
        $topPdv = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('pdv_numero')
            ->selectRaw('
                SUM(retrait_keycost) as chiffre_affaire,
                SUM(sum_depot + sum_retrait) as volume_total,
                SUM(count_depot + count_retrait) as total_transactions
            ')
            ->groupBy('pdv_numero')
            ->orderByDesc('chiffre_affaire')
            ->limit($limit)
            ->get();

        // Récupérer les infos PDV en une seule requête
        $pdvNumeros = $topPdv->pluck('pdv_numero');
        $pdvs = PointOfSale::whereIn('numero_flooz', $pdvNumeros)
            ->with(['organization:id,name'])
            ->get()
            ->keyBy('numero_flooz');

        return $topPdv->map(function ($item) use ($pdvs) {
            $pdv = $pdvs[$item->pdv_numero] ?? null;
            
            return [
                'pdv_numero' => $item->pdv_numero,
                'nom_point' => $pdv ? $pdv->nom_point : 'Inconnu',
                'dealer_name' => $pdv ? ($pdv->organization->name ?? 'Non attribué') : 'Non attribué',
                'chiffre_affaire' => round($item->chiffre_affaire, 2),
                'volume_total' => round($item->volume_total, 2),
                'total_transactions' => $item->total_transactions,
                'moyenne_transaction' => $item->total_transactions > 0 
                    ? round($item->volume_total / $item->total_transactions, 2) 
                    : 0,
            ];
        })->values();
    }

    /**
     * Récupérer les meilleurs dealers par CA (optimisé avec SQL)
     */
    private function getTopDealers($startDate, $endDate, $limit = 10)
    {
        // Récupérer les stats par PDV puis grouper par organization.name
        $dealerStats = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->join('organizations as o', 'p.organization_id', '=', 'o.id')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->select('o.name as dealer_name')
            ->selectRaw('
                SUM(t.retrait_keycost) as chiffre_affaire,
                SUM(t.sum_depot + t.sum_retrait) as volume_total,
                SUM(t.count_depot + t.count_retrait) as total_transactions,
                COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_count
            ')
            ->groupBy('o.name')
            ->orderByDesc('chiffre_affaire')
            ->limit($limit)
            ->get();

        return $dealerStats->map(function ($item) {
            return [
                'dealer_name' => $item->dealer_name,
                'chiffre_affaire' => round($item->chiffre_affaire, 2),
                'volume_total' => round($item->volume_total, 2),
                'total_transactions' => $item->total_transactions,
                'pdv_count' => $item->pdv_count,
                'ca_par_pdv' => $item->pdv_count > 0 
                    ? round($item->chiffre_affaire / $item->pdv_count, 2) 
                    : 0,
            ];
        })->values();
    }

    /**
     * Obtenir l'évolution dans le temps (optimisé avec SQL)
     */
    private function getEvolution($period, $startDate, $endDate)
    {
        if ($period === 'quarter') {
            // Pour trimestre: grouper par mois (3 mois)
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE_FORMAT(transaction_date, '%Y-%m') as period,
                    SUM(retrait_keycost) as chiffre_affaire,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN count_depot > 0 OR count_retrait > 0 THEN pdv_numero END) as pdv_actifs
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } elseif ($period === 'week') {
            // Pour semaine: grouper par semaine (début de semaine)
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE_SUB(DATE(transaction_date), INTERVAL WEEKDAY(transaction_date) DAY) as period,
                    SUM(retrait_keycost) as chiffre_affaire,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN count_depot > 0 OR count_retrait > 0 THEN pdv_numero END) as pdv_actifs
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } else {
            // Pour jour et mois: grouper par jour
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE(transaction_date) as period,
                    SUM(retrait_keycost) as chiffre_affaire,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN count_depot > 0 OR count_retrait > 0 THEN pdv_numero END) as pdv_actifs
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        }

        return $evolution->map(function ($item) use ($period) {
            return [
                'period' => $item->period,
                'label' => $this->formatPeriodLabel($item->period, $period),
                'chiffre_affaire' => round($item->chiffre_affaire, 2),
                'volume' => round($item->volume, 2),
                'transactions' => $item->transactions,
                'pdv_actifs' => $item->pdv_actifs,
            ];
        });
    }

    /**
     * Distribution des transactions (optimisé avec SQL)
     */
    private function getDistribution($startDate, $endDate)
    {
        $distribution = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(count_depot) as total_depots,
                SUM(count_retrait) as total_retraits,
                SUM(count_give_send + count_give_receive) as total_transfers
            ')
            ->first();

        $total = ($distribution->total_depots ?? 0) + 
                 ($distribution->total_retraits ?? 0) + 
                 ($distribution->total_transfers ?? 0);

        return [
            'par_type' => [
                'depots' => [
                    'count' => $distribution->total_depots ?? 0,
                    'percentage' => $total > 0 
                        ? round((($distribution->total_depots ?? 0) / $total) * 100, 2) 
                        : 0,
                ],
                'retraits' => [
                    'count' => $distribution->total_retraits ?? 0,
                    'percentage' => $total > 0 
                        ? round((($distribution->total_retraits ?? 0) / $total) * 100, 2) 
                        : 0,
                ],
                'transfers' => [
                    'count' => $distribution->total_transfers ?? 0,
                    'percentage' => $total > 0 
                        ? round((($distribution->total_transfers ?? 0) / $total) * 100, 2) 
                        : 0,
                ],
            ],
        ];
    }

    /**
     * Obtenir les dates de début et fin selon la période
     */
    private function getPeriodDates($period, $now)
    {
        return match ($period) {
            'day' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'week' => [$now->copy()->subWeeks(8)->startOfDay(), $now->copy()->endOfDay()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Formater le label de période
     */
    private function formatPeriodLabel($key, $period)
    {
        // Pour les mois (format: 2025-12)
        if (preg_match('/^\d{4}-\d{2}$/', $key)) {
            $date = Carbon::createFromFormat('Y-m', $key);
            return $date->format('M Y');  // Dec 2025
        }
        
        // Pour les dates complètes (format: 2025-12-26)
        try {
            $date = Carbon::parse($key);
            
            // Format selon la période
            return match($period) {
                'day' => $date->format('d M'),                    // 26 Dec
                'week' => 'Semaine du ' . $date->format('d/m'),  // Semaine du 22/12
                'month' => $date->format('d M'),                  // 26 Dec
                'quarter' => $date->format('M Y'),                // Dec 2025 (pour les mois)
                default => $date->format('d M Y'),
            };
        } catch (\Exception $e) {
            return $key;
        }
    }

    /**
     * Récupérer le CA mensuel de l'année courante avec comparaisons
     */
    public function getMonthlyRevenue(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        
        // Clé de cache
        $cacheKey = "monthly_revenue_{$year}";
        
        // Cache de 1 heure
        $data = Cache::remember($cacheKey, 3600, function () use ($year) {
            $months = [];
            
            for ($month = 1; $month <= 12; $month++) {
                $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
                $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
                
                // CA du mois courant
                $currentCA = DB::table('pdv_transactions')
                    ->whereBetween('transaction_date', [$currentMonthStart, $currentMonthEnd])
                    ->sum('retrait_keycost');
                
                // CA du mois précédent
                $previousMonthStart = $currentMonthStart->copy()->subMonth()->startOfMonth();
                $previousMonthEnd = $currentMonthStart->copy()->subMonth()->endOfMonth();
                $previousCA = DB::table('pdv_transactions')
                    ->whereBetween('transaction_date', [$previousMonthStart, $previousMonthEnd])
                    ->sum('retrait_keycost');
                
                // CA du même mois l'année précédente
                $lastYearMonthStart = Carbon::create($year - 1, $month, 1)->startOfMonth();
                $lastYearMonthEnd = Carbon::create($year - 1, $month, 1)->endOfMonth();
                $lastYearCA = DB::table('pdv_transactions')
                    ->whereBetween('transaction_date', [$lastYearMonthStart, $lastYearMonthEnd])
                    ->sum('retrait_keycost');
                
                // Convertir null en 0
                $currentCA = $currentCA ?? 0;
                $previousCA = $previousCA ?? 0;
                $lastYearCA = $lastYearCA ?? 0;
                
                // Calculer les variations
                $vsLastMonth = $previousCA > 0 
                    ? round((($currentCA - $previousCA) / $previousCA) * 100, 1)
                    : ($currentCA > 0 ? 100 : 0);
                
                $vsLastYear = $lastYearCA > 0 
                    ? round((($currentCA - $lastYearCA) / $lastYearCA) * 100, 1)
                    : ($currentCA > 0 ? 100 : 0);
                
                $months[] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('M'),
                    'month_full_name' => Carbon::create($year, $month, 1)->format('F'),
                    'ca' => (float) $currentCA,
                    'previous_month_ca' => (float) $previousCA,
                    'last_year_ca' => (float) $lastYearCA,
                    'vs_last_month' => $vsLastMonth,
                    'vs_last_year' => $vsLastYear,
                    'has_data' => $currentCA > 0,
                ];
            }
            
            return [
                'year' => $year,
                'months' => $months,
                'total_ca' => array_sum(array_column($months, 'ca')),
            ];
        });
        
        return response()->json($data);
    }
}
