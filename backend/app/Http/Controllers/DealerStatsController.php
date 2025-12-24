<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DealerStatsController extends Controller
{
    public function stats($id, Request $request)
    {
        $user = $request->user();
        $organization = Organization::findOrFail($id);

        if (!$user->isAdmin() && $user->organization_id !== $organization->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $request->get('period');
        $days = (int) ($request->get('days', 30));
        $mapped = [
            'j1' => 1,
            'j7' => 7,
            'j15' => 15,
            'j30' => 30,
            'j90' => 90,
        ];
        if (isset($mapped[strtolower((string) $period)])) {
            $days = $mapped[strtolower($period)];
        }
        if ($days <= 0) {
            $days = 30;
        }

        $now = Carbon::now();
        // Période = derniers N jours sans inclure aujourd'hui (ex : J-1 = hier uniquement)
        $end = $now->copy()->subDay()->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        $base = DB::table('pdv_transactions as t')
            ->join('point_of_sales as p', 'p.numero_flooz', '=', 't.pdv_numero')
            ->where('p.organization_id', $organization->id)
            ->whereBetween('t.transaction_date', [$start, $end]);

        $summary = (clone $base)->selectRaw('
            SUM(t.count_depot) as depot_count,
            SUM(t.sum_depot) as depot_amount,
            SUM(t.count_retrait) as retrait_count,
            SUM(t.sum_retrait) as retrait_amount,
            SUM(t.count_give_send) as give_sent_count,
            SUM(t.sum_give_send) as give_sent_amount,
            SUM(t.count_give_send_in_network) as give_sent_in_count,
            SUM(t.sum_give_send_in_network) as give_sent_in_amount,
            SUM(t.count_give_send_out_network) as give_sent_out_count,
            SUM(t.sum_give_send_out_network) as give_sent_out_amount,
            SUM(t.count_give_receive) as give_received_count,
            SUM(t.sum_give_receive) as give_received_amount,
            SUM(t.count_give_receive_in_network) as give_received_in_count,
            SUM(t.sum_give_receive_in_network) as give_received_in_amount,
            SUM(t.count_give_receive_out_network) as give_received_out_count,
            SUM(t.sum_give_receive_out_network) as give_received_out_amount,
            SUM(t.retrait_keycost) as ca,
            SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as dealer_commissions,
            SUM(t.pdv_depot_commission + t.pdv_retrait_commission) as pdv_commissions
        ')->first();

        $totalTransactions = ($summary->depot_count ?? 0)
            + ($summary->retrait_count ?? 0)
            + ($summary->give_sent_count ?? 0)
            + ($summary->give_received_count ?? 0);

        // PDV actifs uniques sur la période (au moins un dépôt ou retrait)
        $activePdvCount = (clone $base)
            ->where(function ($q) {
                $q->where('t.count_depot', '>', 0)
                  ->orWhere('t.count_retrait', '>', 0);
            })
            ->distinct('p.id')
            ->count('p.id');

        $activeBreakdown = [];
        foreach ([1, 7, 15, 30, 90] as $d) {
            $e = $now->copy()->subDay()->endOfDay();
            $s = $e->copy()->subDays($d - 1)->startOfDay();
            $count = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 'p.numero_flooz', '=', 't.pdv_numero')
                ->where('p.organization_id', $organization->id)
                ->whereBetween('t.transaction_date', [$s, $e])
                ->where(function ($q) {
                    $q->where('t.count_depot', '>', 0)
                      ->orWhere('t.count_retrait', '>', 0);
                })
                ->distinct('p.id')
                ->count('p.id');
            $activeBreakdown[] = ['days' => $d, 'count' => $count];
        }

        $chartRows = (clone $base)
            ->selectRaw('
                DATE(t.transaction_date) as date,
                SUM(t.sum_depot) as depot_amount,
                SUM(t.sum_retrait) as retrait_amount,
                SUM(t.retrait_keycost) as ca,
                SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as dealer_commissions,
                SUM(t.count_depot + t.count_retrait + t.count_give_send + t.count_give_receive) as tx_count,
                COUNT(DISTINCT CASE WHEN (t.count_depot > 0 OR t.count_retrait > 0) THEN p.id END) as active_pdvs
            ')
            ->groupBy(DB::raw('DATE(t.transaction_date)'))
            ->orderBy('date')
            ->get();

        $chart = [
            'labels' => $chartRows->pluck('date'),
            'depot' => $chartRows->pluck('depot_amount'),
            'retrait' => $chartRows->pluck('retrait_amount'),
            'ca' => $chartRows->pluck('ca'),
            'commissions_dealer' => $chartRows->pluck('dealer_commissions'),
            'tx_count' => $chartRows->pluck('tx_count'),
            'active_pdvs' => $chartRows->pluck('active_pdvs'),
        ];

        $topPdvs = (clone $base)
            ->select(
                'p.id',
                'p.nom_point',
                'p.numero_flooz',
                'p.region',
                'p.prefecture',
                DB::raw('SUM(t.retrait_keycost) as ca'),
                DB::raw('SUM(t.sum_depot) as depot_amount'),
                DB::raw('SUM(t.sum_retrait) as retrait_amount'),
                DB::raw('SUM(t.count_depot + t.count_retrait + t.count_give_send + t.count_give_receive) as tx_count')
            )
            ->groupBy('p.id', 'p.nom_point', 'p.numero_flooz', 'p.region', 'p.prefecture')
            ->orderByDesc(DB::raw('SUM(t.retrait_keycost)'))
            ->limit(10)
            ->get();

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
            ],
            'period' => [
                'days' => $days,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'ca' => (float) ($summary->ca ?? 0),
                'dealer_commissions' => (float) ($summary->dealer_commissions ?? 0),
                'pdv_commissions' => (float) ($summary->pdv_commissions ?? 0),
                'depot_count' => (int) ($summary->depot_count ?? 0),
                'depot_amount' => (float) ($summary->depot_amount ?? 0),
                'retrait_count' => (int) ($summary->retrait_count ?? 0),
                'retrait_amount' => (float) ($summary->retrait_amount ?? 0),
                'active_pdvs' => $activePdvCount,
            ],
            'transfers' => [
                'sent' => [
                    'count' => (int) ($summary->give_sent_count ?? 0),
                    'amount' => (float) ($summary->give_sent_amount ?? 0),
                    'in_count' => (int) ($summary->give_sent_in_count ?? 0),
                    'in_amount' => (float) ($summary->give_sent_in_amount ?? 0),
                    'out_count' => (int) ($summary->give_sent_out_count ?? 0),
                    'out_amount' => (float) ($summary->give_sent_out_amount ?? 0),
                ],
                'received' => [
                    'count' => (int) ($summary->give_received_count ?? 0),
                    'amount' => (float) ($summary->give_received_amount ?? 0),
                    'in_count' => (int) ($summary->give_received_in_count ?? 0),
                    'in_amount' => (float) ($summary->give_received_in_amount ?? 0),
                    'out_count' => (int) ($summary->give_received_out_count ?? 0),
                    'out_amount' => (float) ($summary->give_received_out_amount ?? 0),
                ],
            ],
            'actives' => [
                'selected_period' => $activePdvCount,
                'breakdown' => $activeBreakdown,
            ],
            'chart' => $chart,
            'top_pdvs' => $topPdvs,
        ]);
    }
}
