<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

/**
 * Vérifie que les endpoints analytiques basculés sur les tables d'agrégats répondent
 * et renvoient des montants cohérents avec les transactions insérées.
 */
class AnalyticsEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    private $admin;
    private $org;
    private $pdvs = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);

        $this->admin = $this->user('admin');
        $this->org = $this->organization(['name' => 'Dealer Analytics']);
        $this->pdvs[] = $this->pdv($this->org, $this->admin);
        $this->pdvs[] = $this->pdv($this->org, $this->admin, ['region' => 'KARA', 'latitude' => 9.6, 'longitude' => 1.1]);

        // 45 derniers jours : 1 000 FCFA de CA par jour et par PDV
        for ($d = Carbon::today()->subDays(45); $d->lt(Carbon::today()); $d->addDay()) {
            foreach ($this->pdvs as $pdv) {
                $this->transaction($pdv->numero_flooz, $d->toDateString(), [
                    'count_depot' => 10, 'count_retrait' => 5, 'sum_depot' => 50000, 'sum_retrait' => 20000,
                    'retrait_keycost' => 1000, 'dealer_depot_commission' => 100, 'pdv_depot_commission' => 50,
                ]);
            }
        }

        Artisan::call('analytics:refresh-aggregates', ['--all' => true]);
        Sanctum::actingAs($this->admin);
    }

    public function test_rentability_by_pdv_dealer_and_region(): void
    {
        $start = Carbon::today()->subDays(10)->toDateString();
        $end = Carbon::today()->subDay()->toDateString();

        $byPdv = $this->getJson("/api/rentability/analyze?start_date={$start}&end_date={$end}")->assertOk();
        $byPdv->assertJsonCount(2, 'data');
        $this->assertEqualsWithDelta(10000, $byPdv->json('data.0.total_ca'), 0.01);
        $this->assertSame(10, $byPdv->json('data.0.active_days'));

        $byDealer = $this->getJson("/api/rentability/analyze?start_date={$start}&end_date={$end}&group_by=dealer")->assertOk();
        $this->assertEqualsWithDelta(20000, $byDealer->json('data.0.total_ca'), 0.01);
        $this->assertSame(10, $byDealer->json('data.0.active_days'));
        $this->assertSame(2, $byDealer->json('data.0.pdv_count'));

        $byRegion = $this->getJson("/api/rentability/analyze?start_date={$start}&end_date={$end}&group_by=region")->assertOk();
        $byRegion->assertJsonCount(2, 'data');
    }

    public function test_dashboard_top_dealers_revenue(): void
    {
        $response = $this->getJson('/api/statistics/dashboard')->assertOk();

        $expectedYear = 0;
        for ($d = Carbon::today()->subDays(45); $d->lt(Carbon::today()); $d->addDay()) {
            if ($d->year === Carbon::today()->year) {
                $expectedYear += 2000;
            }
        }

        $this->assertEqualsWithDelta($expectedYear, $response->json('top_dealers.0.revenue'), 0.01);
        $this->assertEqualsWithDelta(2000, $response->json('top_dealers.0.revenue_yesterday'), 0.01);
    }

    public function test_transaction_analytics_periods(): void
    {
        foreach (['day', 'week', 'month', 'quarter'] as $period) {
            $this->getJson("/api/analytics/transactions?period={$period}")
                ->assertOk()
                ->assertJsonStructure(['kpi', 'evolution', 'top_pdv', 'top_dealers']);
        }

        $evolution = collect($this->getJson('/api/analytics/transactions?period=month')->json('evolution'));
        $this->assertEqualsWithDelta(
            45 * 2000,
            $evolution->sum('chiffre_affaire'),
            0.01
        );
        $this->assertSame(2, $evolution->last()['pdv_actifs']);
    }

    public function test_other_analytics_endpoints_respond(): void
    {
        $this->getJson('/api/analytics/monthly-revenue')->assertOk();
        $this->getJson('/api/analytics/insights')->assertOk();
        $this->getJson('/api/forecasting')->assertOk();
        $this->getJson('/api/forecasting?scope=region&entity_id=KARA')->assertOk()->assertJsonStructure(['high_potential_pdv', 'underperforming_pdv']);
        $this->getJson('/api/geolocation/pdv')->assertOk()->assertJsonCount(2, 'pdvs');
        $this->getJson('/api/geolocation/potential-zones')->assertOk();
    }

    public function test_fraud_detection_flags_split_deposits_and_spikes(): void
    {
        $suspect = $this->pdv($this->org, $this->admin, ['nom_point' => 'Suspect']);
        for ($d = Carbon::today()->subDays(20); $d->lt(Carbon::today()); $d->addDay()) {
            $isSpikeDay = $d->isSameDay(Carbon::today()->subDays(5));
            $this->transaction($suspect->numero_flooz, $d->toDateString(), [
                'count_depot' => $isSpikeDay ? 400 : 20, 'count_retrait' => 1,
                'sum_depot' => 40000, 'retrait_keycost' => 500,
            ]);
        }

        $start = Carbon::today()->subDays(30)->toDateString();
        $end = Carbon::today()->toDateString();
        $alerts = collect($this->getJson("/api/fraud-detection?start_date={$start}&end_date={$end}")->assertOk()->json('alerts'));

        $types = $alerts->where('pdv_id', $suspect->id)->pluck('type')->unique()->sort()->values()->all();
        $this->assertSame(['activity_spike', 'split_deposit_fraud'], $types);

        // Les PDV « normaux » ne remontent pas en dépôts fractionnés
        $this->assertCount(0, $alerts->where('type', 'split_deposit_fraud')->where('pdv_id', '!=', $suspect->id));

        // Périmètre dealer et PDV
        $this->getJson("/api/fraud-detection?scope=pdv&entity_id={$suspect->id}&start_date={$start}&end_date={$end}")
            ->assertOk()
            ->assertJsonPath('alerts.0.pdv_id', $suspect->id);
    }
}
