<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\PointOfSale;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait CreatesMoovData
{
    private int $sequence = 0;

    protected function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name], ['display_name' => $name, 'description' => $name]);
    }

    protected function organization(array $attributes = []): Organization
    {
        $n = ++$this->sequence;

        return Organization::create(array_merge([
            'name' => "Dealer {$n}",
            'code' => "D{$n}",
            'is_active' => true,
        ], $attributes));
    }

    protected function user(string $role = 'admin', ?Organization $organization = null, array $attributes = []): User
    {
        $n = ++$this->sequence;

        return User::create(array_merge([
            'name' => "User {$n}",
            'email' => "user{$n}@test.tg",
            'phone' => '2289' . str_pad((string) $n, 7, '0', STR_PAD_LEFT),
            'password' => 'password',
            'role_id' => $this->role($role)->id,
            'organization_id' => $organization?->id,
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes))->load('role');
    }

    protected function pdv(Organization $organization, User $creator, array $attributes = []): PointOfSale
    {
        $n = ++$this->sequence;

        return PointOfSale::create(array_merge([
            'organization_id' => $organization->id,
            'created_by' => $creator->id,
            'status' => 'validated',
            'dealer_name' => $organization->name,
            'numero_flooz' => '228' . str_pad((string) (90000000 + $n), 8, '0', STR_PAD_LEFT),
            'shortcode' => str_pad((string) $n, 6, '0', STR_PAD_LEFT),
            'nom_point' => "PDV {$n}",
            'profil' => 'DISTRO',
            'firstname' => 'Prenom',
            'lastname' => 'Nom',
            'region' => 'MARITIME',
            'prefecture' => 'Golfe',
            'commune' => 'Golfe 1',
            'ville' => 'Lomé',
            'quartier' => 'Bè',
            'latitude' => 6.17,
            'longitude' => 1.23,
            'numero_proprietaire' => '22891000000',
            'support_visibilite' => 'POTENCE',
            'numero_cagnt' => '12345678901',
        ], $attributes));
    }

    /**
     * Insère une ligne de transaction journalière (colonnes non précisées à 0).
     */
    protected function transaction(string $pdvNumero, string $date, array $metrics = []): void
    {
        DB::table('pdv_transactions')->insert(array_merge([
            'pdv_numero' => $pdvNumero,
            'transaction_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ], $metrics));
    }
}
