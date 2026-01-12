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

        // Cache settings for fraud detection
        SystemSetting::firstOrCreate(
            ['key' => 'cache_fraud_detection_enabled'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer le cache pour la détection de fraudes',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'cache_fraud_detection_ttl'],
            [
                'value' => '180',
                'type' => 'integer',
                'description' => 'Durée du cache pour la détection de fraudes en minutes (défaut: 180 min = 3h)',
            ]
        );

        // Cache settings for rentability analysis
        SystemSetting::firstOrCreate(
            ['key' => 'cache_rentability_enabled'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer le cache pour l\'analyse de rentabilité',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'cache_rentability_ttl'],
            [
                'value' => '240',
                'type' => 'integer',
                'description' => 'Durée du cache pour l\'analyse de rentabilité en minutes (défaut: 240 min = 4h)',
            ]
        );

        // Cache settings for map data
        SystemSetting::firstOrCreate(
            ['key' => 'cache_map_enabled'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer le cache pour les données de carte',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'cache_map_ttl'],
            [
                'value' => '30',
                'type' => 'integer',
                'description' => 'Durée du cache pour les données de carte en minutes (défaut: 30 min)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'cache_geolocation_enabled'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Activer le cache pour les données de géolocalisation',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'cache_geolocation_ttl'],
            [
                'value' => '60',
                'type' => 'integer',
                'description' => 'Durée du cache pour les données de géolocalisation en minutes (défaut: 60 min = 1h)',
            ]
        );

        // Rentability calculation parameters
        SystemSetting::firstOrCreate(
            ['key' => 'pdv_activation_cost'],
            [
                'value' => '50000',
                'type' => 'integer',
                'description' => 'Coût d\'activation moyen d\'un PDV en FCFA (défaut: 50 000 FCFA)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'commission_rate_depot'],
            [
                'value' => '1.0',
                'type' => 'float',
                'description' => 'Taux de commission sur les dépôts en pourcentage (défaut: 1.0%)',
            ]
        );

        SystemSetting::firstOrCreate(
            ['key' => 'operational_cost_per_pdv'],
            [
                'value' => '10000',
                'type' => 'integer',
                'description' => 'Coût opérationnel mensuel par PDV en FCFA (défaut: 10 000 FCFA/mois)',
            ]
        );
    }
}
