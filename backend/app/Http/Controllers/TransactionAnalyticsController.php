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
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');
        $now = Carbon::now();
        
        // Définir la fenêtre temporelle selon la période sélectionnée
        // Fenêtre principale affichée (ex: semaine en cours ou J-1)
        [$startDate, $endDate] = $this->getDisplayPeriodDates($period, $now, $year, $month, $week);
        [$prevStartDate, $prevEndDate] = $this->getPreviousPeriodDates($period, $startDate, $endDate, $year, $month, $week);

        // Fenêtre d'évolution/graph (ex: dernières 8 semaines pour le graphe semaine)
        [$evoStartDate, $evoEndDate] = $this->getEvolutionPeriodDates($period, $now, $startDate, $endDate);

        // Clé de cache unique par période et dates
        $cacheKey = "analytics_{$period}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        // Cache de 1 heure (3600s) - les données changent peu fréquemment
        // Le cache sera invalidé automatiquement lors de l'import de nouvelles transactions
        $data = Cache::tags(['cache_analytics', 'analytics', 'transactions'])->remember($cacheKey, 3600, function () use ($period, $startDate, $endDate, $prevStartDate, $prevEndDate, $evoStartDate, $evoEndDate, $now) {
            // Précharger le mois courant pour réutiliser sur week/day
            $monthBundle = null;
            if (in_array($period, ['month', 'week', 'day'])) {
                $monthBundle = $this->getMonthBundle($startDate->year, $startDate->month);
            }

            $currentKPI = $this->calculateKPIWithBundle($monthBundle, $startDate, $endDate);

            // Charger le bundle de la période précédente si on reste dans le même mois
            $prevBundle = null;
            if (in_array($period, ['month', 'week', 'day']) && $prevStartDate->isSameMonth($prevEndDate)) {
                $prevBundle = $this->getMonthBundle($prevStartDate->year, $prevStartDate->month);
            }
            $previousKPI = $this->calculateKPIWithBundle($prevBundle, $prevStartDate, $prevEndDate);
            
            // Récupérer la dernière date de transaction importée
            $lastTransactionDate = PdvTransaction::max('transaction_date');
            
            return [
                'period' => $period,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'last_import_date' => $lastTransactionDate ? Carbon::parse($lastTransactionDate)->format('Y-m-d') : null,
                'kpi' => $this->addComparisons($currentKPI, $previousKPI),
                'top_pdv' => $this->getTopPDV($startDate, $endDate, 10),
                'top_dealers' => $this->getTopDealers($startDate, $endDate, 10),
                'evolution' => $this->getEvolution($period, $evoStartDate, $evoEndDate),
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
     * Calculer les KPI en réutilisant un bundle mensuel si disponible (évite une requête complète)
     */
    private function calculateKPIWithBundle($monthBundle, Carbon $startDate, Carbon $endDate)
    {
        if ($monthBundle && $startDate->isSameMonth($endDate)) {
            return $this->aggregateRangeFromBundle($monthBundle, $startDate, $endDate);
        }

        return $this->calculateKPI($startDate, $endDate);
    }

    /**
     * Récupérer ou construire le bundle du mois (agrégats quotidiens + total mois)
     */
    private function getMonthBundle(int $year, int $month)
    {
        $cacheKey = "month_bundle_{$year}_{$month}";

        return Cache::tags(['cache_analytics', 'analytics', 'transactions'])->remember($cacheKey, 3600, function () use ($year, $month) {
            $monthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth()->endOfDay();

            $rows = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->selectRaw('
                    DATE(transaction_date) as d,
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
                ->groupBy('d')
                ->get();

            $daily = [];

            foreach ($rows as $row) {
                $day = $row->d;
                $daily[$day] = $this->buildKpiArray($row);
            }

            return [
                'year' => $year,
                'month' => $month,
                'daily' => $daily,
                'month_total' => null, // calculé à la volée pour assurer l'unicité des PDV actifs
            ];
        });
    }

    private function aggregateRangeFromBundle(array $bundle, Carbon $startDate, Carbon $endDate)
    {
        $daily = $bundle['daily'] ?? [];
        $agg = $this->emptyKpiArray();

        $cursor = $startDate->copy();
        while ($cursor <= $endDate) {
            $key = $cursor->format('Y-m-d');
            if (isset($daily[$key])) {
                $agg = $this->sumKpi($agg, $daily[$key]);
            }
            $cursor->addDay();
        }

        // Recalculer les PDV actifs en s'assurant de l'unicité sur la période
        $agg['pdv_actifs'] = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where(function($q) {
                $q->where('count_depot', '>', 0)
                  ->orWhere('count_retrait', '>', 0);
            })
            ->distinct('pdv_numero')
            ->count('pdv_numero');

        return $agg;
    }

    private function emptyKpiArray(): array
    {
        return [
            'chiffre_affaire' => 0,
            'total_transactions' => 0,
            'volume_total' => 0,
            'pdv_actifs' => 0,
            'transactions_detail' => [
                'depots' => ['count' => 0, 'amount' => 0, 'average' => 0],
                'retraits' => ['count' => 0, 'amount' => 0, 'average' => 0],
                'transfers_envoyes' => ['count' => 0, 'amount' => 0],
                'transfers_recus' => ['count' => 0, 'amount' => 0],
            ],
            'commissions' => [
                'pdv' => 0,
                'dealers' => 0,
                'total' => 0,
            ],
        ];
    }

    private function buildKpiArray($row): array
    {
        return [
            'chiffre_affaire' => round($row->total_ca ?? 0, 2),
            'total_transactions' => $row->total_transactions ?? 0,
            'volume_total' => round($row->total_volume ?? 0, 2),
            'pdv_actifs' => $row->pdv_actifs ?? 0,
            'transactions_detail' => [
                'depots' => [
                    'count' => $row->total_depot_count ?? 0,
                    'amount' => round($row->total_depot_amount ?? 0, 2),
                    'average' => ($row->total_depot_count ?? 0) > 0
                        ? round(($row->total_depot_amount ?? 0) / $row->total_depot_count, 2)
                        : 0,
                ],
                'retraits' => [
                    'count' => $row->total_retrait_count ?? 0,
                    'amount' => round($row->total_retrait_amount ?? 0, 2),
                    'average' => ($row->total_retrait_count ?? 0) > 0
                        ? round(($row->total_retrait_amount ?? 0) / $row->total_retrait_count, 2)
                        : 0,
                ],
                'transfers_envoyes' => [
                    'count' => $row->total_transfers_sent ?? 0,
                    'amount' => round($row->total_transfers_sent_amount ?? 0, 2),
                ],
                'transfers_recus' => [
                    'count' => $row->total_transfers_received ?? 0,
                    'amount' => round($row->total_transfers_received_amount ?? 0, 2),
                ],
            ],
            'commissions' => [
                'pdv' => round($row->total_commission_pdv ?? 0, 2),
                'dealers' => round($row->total_commission_dealers ?? 0, 2),
                'total' => round(($row->total_commission_pdv ?? 0) + ($row->total_commission_dealers ?? 0), 2),
            ],
        ];
    }

    private function sumKpi(array $base, array $addition): array
    {
        $base['chiffre_affaire'] += $addition['chiffre_affaire'];
        $base['total_transactions'] += $addition['total_transactions'];
        $base['volume_total'] += $addition['volume_total'];
        $base['pdv_actifs'] += $addition['pdv_actifs'];

        $base['transactions_detail']['depots']['count'] += $addition['transactions_detail']['depots']['count'];
        $base['transactions_detail']['depots']['amount'] += $addition['transactions_detail']['depots']['amount'];
        $base['transactions_detail']['retraits']['count'] += $addition['transactions_detail']['retraits']['count'];
        $base['transactions_detail']['retraits']['amount'] += $addition['transactions_detail']['retraits']['amount'];
        $base['transactions_detail']['transfers_envoyes']['count'] += $addition['transactions_detail']['transfers_envoyes']['count'];
        $base['transactions_detail']['transfers_envoyes']['amount'] += $addition['transactions_detail']['transfers_envoyes']['amount'];
        $base['transactions_detail']['transfers_recus']['count'] += $addition['transactions_detail']['transfers_recus']['count'];
        $base['transactions_detail']['transfers_recus']['amount'] += $addition['transactions_detail']['transfers_recus']['amount'];

        $base['commissions']['pdv'] += $addition['commissions']['pdv'];
        $base['commissions']['dealers'] += $addition['commissions']['dealers'];
        $base['commissions']['total'] += $addition['commissions']['total'];

        // Recalculer les averages après somme
        $depotsCount = $base['transactions_detail']['depots']['count'];
        $retraitsCount = $base['transactions_detail']['retraits']['count'];
        $base['transactions_detail']['depots']['average'] = $depotsCount > 0
            ? round($base['transactions_detail']['depots']['amount'] / $depotsCount, 2)
            : 0;
        $base['transactions_detail']['retraits']['average'] = $retraitsCount > 0
            ? round($base['transactions_detail']['retraits']['amount'] / $retraitsCount, 2)
            : 0;

        return $base;
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
        if (in_array($period, ['month', 'quarter', 'historical_year'])) {
            // Pour mois, trimestre ou année complète: grouper par mois
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
            // Pour jour, mois, historical_month, historical_week: grouper par jour
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

        // Remplir les périodes manquantes avec des zéros
        return $this->fillMissingPeriods($evolution, $period, $startDate, $endDate);
    }

    /**
     * Remplir les périodes manquantes avec des valeurs à zéro
     */
    private function fillMissingPeriods($evolution, $period, $startDate, $endDate)
    {
        $data = $evolution->keyBy('period');
        $filled = collect();

        if ($period === 'day') {
            // Remplir jour par jour (tous les jours de la semaine)
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $key = $current->format('Y-m-d');
                $filled->push([
                    'period' => $key,
                    'label' => $this->formatPeriodLabel($key, $period),
                    'chiffre_affaire' => isset($data[$key]) ? round($data[$key]->chiffre_affaire, 2) : 0,
                    'volume' => isset($data[$key]) ? round($data[$key]->volume, 2) : 0,
                    'transactions' => isset($data[$key]) ? $data[$key]->transactions : 0,
                    'pdv_actifs' => isset($data[$key]) ? $data[$key]->pdv_actifs : 0,
                ]);
                $current->addDay();
            }
        } elseif ($period === 'week') {
            // Remplir les 8 dernières semaines
            $current = $endDate->copy()->subWeeks(7)->startOfWeek();
            for ($i = 0; $i < 8; $i++) {
                $key = $current->format('Y-m-d');
                $filled->push([
                    'period' => $key,
                    'label' => $this->formatPeriodLabel($key, $period),
                    'chiffre_affaire' => isset($data[$key]) ? round($data[$key]->chiffre_affaire, 2) : 0,
                    'volume' => isset($data[$key]) ? round($data[$key]->volume, 2) : 0,
                    'transactions' => isset($data[$key]) ? $data[$key]->transactions : 0,
                    'pdv_actifs' => isset($data[$key]) ? $data[$key]->pdv_actifs : 0,
                ]);
                $current->addWeek();
            }
        } elseif (in_array($period, ['month', 'quarter', 'historical_year'])) {
            // Remplir mois par mois
            $current = $startDate->copy()->startOfMonth();
            while ($current <= $endDate) {
                $key = $current->format('Y-m');
                $filled->push([
                    'period' => $key,
                    'label' => $this->formatPeriodLabel($key, $period),
                    'chiffre_affaire' => isset($data[$key]) ? round($data[$key]->chiffre_affaire, 2) : 0,
                    'volume' => isset($data[$key]) ? round($data[$key]->volume, 2) : 0,
                    'transactions' => isset($data[$key]) ? $data[$key]->transactions : 0,
                    'pdv_actifs' => isset($data[$key]) ? $data[$key]->pdv_actifs : 0,
                ]);
                $current->addMonth();
            }
        } else {
            // Pour les autres cas (historical_month, historical_week), remplir jour par jour
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $key = $current->format('Y-m-d');
                $filled->push([
                    'period' => $key,
                    'label' => $this->formatPeriodLabel($key, $period),
                    'chiffre_affaire' => isset($data[$key]) ? round($data[$key]->chiffre_affaire, 2) : 0,
                    'volume' => isset($data[$key]) ? round($data[$key]->volume, 2) : 0,
                    'transactions' => isset($data[$key]) ? $data[$key]->transactions : 0,
                    'pdv_actifs' => isset($data[$key]) ? $data[$key]->pdv_actifs : 0,
                ]);
                $current->addDay();
            }
        }

        return $filled;
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

    /**     * Ajouter les comparaisons avec la période précédente
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

        // Ajouter les comparaisons au niveau principal
        $currentKPI['comparison'] = [
            'chiffre_affaire' => $calculateChange(
                $currentKPI['chiffre_affaire'],
                $previousKPI['chiffre_affaire'] ?? 0
            ),
            'total_transactions' => $calculateChange(
                $currentKPI['total_transactions'],
                $previousKPI['total_transactions'] ?? 0
            ),
            'volume_total' => $calculateChange(
                $currentKPI['volume_total'],
                $previousKPI['volume_total'] ?? 0
            ),
            'pdv_actifs' => $calculateChange(
                $currentKPI['pdv_actifs'],
                $previousKPI['pdv_actifs'] ?? 0
            ),
        ];

        // Ajouter les comparaisons pour les détails de transactions
        if (isset($currentKPI['transactions_detail'])) {
            $currentKPI['transactions_detail']['depots']['comparison'] = $calculateChange(
                $currentKPI['transactions_detail']['depots']['count'],
                $previousKPI['transactions_detail']['depots']['count'] ?? 0
            );
            $currentKPI['transactions_detail']['retraits']['comparison'] = $calculateChange(
                $currentKPI['transactions_detail']['retraits']['count'],
                $previousKPI['transactions_detail']['retraits']['count'] ?? 0
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
                $startDate->copy()->subDay()->endOfDay()
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
     * Obtenir la fenêtre principale affichée selon la période (widget top)
     * - day : J-1
     * - week : semaine en cours (lun -> hier pour éviter les données partielles du jour)
     * - month/quarter : mois ou trimestre en cours jusqu'à J-1
     */
    private function getDisplayPeriodDates($period, Carbon $now, $year = null, $month = null, $week = null)
    {
        $yesterday = $now->copy()->subDay();
        $yesterdayEnd = $yesterday->copy()->endOfDay();

        return match ($period) {
            'day' => [
                $yesterday->copy()->startOfDay(),
                $yesterdayEnd
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $yesterdayEnd->lessThan($now->copy()->startOfWeek()) ? $now->copy()->endOfWeek()->endOfDay() : $yesterdayEnd
            ],
            'quarter' => [
                $now->copy()->startOfQuarter(),
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
            default => [
                $now->copy()->startOfMonth(),
                $yesterdayEnd
            ],
        };
    }

    /**
     * Fenêtre utilisée pour les graphiques/évolution (on garde l'historique)
     */
    private function getEvolutionPeriodDates($period, Carbon $now, Carbon $displayStart, Carbon $displayEnd)
    {
        return match ($period) {
            'day' => [
                $now->copy()->startOfMonth(),
                $displayEnd
            ],
            'week' => [
                $displayEnd->copy()->subWeeks(7)->startOfWeek(),
                $displayEnd
            ],
            default => [$displayStart, $displayEnd],
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
        $data = Cache::tags(['cache_analytics', 'analytics', 'transactions'])->remember($cacheKey, 3600, function () use ($year) {
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
