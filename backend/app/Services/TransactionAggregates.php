<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agrégats des transactions PDV.
 *
 * - pdv_transaction_monthly : un mois par PDV.
 * - transaction_daily_summary : un jour par (dealer, région).
 *
 * source()/table() renvoient UNE LIGNE PAR PDV pour la plage [start, end] :
 * pdv_numero, days_count (jours présents dans les exports), active_days (jours avec
 * dépôt ou retrait) et les colonnes de métriques de pdv_transactions, sommées.
 *
 * Pour chaque mois touché par la plage on lit au plus la moitié des jours en détail :
 * - mois entièrement couvert : ligne de la table mensuelle ;
 * - mois couvert à moins de 50 % : jours couverts lus dans pdv_transactions ;
 * - mois couvert à plus de 50 % : ligne mensuelle MOINS les jours exclus.
 * Les sommes sont exactes quelle que soit la plage.
 */
class TransactionAggregates
{
    public const COUNT_COLUMNS = [
        'count_depot', 'count_retrait',
        'count_give_send', 'count_give_send_in_network', 'count_give_send_out_network',
        'count_give_receive', 'count_give_receive_in_network', 'count_give_receive_out_network',
    ];

    public const SUM_COLUMNS = [
        'sum_depot', 'pdv_depot_commission', 'dealer_depot_commission', 'pdv_depot_retenue', 'dealer_depot_retenue',
        'depot_keycost', 'depot_customer_tva',
        'sum_retrait', 'pdv_retrait_commission', 'dealer_retrait_commission', 'pdv_retrait_retenue', 'dealer_retrait_retenue',
        'retrait_keycost', 'retrait_customer_tva',
        'sum_give_send', 'sum_give_send_in_network', 'sum_give_send_out_network',
        'sum_give_receive', 'sum_give_receive_in_network', 'sum_give_receive_out_network',
    ];

    private const ACTIVE_CONDITION = '(count_depot > 0 OR count_retrait > 0)';

    public static function metricColumns(): array
    {
        return array_merge(self::COUNT_COLUMNS, self::SUM_COLUMNS);
    }

    /**
     * Sous-requête "une ligne par PDV" pour [start, end], aliasée (par défaut "t").
     */
    public static function table(string|\DateTimeInterface $start, string|\DateTimeInterface $end, ?array $columns = null, Builder|array|null $pdvNumeros = null, string $alias = 't'): Builder
    {
        return DB::query()->fromSub(self::source($start, $end, $columns, $pdvNumeros), $alias);
    }

    /**
     * @param array|null $columns Métriques nécessaires (null = toutes). Limiter les colonnes
     *                            réduit nettement la table temporaire de l'union.
     * @param Builder|array|null $pdvNumeros Restreint à ces numéros Flooz (sous-requête ou liste).
     */
    public static function source(string|\DateTimeInterface $start, string|\DateTimeInterface $end, ?array $columns = null, Builder|array|null $pdvNumeros = null): Builder
    {
        $columns ??= self::metricColumns();
        $unknown = array_diff($columns, self::metricColumns());
        if ($unknown) {
            throw new \InvalidArgumentException('Colonnes inconnues : ' . implode(', ', $unknown));
        }

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        $parts = [];

        for ($month = $start->copy()->startOfMonth(); $month->lte($end); $month->addMonthNoOverflow()) {
            $monthEnd = $month->copy()->endOfMonth()->startOfDay();
            $segStart = $start->gt($month) ? $start->copy() : $month->copy();
            $segEnd = $end->lt($monthEnd) ? $end->copy() : $monthEnd->copy();

            $covered = (int) $segStart->diffInDays($segEnd) + 1;
            $daysInMonth = $month->daysInMonth;

            if ($covered === $daysInMonth) {
                $parts[] = self::monthlyRows($month, $columns);
            } elseif ($covered * 2 <= $daysInMonth) {
                $parts[] = self::rawRows($segStart, $segEnd, 1, $columns);
            } else {
                $parts[] = self::monthlyRows($month, $columns);
                if ($segStart->gt($month)) {
                    $parts[] = self::rawRows($month, $segStart->copy()->subDay(), -1, $columns);
                }
                if ($segEnd->lt($monthEnd)) {
                    $parts[] = self::rawRows($segEnd->copy()->addDay(), $monthEnd, -1, $columns);
                }
            }
        }

        if (empty($parts)) {
            return self::rawRows($start, $start, 1, $columns)->whereRaw('1 = 0');
        }

        if ($pdvNumeros !== null) {
            foreach ($parts as $part) {
                $part->whereIn('pdv_numero', $pdvNumeros);
            }
        }

        $union = array_shift($parts);
        foreach ($parts as $part) {
            $union->unionAll($part);
        }

        $sums = array_map(fn ($c) => DB::raw("SUM(u.{$c}) as {$c}"), $columns);

        return DB::query()->fromSub($union, 'u')
            ->select(array_merge(
                ['u.pdv_numero', DB::raw('SUM(u.days_count) as days_count'), DB::raw('SUM(u.active_days) as active_days')],
                $sums
            ))
            ->groupBy('u.pdv_numero')
            ->havingRaw('SUM(u.days_count) > 0');
    }

    /**
     * Lignes détaillées agrégées par PDV ; $sign = -1 pour soustraire des jours d'une ligne mensuelle.
     */
    private static function rawRows(Carbon $start, Carbon $end, int $sign, array $columns): Builder
    {
        $sums = array_map(fn ($c) => DB::raw("{$sign} * SUM({$c}) as {$c}"), $columns);

        return DB::table('pdv_transactions')
            ->select(array_merge(
                ['pdv_numero', DB::raw("{$sign} * COUNT(*) as days_count"), DB::raw("{$sign} * SUM" . self::ACTIVE_CONDITION . ' as active_days')],
                $sums
            ))
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('pdv_numero');
    }

    private static function monthlyRows(Carbon $month, array $columns): Builder
    {
        return DB::table('pdv_transaction_monthly')
            ->select(array_merge(['pdv_numero', 'days_count', 'active_days'], $columns))
            ->where('month', $month->toDateString());
    }

    /**
     * Recalcule les agrégats mensuels d'un mois et les synthèses quotidiennes de ses jours.
     */
    public static function refreshMonth(string|\DateTimeInterface $anyDayOfMonth): void
    {
        $month = Carbon::parse($anyDayOfMonth)->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $metrics = self::metricColumns();
        $sums = implode(', ', array_map(fn ($c) => "SUM({$c})", $metrics));
        $columns = implode(', ', $metrics);

        DB::transaction(function () use ($month, $monthEnd, $sums, $columns) {
            DB::table('pdv_transaction_monthly')->where('month', $month->toDateString())->delete();

            DB::insert(
                "INSERT INTO pdv_transaction_monthly (month, pdv_numero, days_count, active_days, {$columns})
                 SELECT ?, pdv_numero, COUNT(*), SUM" . self::ACTIVE_CONDITION . ", {$sums}
                 FROM pdv_transactions
                 WHERE transaction_date BETWEEN ? AND ?
                 GROUP BY pdv_numero",
                [$month->toDateString(), $month->toDateString(), $monthEnd->toDateString()]
            );
        });

        self::refreshDays($month, $monthEnd);
    }

    /**
     * Recalcule transaction_daily_summary pour [start, end] (rattachement dealer/région actuel des PDV).
     */
    public static function refreshDays(string|\DateTimeInterface $start, string|\DateTimeInterface $end): void
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        $metrics = self::metricColumns();
        $sums = implode(', ', array_map(fn ($c) => "SUM(t.{$c})", $metrics));
        $columns = implode(', ', $metrics);

        DB::transaction(function () use ($start, $end, $sums, $columns) {
            DB::table('transaction_daily_summary')->whereBetween('transaction_date', [$start, $end])->delete();

            DB::insert(
                "INSERT INTO transaction_daily_summary (transaction_date, organization_id, region, pdv_count, pdv_actifs, {$columns})
                 SELECT t.transaction_date, p.organization_id, p.region, COUNT(*), SUM(t.count_depot > 0 OR t.count_retrait > 0), {$sums}
                 FROM pdv_transactions t
                 LEFT JOIN point_of_sales p ON p.numero_flooz = t.pdv_numero
                 WHERE t.transaction_date BETWEEN ? AND ?
                 GROUP BY t.transaction_date, p.organization_id, p.region",
                [$start, $end]
            );
        });
    }

    /**
     * Recalcule tous les mois présents dans pdv_transactions.
     */
    public static function refreshAll(?callable $onMonth = null): int
    {
        $months = DB::table('pdv_transactions')
            ->selectRaw("DISTINCT DATE_FORMAT(transaction_date, '%Y-%m-01') as m")
            ->orderBy('m')
            ->pluck('m');

        DB::table('pdv_transaction_monthly')->whereNotIn('month', $months)->delete();
        DB::table('transaction_daily_summary')
            ->whereRaw("DATE_FORMAT(transaction_date, '%Y-%m-01') NOT IN (" . ($months->isEmpty() ? "''" : implode(',', array_fill(0, $months->count(), '?'))) . ')', $months->all())
            ->delete();

        foreach ($months as $month) {
            self::refreshMonth($month);
            if ($onMonth) {
                $onMonth($month);
            }
        }

        return $months->count();
    }
}
