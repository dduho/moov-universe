<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMoovData;
use Tests\TestCase;

class PdvSearchTest extends TestCase
{
    use RefreshDatabase;
    use CreatesMoovData;

    private function ids(string $term): array
    {
        return collect($this->getJson('/api/point-of-sales?' . http_build_query(['search' => $term]))->assertOk()->json('data'))
            ->pluck('id')->sort()->values()->all();
    }

    public function test_numbers_are_found_as_displayed_with_spaces_or_as_stored(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $target = $this->pdv($org, $admin, ['shortcode' => '1311244', 'numero_flooz' => '22896777558', 'nom_point' => 'PHIL-ETS_AHOEFA_7558']);
        $this->pdv($org, $admin, ['shortcode' => '8932019', 'numero_flooz' => '22898197756']);
        Sanctum::actingAs($admin);

        // Valeur stockée, valeur affichée par l'interface (131 1244 / 228 96 77 75 58), et variantes de saisie
        foreach (['1311244', '131 1244', ' 131 1244 ', '311244', '22896777558', '228 96 77 75 58', '+228 96 77 75 58', '228-96-77-75-58'] as $term) {
            $this->assertSame([$target->id], $this->ids($term), "recherche « {$term} »");
        }

        $this->assertSame([], $this->ids('999 9999'));
    }

    public function test_name_search_treats_underscore_and_percent_literally(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $literal = $this->pdv($org, $admin, ['nom_point' => 'PHIL-ETS_AHOEFA_7558']);
        $this->pdv($org, $admin, ['nom_point' => 'PHIL-ETSXAHOEFAX7558']);
        $this->pdv($org, $admin, ['nom_point' => 'BOUTIQUE 100% FLOOZ']);
        Sanctum::actingAs($admin);

        $this->assertSame([$literal->id], $this->ids('ETS_AHOEFA'));
        $this->assertCount(1, $this->ids('100%'));
        $this->assertCount(0, $this->ids('%%%'));
    }

    public function test_name_containing_digits_is_still_searchable(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $pdv = $this->pdv($org, $admin, ['nom_point' => 'ETS 12 34 LOME']);
        Sanctum::actingAs($admin);

        $this->assertContains($pdv->id, $this->ids('12 34'));
    }

    public function test_search_by_dealer_name(): void
    {
        $admin = $this->user('admin');
        $phil = $this->organization(['name' => 'PHILDEALER']);
        $other = $this->organization(['name' => 'AUTRE']);
        $mine = $this->pdv($phil, $admin, ['nom_point' => 'Point A']);
        $this->pdv($other, $admin, ['nom_point' => 'Point B']);
        Sanctum::actingAs($admin);

        $this->assertSame([$mine->id], $this->ids('PHILDEALER'));
    }

    public function test_global_search_also_accepts_formatted_numbers(): void
    {
        $admin = $this->user('admin');
        $org = $this->organization();
        $target = $this->pdv($org, $admin, ['shortcode' => '1311244', 'numero_flooz' => '22896777558']);
        Sanctum::actingAs($admin);

        foreach (['131 1244', '228 96 77 75 58'] as $term) {
            $found = collect($this->postJsonOrGet($term))->pluck('id')->all();
            $this->assertContains($target->id, $found, "recherche globale « {$term} »");
        }
    }

    private function postJsonOrGet(string $term): array
    {
        $response = $this->getJson('/api/search?' . http_build_query(['query' => $term, 'type' => 'pdv']));
        $response->assertOk();

        return $response->json('pdv');
    }
}
