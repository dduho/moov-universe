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
        // Sur une base vierge la table n'existe pas encore (créée en 2025_12_03) : elle inclut déjà ces colonnes
        if (!Schema::hasTable('point_of_sale_uploads')) {
            return;
        }

        Schema::table('point_of_sale_uploads', function (Blueprint $table) {
            // Ajouter la colonne mime_type si elle n'existe pas
            if (!Schema::hasColumn('point_of_sale_uploads', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('point_of_sale_uploads')) {
            return;
        }

        Schema::table('point_of_sale_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('point_of_sale_uploads', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
        });
    }
};
