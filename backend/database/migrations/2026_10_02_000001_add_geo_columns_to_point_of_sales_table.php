<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stocke le résultat de la validation géographique (région déduite du GPS)
     * pour pouvoir filtrer les incohérences en SQL au lieu de recalculer
     * les polygones pour chaque PDV à chaque requête.
     */
    public function up(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table) {
            $table->string('geo_actual_region', 20)->nullable()->after('longitude');
            $table->boolean('geo_has_alert')->default(false)->after('geo_actual_region');
            $table->index('geo_has_alert');
        });

        // Remplit les colonnes pour les PDV existants
        Artisan::call('pdv:refresh-geo');
    }

    public function down(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table) {
            $table->dropIndex(['geo_has_alert']);
            $table->dropColumn(['geo_actual_region', 'geo_has_alert']);
        });
    }
};
