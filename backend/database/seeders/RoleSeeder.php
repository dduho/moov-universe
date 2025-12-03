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
                'description' => 'Staff Moov avec accès complet à tous les PDV',
            ],
            [
                'name' => 'dealer',
                'display_name' => 'Dealer',
                'description' => 'Dealer gérant une organisation avec ses propres PDV',
            ],
            [
                'name' => 'commercial',
                'display_name' => 'Commercial',
                'description' => 'Commercial/Employé d\'un dealer',
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
