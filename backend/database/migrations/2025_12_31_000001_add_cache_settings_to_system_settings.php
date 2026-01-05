<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajout des paramètres de cache pour chaque widget avancé
        $settings = [
            ['key' => 'cache_network_optimization_enabled', 'value' => '1'],
            ['key' => 'cache_network_optimization_ttl', 'value' => '180'],
            ['key' => 'cache_risk_compliance_enabled', 'value' => '1'],
            ['key' => 'cache_risk_compliance_ttl', 'value' => '180'],
            ['key' => 'cache_advanced_geospatial_enabled', 'value' => '1'],
            ['key' => 'cache_advanced_geospatial_ttl', 'value' => '180'],
            ['key' => 'cache_offline_dashboard_enabled', 'value' => '1'],
            ['key' => 'cache_offline_dashboard_ttl', 'value' => '180'],
        ];
        foreach ($settings as $setting) {
            // N'insère que si la clé n'existe pas déjà
            if (!DB::table('system_settings')->where('key', $setting['key'])->exists()) {
                DB::table('system_settings')->insert($setting);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $keys = [
            'cache_network_optimization_enabled',
            'cache_network_optimization_ttl',
            'cache_risk_compliance_enabled',
            'cache_risk_compliance_ttl',
            'cache_advanced_geospatial_enabled',
            'cache_advanced_geospatial_ttl',
            'cache_offline_dashboard_enabled',
            'cache_offline_dashboard_ttl',
        ];
        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
