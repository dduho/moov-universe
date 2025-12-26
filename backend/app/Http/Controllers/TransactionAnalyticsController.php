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
        
        // Cache de 15 minutes pour éviter les recalculs constants
        $data = Cache::remember($cacheKey, 900, function () use ($period, $startDate, $endDate) {
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
                COUNT(DISTINCT pdv_numero) as pdv_actifs,
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
            ->get()
            ->keyBy('numero_flooz');

        return $topPdv->map(function ($item) use ($pdvs) {
            $pdv = $pdvs[$item->pdv_numero] ?? null;
            
            return [
                'pdv_numero' => $item->pdv_numero,
                'nom_point' => $pdv ? $pdv->nom_point : 'Inconnu',
                'dealer_name' => $pdv ? $pdv->dealer_name : 'Non attribué',
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
        // Récupérer les stats par PDV puis grouper par dealer_name
        $dealerStats = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->whereNotNull('p.dealer_name')
            ->select('p.dealer_name')
            ->selectRaw('
                SUM(t.retrait_keycost) as chiffre_affaire,
                SUM(t.sum_depot + t.sum_retrait) as volume_total,
                SUM(t.count_depot + t.count_retrait) as total_transactions,
                COUNT(DISTINCT t.pdv_numero) as pdv_count
            ')
            ->groupBy('p.dealer_name')
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
        // Pour le graphique, on affiche toujours par jour pour avoir plus de points
        // Sauf pour trimestre où on groupe par semaine
        if ($period === 'quarter') {
            // Pour trimestre: grouper par semaine
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE_FORMAT(transaction_date, '%Y-W%u') as period,
                    SUM(retrait_keycost) as chiffre_affaire,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } else {
            // Pour jour, semaine, mois: grouper par jour
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    DATE(transaction_date) as period,
                    SUM(retrait_keycost) as chiffre_affaire,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions
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
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    /**
     * Formater le label de période
     */
    private function formatPeriodLabel($key, $period)
    {
        // Pour les semaines (format: 2025-W51)
        if (str_contains($key, '-W')) {
            [$year, $week] = explode('-W', $key);
            return "S$week $year";
        }
        
        // Pour les dates complètes (format: 2025-12-26)
        try {
            $date = Carbon::parse($key);
            
            // Format selon la période
            return match($period) {
                'day' => $date->format('d M'),          // 26 Dec
                'week' => $date->format('d M'),         // 26 Dec
                'month' => $date->format('d M'),        // 26 Dec
                'quarter' => $date->format('d M'),      // 26 Dec
                default => $date->format('d M Y'),
            };
        } catch (\Exception $e) {
            return $key;
        }
    }
}
