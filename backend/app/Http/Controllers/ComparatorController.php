<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PdvTransaction;
use App\Models\PointOfSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ComparatorController extends Controller
{
    /**
     * Comparer plusieurs entités (PDV, Dealers, ou Périodes)
     */
    public function compare(Request $request)
    {
        $request->validate([
            'type' => 'required|in:pdv,dealer,period',
            'entities' => 'required|array|min:2|max:4',
            'period' => 'required|in:day,week,month,quarter',
            'date' => 'nullable|date',
        ]);

        $type = $request->input('type');
        $entities = $request->input('entities');
        $period = $request->input('period');
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : Carbon::now();

        [$startDate, $endDate] = $this->getPeriodDates($period, $date);

        $cacheKey = "compare_{$type}_" . md5(json_encode($entities)) . "_{$period}_{$startDate->format('Y-m-d')}";
        
        $data = Cache::remember($cacheKey, 3600, function () use ($type, $entities, $startDate, $endDate) {
            return $this->performComparison($type, $entities, $startDate, $endDate);
        });

        return response()->json([
            'type' => $type,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'comparisons' => $data,
        ]);
    }

    /**
     * Effectuer la comparaison selon le type
     */
    private function performComparison($type, $entities, $startDate, $endDate)
    {
        switch ($type) {
            case 'pdv':
                return $this->comparePdvs($entities, $startDate, $endDate);
            case 'dealer':
                return $this->compareDealers($entities, $startDate, $endDate);
            case 'period':
                return $this->comparePeriods($entities, $startDate, $endDate);
            default:
                return [];
        }
    }

    /**
     * Comparer plusieurs PDV
     */
    private function comparePdvs($pdvIdentifiers, $startDate, $endDate)
    {
        $results = [];

        foreach ($pdvIdentifiers as $identifier) {
            // Récupérer le PDV par ID
            $pdv = PointOfSale::find($identifier);
            
            if (!$pdv) {
                continue; // Skip si PDV introuvable
            }

            // Utiliser numero_flooz pour les transactions
            $pdvNumero = $pdv->numero_flooz ?? $pdv->numero;
            
            if (!$pdvNumero) {
                continue; // Skip si pas de numéro
            }

            $stats = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdvNumero)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(retrait_keycost) as ca,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions,
                    SUM(count_depot) as depots,
                    SUM(count_retrait) as retraits,
                    SUM(pdv_depot_commission + pdv_retrait_commission) as commission
                ')
                ->first();

            // Évolution temporelle
            $evolution = DB::table('pdv_transactions')
                ->where('pdv_numero', $pdvNumero)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw('DATE(transaction_date) as date, SUM(retrait_keycost) as ca')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $results[] = [
                'id' => $pdvNumero,
                'name' => $pdv->nom_point,
                'info' => $pdv ? [
                    'dealer' => $pdv->organization->name ?? 'N/A',
                    'region' => $pdv->region,
                    'ville' => $pdv->ville,
                ] : null,
                'metrics' => [
                    'ca' => round($stats->ca ?? 0, 2),
                    'volume' => round($stats->volume ?? 0, 2),
                    'transactions' => $stats->transactions ?? 0,
                    'depots' => $stats->depots ?? 0,
                    'retraits' => $stats->retraits ?? 0,
                    'commission' => round($stats->commission ?? 0, 2),
                    'avg_transaction' => $stats->transactions > 0 
                        ? round($stats->volume / $stats->transactions, 2) 
                        : 0,
                ],
                'evolution' => $evolution->map(fn($e) => [
                    'date' => $e->date,
                    'value' => round($e->ca, 2),
                ]),
            ];
        }

        return $results;
    }

    /**
     * Comparer plusieurs dealers
     */
    private function compareDealers($dealerNames, $startDate, $endDate)
    {
        $results = [];

        foreach ($dealerNames as $dealerName) {
            $stats = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
                ->join('organizations as o', 'p.organization_id', '=', 'o.id')
                ->where('o.name', $dealerName)
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(t.retrait_keycost) as ca,
                    SUM(t.sum_depot + t.sum_retrait) as volume,
                    SUM(t.count_depot + t.count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN t.count_depot > 0 OR t.count_retrait > 0 THEN t.pdv_numero END) as pdv_actifs,
                    COUNT(DISTINCT t.pdv_numero) as pdv_total,
                    SUM(t.dealer_depot_commission + t.dealer_retrait_commission) as commission
                ')
                ->first();

            // Évolution temporelle
            $evolution = DB::table('pdv_transactions as t')
                ->join('point_of_sales as p', 't.pdv_numero', '=', 'p.numero_flooz')
                ->join('organizations as o', 'p.organization_id', '=', 'o.id')
                ->where('o.name', $dealerName)
                ->whereBetween('t.transaction_date', [$startDate, $endDate])
                ->selectRaw('DATE(t.transaction_date) as date, SUM(t.retrait_keycost) as ca')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $results[] = [
                'id' => $dealerName,
                'name' => $dealerName,
                'info' => [
                    'pdv_count' => $stats->pdv_total ?? 0,
                    'pdv_actifs' => $stats->pdv_actifs ?? 0,
                ],
                'metrics' => [
                    'ca' => round($stats->ca ?? 0, 2),
                    'volume' => round($stats->volume ?? 0, 2),
                    'transactions' => $stats->transactions ?? 0,
                    'commission' => round($stats->commission ?? 0, 2),
                    'ca_per_pdv' => $stats->pdv_actifs > 0 
                        ? round($stats->ca / $stats->pdv_actifs, 2) 
                        : 0,
                ],
                'evolution' => $evolution->map(fn($e) => [
                    'date' => $e->date,
                    'value' => round($e->ca, 2),
                ]),
            ];
        }

        return $results;
    }

    /**
     * Comparer plusieurs périodes
     */
    private function comparePeriods($periods, $startDate, $endDate)
    {
        $results = [];

        foreach ($periods as $periodSpec) {
            // Format attendu: "2025-12" ou "2024-Q4" ou "2025-W50"
            [$pStart, $pEnd] = $this->parsePeriodSpec($periodSpec);

            $stats = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$pStart, $pEnd])
                ->selectRaw('
                    SUM(retrait_keycost) as ca,
                    SUM(sum_depot + sum_retrait) as volume,
                    SUM(count_depot + count_retrait) as transactions,
                    COUNT(DISTINCT CASE WHEN count_depot > 0 OR count_retrait > 0 THEN pdv_numero END) as pdv_actifs
                ')
                ->first();

            // Évolution temporelle
            $evolution = DB::table('pdv_transactions')
                ->whereBetween('transaction_date', [$pStart, $pEnd])
                ->selectRaw('DATE(transaction_date) as date, SUM(retrait_keycost) as ca')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $results[] = [
                'id' => $periodSpec,
                'name' => $this->formatPeriodName($periodSpec),
                'info' => [
                    'start' => $pStart->format('Y-m-d'),
                    'end' => $pEnd->format('Y-m-d'),
                    'days' => $pStart->diffInDays($pEnd) + 1,
                ],
                'metrics' => [
                    'ca' => round($stats->ca ?? 0, 2),
                    'volume' => round($stats->volume ?? 0, 2),
                    'transactions' => $stats->transactions ?? 0,
                    'pdv_actifs' => $stats->pdv_actifs ?? 0,
                    'ca_per_day' => $pStart->diffInDays($pEnd) > 0 
                        ? round($stats->ca / ($pStart->diffInDays($pEnd) + 1), 2) 
                        : 0,
                ],
                'evolution' => $evolution->map(fn($e) => [
                    'date' => $e->date,
                    'value' => round($e->ca, 2),
                ]),
            ];
        }

        return $results;
    }

    /**
     * Parser une spécification de période
     */
    private function parsePeriodSpec($spec)
    {
        // Format: "2025-12" (mois), "2025-Q4" (trimestre), "2025-W50" (semaine)
        if (preg_match('/^(\d{4})-(\d{2})$/', $spec, $matches)) {
            // Mois
            $date = Carbon::createFromDate($matches[1], $matches[2], 1);
            return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
        } elseif (preg_match('/^(\d{4})-Q([1-4])$/', $spec, $matches)) {
            // Trimestre
            $year = $matches[1];
            $quarter = $matches[2];
            $month = ($quarter - 1) * 3 + 1;
            $date = Carbon::createFromDate($year, $month, 1);
            return [$date->copy()->startOfQuarter(), $date->copy()->endOfQuarter()];
        } elseif (preg_match('/^(\d{4})-W(\d{2})$/', $spec, $matches)) {
            // Semaine
            $date = Carbon::now()->setISODate($matches[1], $matches[2]);
            return [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];
        }

        // Par défaut: mois actuel
        return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
    }

    /**
     * Formater le nom d'une période
     */
    private function formatPeriodName($spec)
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $spec, $matches)) {
            $date = Carbon::createFromDate($matches[1], $matches[2], 1);
            return $date->locale('fr')->isoFormat('MMMM YYYY');
        } elseif (preg_match('/^(\d{4})-Q([1-4])$/', $spec, $matches)) {
            return "T{$matches[2]} {$matches[1]}";
        } elseif (preg_match('/^(\d{4})-W(\d{2})$/', $spec, $matches)) {
            return "Semaine {$matches[2]}, {$matches[1]}";
        }
        return $spec;
    }

    /**
     * Obtenir les dates de début et fin selon la période
     */
    private function getPeriodDates($period, $date)
    {
        switch ($period) {
            case 'day':
                return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            case 'week':
                return [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];
            case 'month':
                return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
            case 'quarter':
                return [$date->copy()->startOfQuarter(), $date->copy()->endOfQuarter()];
            default:
                return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
        }
    }

    /**
     * Rechercher des PDV par nom ou numéro
     */
    public function searchPdvs(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 20);

        $query = PointOfSale::query()
            ->where('status', 'approved');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'LIKE', "%{$search}%")
                  ->orWhere('nom_point', 'LIKE', "%{$search}%")
                  ->orWhere('nom_responsable', 'LIKE', "%{$search}%");
            });
        }

        $pdvs = $query->select('id', 'numero', 'nom_point', 'nom_responsable', 'organization_id')
                      ->with(['organization:id,name'])
                      ->orderBy('numero')
                      ->paginate($perPage);

        return response()->json($pdvs);
    }

    /**
     * Rechercher des dealers
     */
    public function searchDealers(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 20);

        $query = Organization::query();

        if (!empty($search)) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $dealers = $query->select('id', 'name')
                        ->orderBy('name')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'id' => $item->name,
                                'name' => $item->name,
                            ];
                        });

        // Paginate manually
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $total = $dealers->count();
        $items = $dealers->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ]);
    }
}
