<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\PdvTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PdvStatsController extends Controller
{
    /**
     * Récupérer les statistiques d'un PDV avec filtres
     */
    public function getStats($id, Request $request)
    {
        $pdv = PointOfSale::findOrFail($id);
        
        // Paramètres de filtrage
        $period = $request->input('period', 'month'); // day, week, month (défaut: month)
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        
        // Définir la fenêtre temporelle selon la période sélectionnée
        $now = Carbon::now();
        [$startDate, $endDate] = match ($period) {
            'day' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()], // J-1
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // month par défaut
        };

        // Récupérer les transactions du PDV filtrées par période
        $transactionsQuery = PdvTransaction::where('pdv_numero', $pdv->numero_flooz)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc');
        
        $allTransactions = $transactionsQuery->get();

        // Toujours retourner des données pour un PDV existant, même sans transactions
        // Grouper par période selon le filtre
        $groupedTransactions = $this->groupByPeriod($allTransactions, $period);
        
        // Calculer les statistiques globales
        $stats = [
            'hasData' => true,
            'pdv' => [
                'id' => $pdv->id,
                'nom_point' => $pdv->nom_point,
                'numero_flooz' => $pdv->numero_flooz,
            ],
            'period' => $period,
            'summary' => $this->calculateSummary($allTransactions),
            'trends' => $this->calculateTrends($allTransactions),
            'commissions' => $this->calculateCommissions($allTransactions),
            'transfers' => $this->calculateTransfers($allTransactions),
            'performance' => $this->calculatePerformance($groupedTransactions),
            'charts' => $this->prepareChartData($groupedTransactions, $period),
            'timeline' => $this->getTimelinePaginated($groupedTransactions, $page, $perPage),
        ];

        return response()->json($stats);
    }

    /**
     * Calculer les statistiques globales
     */
    private function calculateSummary($transactions)
    {
        return [
            'total_transactions' => $transactions->sum('count_depot') + 
                                   $transactions->sum('count_retrait') +
                                   $transactions->sum('count_give_send') +
                                   $transactions->sum('count_give_receive'),
            'total_volume' => $transactions->sum('sum_depot') + 
                             $transactions->sum('sum_retrait'),
            // Chiffre d'affaires généré (RETRAIT_KEYCOST)
            'chiffre_affaire' => $transactions->sum('retrait_keycost'),
            'total_depot_count' => $transactions->sum('count_depot'),
            'total_depot_amount' => $transactions->sum('sum_depot'),
            'total_retrait_count' => $transactions->sum('count_retrait'),
            'total_retrait_amount' => $transactions->sum('sum_retrait'),
            'total_transfers_sent' => $transactions->sum('count_give_send'),
            'total_transfers_received' => $transactions->sum('count_give_receive'),
            'avg_depot' => $transactions->sum('count_depot') > 0 
                ? $transactions->sum('sum_depot') / $transactions->sum('count_depot') 
                : 0,
            'avg_retrait' => $transactions->sum('count_retrait') > 0 
                ? $transactions->sum('sum_retrait') / $transactions->sum('count_retrait') 
                : 0,
        ];
    }

    /**
     * Calculer les tendances (dernière période vs moyenne)
     */
    private function calculateTrends($transactions)
    {
        if ($transactions->isEmpty()) {
            return [
                'latest_period' => null,
                'latest_data' => [
                    'date' => null,
                    'depot_count' => 0,
                    'retrait_count' => 0,
                    'depot_amount' => 0,
                    'retrait_amount' => 0,
                ],
                'average' => [
                    'depot_count' => 0,
                    'retrait_count' => 0,
                    'depot_amount' => 0,
                    'retrait_amount' => 0,
                ],
                'depot_vs_average' => 0,
                'retrait_vs_average' => 0,
            ];
        }

        $latest = $transactions->first();
        $average = [
            'depot_count' => $transactions->avg('count_depot'),
            'retrait_count' => $transactions->avg('count_retrait'),
            'depot_amount' => $transactions->avg('sum_depot'),
            'retrait_amount' => $transactions->avg('sum_retrait'),
        ];

        return [
            'latest_period' => $latest->transaction_date->format('d M Y'),
            'latest_data' => [
                'date' => $latest->transaction_date->format('Y-m-d'),
                'depot_count' => $latest->count_depot,
                'retrait_count' => $latest->count_retrait,
                'depot_amount' => $latest->sum_depot,
                'retrait_amount' => $latest->sum_retrait,
            ],
            'average' => $average,
            'depot_vs_average' => $average['depot_amount'] > 0 
                ? (($latest->sum_depot - $average['depot_amount']) / $average['depot_amount'] * 100) 
                : 0,
            'retrait_vs_average' => $average['retrait_amount'] > 0 
                ? (($latest->sum_retrait - $average['retrait_amount']) / $average['retrait_amount'] * 100) 
                : 0,
        ];
    }

    /**
     * Calculer le total des commissions
     */
    private function calculateCommissions($transactions)
    {
        $totalPdvCommissions = $transactions->sum('pdv_depot_commission') + 
                               $transactions->sum('pdv_retrait_commission');
        $totalDealerCommissions = $transactions->sum('dealer_depot_commission') + 
                                  $transactions->sum('dealer_retrait_commission');

        return [
            'pdv' => [
                'total' => $totalPdvCommissions,
                'depot' => $transactions->sum('pdv_depot_commission'),
                'retrait' => $transactions->sum('pdv_retrait_commission'),
            ],
            'dealer' => [
                'total' => $totalDealerCommissions,
                'depot' => $transactions->sum('dealer_depot_commission'),
                'retrait' => $transactions->sum('dealer_retrait_commission'),
            ],
            'retenues' => [
                'pdv_total' => $transactions->sum('pdv_depot_retenue') + 
                              $transactions->sum('pdv_retrait_retenue'),
                'dealer_total' => $transactions->sum('dealer_depot_retenue') + 
                                 $transactions->sum('dealer_retrait_retenue'),
            ],
        ];
    }

    /**
     * Calculer les statistiques de transferts
     */
    private function calculateTransfers($transactions)
    {
        return [
            'sent' => [
                'total_count' => $transactions->sum('count_give_send'),
                'total_amount' => $transactions->sum('sum_give_send'),
                'in_network_count' => $transactions->sum('count_give_send_in_network'),
                'in_network_amount' => $transactions->sum('sum_give_send_in_network'),
                'out_network_count' => $transactions->sum('count_give_send_out_network'),
                'out_network_amount' => $transactions->sum('sum_give_send_out_network'),
            ],
            'received' => [
                'total_count' => $transactions->sum('count_give_receive'),
                'total_amount' => $transactions->sum('sum_give_receive'),
                'in_network_count' => $transactions->sum('count_give_receive_in_network'),
                'in_network_amount' => $transactions->sum('sum_give_receive_in_network'),
                'out_network_count' => $transactions->sum('count_give_receive_out_network'),
                'out_network_amount' => $transactions->sum('sum_give_receive_out_network'),
            ],
        ];
    }

    /**
     * Récupérer la timeline des transactions (pour les graphiques)
     */
    private function getTimeline($transactions)
    {
        return $transactions->sortBy('transaction_date')->map(function($transaction) {
            return [
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'depot_count' => $transaction->count_depot,
                'depot_amount' => $transaction->sum_depot,
                'retrait_count' => $transaction->count_retrait,
                'retrait_amount' => $transaction->sum_retrait,
                'pdv_commission' => $transaction->pdv_depot_commission + $transaction->pdv_retrait_commission,
                'dealer_commission' => $transaction->dealer_depot_commission + $transaction->dealer_retrait_commission,
                'transfers_sent' => $transaction->count_give_send,
                'transfers_received' => $transaction->count_give_receive,
            ];
        })->values();
    }

    /**
     * Grouper les transactions par période
     */
    private function groupByPeriod($transactions, $period)
    {
        return $transactions->groupBy(function($transaction) use ($period) {
            $date = $transaction->transaction_date;
            switch ($period) {
                case 'week':
                    return $date->format('Y') . '-W' . $date->format('W');
                case 'month':
                    return $date->format('Y-m');
                default: // day
                    return $date->format('Y-m-d');
            }
        })->map(function($group, $key) use ($period) {
            return [
                'period' => $key,
                'label' => $this->formatPeriodLabel($key, $period),
                'depot_count' => $group->sum('count_depot'),
                'depot_amount' => $group->sum('sum_depot'),
                'retrait_count' => $group->sum('count_retrait'),
                'retrait_amount' => $group->sum('sum_retrait'),
                'pdv_commission' => $group->sum('pdv_depot_commission') + $group->sum('pdv_retrait_commission'),
                'dealer_commission' => $group->sum('dealer_depot_commission') + $group->sum('dealer_retrait_commission'),
                'transfers_sent' => $group->sum('count_give_send'),
                'transfers_received' => $group->sum('count_give_receive'),
                'total_volume' => $group->sum('sum_depot') + $group->sum('sum_retrait'),
            ];
        });
    }

    /**
     * Formater le label de période
     */
    private function formatPeriodLabel($key, $period)
    {
        switch ($period) {
            case 'week':
                [$year, $week] = explode('-W', $key);
                return "Semaine $week, $year";
            case 'month':
                return \Carbon\Carbon::createFromFormat('Y-m', $key)->format('M Y');
            default:
                return \Carbon\Carbon::parse($key)->format('d M Y');
        }
    }

    /**
     * Timeline paginée
     */
    private function getTimelinePaginated($groupedTransactions, $page, $perPage)
    {
        $items = $groupedTransactions->sortKeysDesc()->values();
        $total = $items->count();
        $lastPage = ceil($total / $perPage);
        
        $paginatedItems = $items->slice(($page - 1) * $perPage, $perPage)->values();
        
        return [
            'data' => $paginatedItems,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'from' => ($page - 1) * $perPage + 1,
            'to' => min($page * $perPage, $total),
        ];
    }

    /**
     * Calculer les performances
     */
    private function calculatePerformance($groupedTransactions)
    {
        if ($groupedTransactions->isEmpty()) {
            return null;
        }

        $items = $groupedTransactions->sortKeysDesc()->values();
        $latest = $items->first();
        $volumes = $items->pluck('total_volume');
        
        return [
            'best_period' => $items->sortByDesc('total_volume')->first(),
            'worst_period' => $items->sortBy('total_volume')->first(),
            'average_volume' => $volumes->average(),
            'median_volume' => $volumes->median(),
            'consistency' => $this->calculateConsistency($volumes),
        ];
    }

    /**
     * Calculer la consistance (écart-type / moyenne)
     */
    private function calculateConsistency($values)
    {
        if ($values->isEmpty()) return 0;
        
        $mean = $values->average();
        if ($mean == 0) return 0;
        
        $variance = $values->map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        })->average();
        
        $stdDev = sqrt($variance);
        
        // Coefficient de variation (inversé pour que plus haut = plus consistant)
        return $mean > 0 ? max(0, 100 - ($stdDev / $mean * 100)) : 0;
    }

    /**
     * Préparer les données pour les graphiques
     */
    private function prepareChartData($groupedTransactions, $period)
    {
        $items = $groupedTransactions->sortKeys()->values();
        
        return [
            'labels' => $items->pluck('label'),
            'volumes' => [
                'labels' => $items->pluck('label'),
                'depot' => $items->pluck('depot_amount'),
                'retrait' => $items->pluck('retrait_amount'),
            ],
            'transactions' => [
                'labels' => $items->pluck('label'),
                'depot' => $items->pluck('depot_count'),
                'retrait' => $items->pluck('retrait_count'),
            ],
            'commissions' => [
                'labels' => $items->pluck('label'),
                'pdv' => $items->pluck('pdv_commission'),
                'dealer' => $items->pluck('dealer_commission'),
            ],
            'transfers' => [
                'labels' => $items->pluck('label'),
                'sent' => $items->pluck('transfers_sent'),
                'received' => $items->pluck('transfers_received'),
            ],
        ];
    }
}
