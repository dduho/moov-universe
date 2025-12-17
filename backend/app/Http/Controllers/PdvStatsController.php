<?php

namespace App\Http\Controllers;

use App\Models\PointOfSale;
use App\Models\PdvTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdvStatsController extends Controller
{
    /**
     * Récupérer les statistiques d'un PDV
     */
    public function getStats($id)
    {
        $pdv = PointOfSale::findOrFail($id);
        
        // Récupérer toutes les transactions du PDV
        $transactions = PdvTransaction::where('pdv_numero', $pdv->numero_flooz)
            ->orderBy('transaction_date', 'desc')
            ->get();

        if ($transactions->isEmpty()) {
            return response()->json([
                'hasData' => false,
                'message' => 'Aucune donnée de transaction disponible pour ce PDV'
            ]);
        }

        // Calculer les statistiques globales
        $stats = [
            'hasData' => true,
            'pdv' => [
                'id' => $pdv->id,
                'nom_point' => $pdv->nom_point,
                'numero_flooz' => $pdv->numero_flooz,
            ],
            'summary' => $this->calculateSummary($transactions),
            'trends' => $this->calculateTrends($transactions),
            'commissions' => $this->calculateCommissions($transactions),
            'transfers' => $this->calculateTransfers($transactions),
            'timeline' => $this->getTimeline($transactions),
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
        $latest = $transactions->first();
        $average = [
            'depot_count' => $transactions->avg('count_depot'),
            'retrait_count' => $transactions->avg('count_retrait'),
            'depot_amount' => $transactions->avg('sum_depot'),
            'retrait_amount' => $transactions->avg('sum_retrait'),
        ];

        return [
            'latest_period' => [
                'date' => $latest->transaction_date->format('Y-m-d'),
                'depot_count' => $latest->count_depot,
                'retrait_count' => $latest->count_retrait,
                'depot_amount' => $latest->sum_depot,
                'retrait_amount' => $latest->sum_retrait,
            ],
            'average' => $average,
            'performance' => [
                'depot_count_vs_avg' => $average['depot_count'] > 0 
                    ? (($latest->count_depot - $average['depot_count']) / $average['depot_count'] * 100) 
                    : 0,
                'retrait_count_vs_avg' => $average['retrait_count'] > 0 
                    ? (($latest->count_retrait - $average['retrait_count']) / $average['retrait_count'] * 100) 
                    : 0,
            ],
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
}
