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
        Schema::table('point_of_sale_uploads', function (Blueprint $table) {
            // Ajouter la colonne file_path si elle n'existe pas
            if (!Schema::hasColumn('point_of_sale_uploads', 'file_path')) {
                $table->string('file_path')->nullable()->after('upload_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_of_sale_uploads', function (Blueprint $table) {
            if (Schema::hasColumn('point_of_sale_uploads', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
