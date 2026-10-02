<?php

namespace Tests\Feature;

use App\Services\TransactionAggregates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class TransactionAggregatesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    private array $numeros = [];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->user('admin');
        $org = $this->organization();
        $this->numeros = [
            $this->pdv($org, $admin)->numero_flooz,
            $this->pdv($org, $admin, ['region' => 'KARA'])->numero_flooz,
            '22899999999', // PDV absent de point_of_sales
        ];

        // Février -> avril 2026, avec des jours manquants et des jours sans activité
        mt_srand(42);
        for ($d = Carbon::parse('2026-02-01'); $d->lte(Carbon::parse('2026-04-30')); $d->addDay()) {
            foreach ($this->numeros as $i => $numero) {
                if (($d->day + $i) % 7 === 0) {
                    continue; // pas de ligne ce jour-là
                }
                $inactive = ($d->day + $i) % 5 === 0;
                $this->transaction($numero, $d->toDateString(), [
                    'count_depot' => $inactive ? 0 : mt_rand(1, 40),
                    'count_retrait' => $inactive ? 0 : mt_rand(0, 30),
                    'sum_depot' => mt_rand(0, 900000) / 100,
                    'retrait_keycost' => mt_rand(0, 900000) / 100,
                    'dealer_depot_commission' => mt_rand(0, 150000) / 100,
                ]);
            }
        }

        Artisan::call('analytics:refresh-aggregates', ['--all' => true]);
    }

    public static function ranges(): array
    {
        return [
            'un jour' => ['2026-03-10', '2026-03-10'],
            'quelques jours (< 50 % du mois)' => ['2026-03-03', '2026-03-12'],
            'majorité du mois (> 50 %)' => ['2026-03-02', '2026-03-29'],
            'mois complet' => ['2026-03-01', '2026-03-31'],
            'à cheval sur deux mois' => ['2026-02-20', '2026-03-10'],
            'mois complets + bords' => ['2026-02-05', '2026-04-27'],
            'déborde de la période de données' => ['2026-01-15', '2026-05-15'],
            'fin de mois de février' => ['2026-02-15', '2026-02-28'],
        ];
    }

    /**
     * @dataProvider ranges
     */
    public function test_source_matches_raw_table(string $start, string $end): void
    {
        $columns = ['count_depot', 'count_retrait', 'sum_depot', 'retrait_keycost', 'dealer_depot_commission'];

        $expected = DB::table('pdv_transactions')
            ->whereBetween('transaction_date', [$start, $end])
            ->groupBy('pdv_numero')
            ->orderBy('pdv_numero')
            ->selectRaw('pdv_numero, COUNT(*) as days_count, SUM(count_depot > 0 OR count_retrait > 0) as active_days,
                SUM(count_depot) as count_depot, SUM(count_retrait) as count_retrait, SUM(sum_depot) as sum_depot,
                SUM(retrait_keycost) as retrait_keycost, SUM(dealer_depot_commission) as dealer_depot_commission')
            ->get()
            ->keyBy('pdv_numero');

        $actual = TransactionAggregates::table($start, $end, $columns)->orderBy('t.pdv_numero')->get()->keyBy('pdv_numero');

        $this->assertEquals($expected->keys()->all(), $actual->keys()->all());

        foreach ($expected as $numero => $row) {
            foreach (array_merge(['days_count', 'active_days'], $columns) as $column) {
                $this->assertEqualsWithDelta((float) $row->{$column}, (float) $actual[$numero]->{$column}, 0.001, "{$numero}.{$column} sur {$start} → {$end}");
            }
        }
    }

    public function test_daily_summary_totals_match_raw_table(): void
    {
        $raw = DB::table('pdv_transactions')
            ->groupBy('transaction_date')
            ->selectRaw('transaction_date, SUM(retrait_keycost) as ca, SUM(count_depot > 0 OR count_retrait > 0) as actifs')
            ->pluck('ca', 'transaction_date');

        $summary = DB::table('transaction_daily_summary')
            ->groupBy('transaction_date')
            ->selectRaw('transaction_date, SUM(retrait_keycost) as ca')
            ->pluck('ca', 'transaction_date');

        $this->assertEquals($raw->keys()->all(), $summary->keys()->all());
        foreach ($raw as $date => $ca) {
            $this->assertEqualsWithDelta((float) $ca, (float) $summary[$date], 0.001, $date);
        }

        // Le PDV inconnu est conservé (dealer/région NULL) pour que les totaux restent exacts
        $this->assertTrue(DB::table('transaction_daily_summary')->whereNull('organization_id')->exists());
    }

    public function test_refresh_month_reflects_reimported_data(): void
    {
        DB::table('pdv_transactions')
            ->where('pdv_numero', $this->numeros[0])
            ->where('transaction_date', '2026-03-10')
            ->update(['retrait_keycost' => 1000000]);

        TransactionAggregates::refreshMonth('2026-03-10');

        $monthly = DB::table('pdv_transaction_monthly')
            ->where('month', '2026-03-01')->where('pdv_numero', $this->numeros[0])->value('retrait_keycost');
        $raw = DB::table('pdv_transactions')
            ->where('pdv_numero', $this->numeros[0])->whereBetween('transaction_date', ['2026-03-01', '2026-03-31'])->sum('retrait_keycost');

        $this->assertEqualsWithDelta((float) $raw, (float) $monthly, 0.001);
    }

    public function test_pdv_filter_restricts_source(): void
    {
        $rows = TransactionAggregates::source('2026-02-01', '2026-04-30', ['retrait_keycost'], [$this->numeros[1]])->get();

        $this->assertCount(1, $rows);
        $this->assertSame($this->numeros[1], $rows->first()->pdv_numero);
    }

    public function test_unknown_column_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TransactionAggregates::source('2026-02-01', '2026-02-02', ['retrait_keycost; DROP TABLE users']);
    }
}
