<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::firstOrCreate(
            ['key' => 'pdv_proximity_threshold'],
            [
                'value' => '300',
                'type' => 'integer',
                'description' => 'Distance minimale en mètres entre deux points de vente (défaut: 300m)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'gps_accuracy_max'],
            [
                'value' => '30',
                'type' => 'integer',
                'description' => 'Précision GPS maximale acceptée en mètres',
            ]
        );
    }
}
