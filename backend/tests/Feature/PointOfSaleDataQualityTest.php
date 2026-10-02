<?php

namespace Tests\Feature;

use App\Models\PointOfSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PointOfSaleDataQualityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    public function test_geo_columns_are_computed_on_save(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();

        // Coordonnées de Lomé déclarées en région KARA : incohérence
        $wrong = $this->pdv($org, $admin, ['region' => 'KARA', 'latitude' => 6.17, 'longitude' => 1.23]);
        $right = $this->pdv($org, $admin, ['region' => 'MARITIME', 'latitude' => 6.17, 'longitude' => 1.23]);

        $this->assertTrue($wrong->fresh()->geo_has_alert);
        $this->assertSame('MARITIME', $wrong->fresh()->geo_actual_region);
        $this->assertFalse($right->fresh()->geo_has_alert);

        $wrong->update(['region' => 'MARITIME']);
        $this->assertFalse($wrong->fresh()->geo_has_alert);
    }

    public function test_geo_inconsistency_filter_and_geo_alerts_use_stored_flag(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $wrong = $this->pdv($org, $admin, ['region' => 'SAVANES']);
        $this->pdv($org, $admin);

        Sanctum::actingAs($admin);

        $this->getJson('/api/point-of-sales?geo_inconsistency=1')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $wrong->id);

        $this->getJson('/api/statistics/geo-alerts')
            ->assertOk()
            ->assertJsonPath('total_checked', 2)
            ->assertJsonPath('alerts_count', 1)
            ->assertJsonPath('alerts.0.id', $wrong->id);
    }

    public function test_refresh_geo_command_recomputes_flags(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['region' => 'KARA']);
        PointOfSale::query()->update(['geo_has_alert' => false, 'geo_actual_region' => null]);

        Artisan::call('pdv:refresh-geo');

        $this->assertTrue($pdv->fresh()->geo_has_alert);
    }

    public function test_incomplete_scope_matches_missing_required_fields_accessor(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();

        $cases = [
            'complet' => [],
            'quartier vide' => ['quartier' => ''],
            'placeholder N/A' => ['ville' => ' n/a '],
            'flooz 990' => ['numero_flooz' => '99000000001'],
            'cagnt 000' => ['numero_cagnt' => '00012345678'],
            'sans gps' => ['latitude' => null, 'longitude' => null],
            'gps à zéro' => ['latitude' => 0, 'longitude' => 0],
            'support absent' => ['support_visibilite' => null],
        ];

        $ids = [];
        foreach ($cases as $label => $attributes) {
            $ids[$label] = $this->pdv($org, $admin, $attributes)->id;
        }

        $incompleteIds = PointOfSale::query()->incomplete()->pluck('id')->sort()->values()->all();
        $accessorIds = PointOfSale::query()->get()
            ->filter(fn ($pdv) => !empty($pdv->missing_required_fields))
            ->pluck('id')->sort()->values()->all();

        $this->assertSame($accessorIds, $incompleteIds);
        $this->assertNotContains($ids['complet'], $incompleteIds);
        $this->assertCount(count($cases) - 1, $incompleteIds);
    }

    public function test_dashboard_lists_only_really_incomplete_pdv(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $this->pdv($org, $admin);
        $incomplete = $this->pdv($org, $admin, ['quartier' => null]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/statistics/dashboard')->assertOk();

        $response->assertJsonCount(1, 'incomplete_pdvs');
        $response->assertJsonPath('incomplete_pdvs.0.id', $incomplete->id);
        $response->assertJsonPath('incomplete_pdvs.0.missing_required_fields', ['Quartier']);
    }

    public function test_detail_still_exposes_computed_attributes(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin);

        Sanctum::actingAs($admin);

        $this->getJson("/api/point-of-sales/{$pdv->id}")
            ->assertOk()
            ->assertJsonPath('pdv.has_active_task', false)
            ->assertJsonPath('pdv.has_task_in_revision', false)
            ->assertJsonPath('pdv.geo_validation.has_alert', false)
            ->assertJsonPath('pdv.missing_required_fields', []);
    }

    public function test_list_does_not_run_queries_per_row(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        for ($i = 0; $i < 30; $i++) {
            $this->pdv($org, $admin);
        }

        Sanctum::actingAs($admin);
        \DB::enableQueryLog();
        $this->getJson('/api/point-of-sales?per_page=30')->assertOk()->assertJsonCount(30, 'data');

        $this->assertLessThan(12, count(\DB::getQueryLog()));
    }
}
