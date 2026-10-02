<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class SettingsAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    protected function setUp(): void
    {
        parent::setUp();

        SystemSetting::setValue('commission_rate_depot', '1.5', 'string', 'Taux');
        SystemSetting::setValue('pdv_proximity_threshold', '300', 'integer', 'Seuil');
    }

    public function test_settings_are_not_public(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
        $this->getJson('/api/settings/commission_rate_depot')->assertUnauthorized();
    }

    public function test_full_settings_list_is_admin_only(): void
    {
        $org = $this->organization();
        Sanctum::actingAs($this->user('dealer_owner', $org));

        $this->getJson('/api/settings')->assertForbidden();
    }

    public function test_authenticated_user_can_read_a_single_setting(): void
    {
        $org = $this->organization();
        Sanctum::actingAs($this->user('dealer_agent', $org));

        $this->getJson('/api/settings/pdv_proximity_threshold')
            ->assertOk()
            ->assertJson(['key' => 'pdv_proximity_threshold', 'value' => 300]);
    }

    public function test_admin_can_list_settings_and_cache_settings(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/settings')->assertOk()->assertJsonFragment(['key' => 'commission_rate_depot']);
        $this->getJson('/api/settings/cache')->assertOk();
    }
}
