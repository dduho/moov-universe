<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('point_of_sales', 'nif_particulier')) {
                $table->string('nif_particulier')->nullable()->after('regime_fiscal');
            }
            if (!Schema::hasColumn('point_of_sales', 'commercial')) {
                $table->string('commercial')->nullable()->after('numero_cagnt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table) {
            $table->dropColumn(['nif_particulier', 'commercial']);
        });
    }
};
