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
                'name' => 'dealer_owner',
                'display_name' => 'Propriétaire Dealer',
                'description' => 'Propriétaire d\'un Dealer avec accès à tous les PDV de son organisation',
            ],
            [
                'name' => 'dealer_agent',
                'display_name' => 'Commercial Dealer',
                'description' => 'Commercial d\'un Dealer avec accès uniquement aux PDV qu\'il a créés',
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
