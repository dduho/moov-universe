<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProximitySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::setValue(
            'pdv_proximity_threshold',
            300,
            'integer',
            'Distance minimale en mètres entre deux points de vente (défaut: 300m)'
        );
    }
}
