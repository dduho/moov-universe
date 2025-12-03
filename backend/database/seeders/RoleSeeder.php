<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrateur Moov Money',
                'description' => 'Employé Moov Money avec accès complet à tous les Dealers et PDV',
            ],
            [
                'name' => 'dealer',
                'display_name' => 'Utilisateur Dealer',
                'description' => 'Utilisateur appartenant à un Dealer, accès limité aux PDV de son organisation uniquement',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
