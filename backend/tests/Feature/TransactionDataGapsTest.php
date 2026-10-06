<?php

namespace Tests\Feature;

use App\Services\TransactionAggregates;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class TransactionDataGapsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    public function test_detects_missing_and_zero_data_ranges(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $numero = $this->pdv($org, $admin)->numero_flooz;

        // 01-05 : données normales. 06-08 : absent (vrai trou). 09-11 : lignes présentes mais CA=0
        // (fichier source vide, comme avril 2026). 12 : normal à nouveau.
        foreach (['2026-06-01', '2026-06-02', '2026-06-03', '2026-06-04', '2026-06-05'] as $d) {
            $this->transaction($numero, $d, ['retrait_keycost' => 1000, 'count_retrait' => 1]);
        }
        foreach (['2026-06-09', '2026-06-10', '2026-06-11'] as $d) {
            $this->transaction($numero, $d, ['retrait_keycost' => 0, 'count_depot' => 5]); // lignes vides
        }
        $this->transaction($numero, '2026-06-12', ['retrait_keycost' => 500]);

        TransactionAggregates::refreshDays('2026-06-01', '2026-06-12');

        Carbon::setTestNow('2026-06-13'); // "hier" = 06-12, dernier jour attendu dans la plage
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/transactions/data-gaps')->assertOk();

        $response->assertJsonPath('first_date', '2026-06-01');
        $response->assertJsonPath('last_date', '2026-06-12');
        $response->assertJsonCount(2, 'ranges');
        $response->assertJsonFragment(['type' => 'missing', 'start' => '2026-06-06', 'end' => '2026-06-08', 'days' => 3]);
        $response->assertJsonFragment(['type' => 'zero_data', 'start' => '2026-06-09', 'end' => '2026-06-11', 'days' => 3]);

        Carbon::setTestNow();
    }

    public function test_no_ranges_when_data_is_complete(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $numero = $this->pdv($org, $admin)->numero_flooz;

        $this->transaction($numero, '2026-06-01', ['retrait_keycost' => 1000]);
        $this->transaction($numero, '2026-06-02', ['retrait_keycost' => 1000]);
        TransactionAggregates::refreshDays('2026-06-01', '2026-06-02');

        Carbon::setTestNow('2026-06-03');
        Sanctum::actingAs($admin);

        $this->getJson('/api/transactions/data-gaps')
            ->assertOk()
            ->assertJsonPath('ranges', []);

        Carbon::setTestNow();
    }

    public function test_requires_admin(): void
    {
        $org = $this->organization();
        Sanctum::actingAs($this->user('dealer_agent', $org));

        $this->getJson('/api/transactions/data-gaps')->assertForbidden();
    }

    public function test_empty_dataset_returns_no_range(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/transactions/data-gaps')
            ->assertOk()
            ->assertJsonPath('first_date', null)
            ->assertJsonPath('ranges', []);
    }
}
