<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PointOfSaleMapTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    public function test_map_returns_markers_with_expected_shape(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization(['name' => 'Dealer Carte']);
        $pdv = $this->pdv($org, $admin, ['latitude' => 6.18, 'longitude' => 1.22]);
        $this->pdv($org, $admin, ['latitude' => null, 'longitude' => null]); // sans GPS : exclu

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/point-of-sales/for-map')->assertOk();

        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $pdv->id);
        $response->assertJsonPath('0.organization.name', 'Dealer Carte');
        $this->assertEqualsWithDelta(6.18, $response->json('0.latitude'), 0.000001);
        $this->assertSame(
            ['id', 'organization_id', 'nom_point', 'numero_flooz', 'shortcode', 'profil', 'region', 'prefecture',
             'ville', 'quartier', 'status', 'latitude', 'longitude', 'organization'],
            array_keys($response->json('0'))
        );
    }

    public function test_map_query_count_does_not_grow_with_number_of_pdv(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        for ($i = 0; $i < 40; $i++) {
            $this->pdv($org, $admin);
        }

        Sanctum::actingAs($admin);
        DB::enableQueryLog();
        $this->getJson('/api/point-of-sales/for-map')->assertOk()->assertJsonCount(40);

        // Avant : 2 requêtes par PDV (tâches) ; maintenant un nombre fixe
        $this->assertLessThan(10, count(DB::getQueryLog()));
    }

    public function test_dealer_owner_only_sees_own_organization(): void
    {
        $admin = $this->user('admin');
        $mine = $this->organization();
        $other = $this->organization();
        $ownPdv = $this->pdv($mine, $admin);
        $this->pdv($other, $admin);

        Sanctum::actingAs($this->user('dealer_owner', $mine));

        $this->getJson('/api/point-of-sales/for-map')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownPdv->id);
    }

    public function test_dealer_agent_sees_created_and_assigned_pdv_only(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $agent = $this->user('dealer_agent', $org);
        $created = $this->pdv($org, $agent);
        $assigned = $this->pdv($org, $admin);
        $this->pdv($org, $admin); // ni créé ni assigné

        DB::table('tasks')->insert([
            'point_of_sale_id' => $assigned->id, 'assigned_to' => $agent->id, 'created_by' => $admin->id,
            'title' => 'Vérifier', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs($agent);

        $ids = collect($this->getJson('/api/point-of-sales/for-map')->assertOk()->json())->pluck('id')->sort()->values()->all();
        $this->assertSame([$created->id, $assigned->id], $ids);
    }

    public function test_map_cache_is_invalidated_when_a_pdv_changes(): void
    {
        config(['cache.default' => 'array']);
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['nom_point' => 'Avant']);

        Sanctum::actingAs($admin);
        $this->getJson('/api/point-of-sales/for-map')->assertJsonPath('0.nom_point', 'Avant');

        $pdv->update(['nom_point' => 'Après']);

        $this->getJson('/api/point-of-sales/for-map')->assertJsonPath('0.nom_point', 'Après');
    }

    public function test_large_json_responses_are_gzipped_when_accepted(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        for ($i = 0; $i < 60; $i++) {
            $this->pdv($org, $admin);
        }

        Sanctum::actingAs($admin);

        $response = $this->get('/api/point-of-sales/for-map', ['Accept-Encoding' => 'gzip, deflate', 'Accept' => 'application/json']);
        $response->assertOk()->assertHeader('Content-Encoding', 'gzip');
        $this->assertCount(60, json_decode(gzdecode($response->getContent()), true));

        $plain = $this->getJson('/api/point-of-sales/for-map');
        $this->assertFalse($plain->headers->has('Content-Encoding'));
    }
}
