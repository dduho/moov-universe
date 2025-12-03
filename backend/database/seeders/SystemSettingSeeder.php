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
            ['key' => 'proximity_alert_distance'],
            [
                'value' => '300',
                'type' => 'integer',
                'description' => 'Distance d\'alerte de proximité entre PDV en mètres',
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
