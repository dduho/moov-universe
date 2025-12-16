<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter le paramètre pour activer/désactiver les emails
        DB::table('system_settings')->insert([
            'key' => 'mail_notifications_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Activer ou désactiver toutes les notifications par email',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->where('key', 'mail_notifications_enabled')->delete();
    }
};
