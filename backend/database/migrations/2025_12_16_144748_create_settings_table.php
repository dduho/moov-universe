<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insérer le paramètre pour les notifications email
        DB::table('settings')->insert([
            'key' => 'mail_notifications_enabled',
            'value' => 'true',
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
        Schema::dropIfExists('settings');
    }
};
