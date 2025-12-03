<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@moov.tg'],
                [
                    'name' => 'Admin Moov',
                    'password' => Hash::make('password'),
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
