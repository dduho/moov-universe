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
        Schema::create('daily_analytics_cache', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            
            // KPI globaux
            $table->decimal('total_ca', 20, 2)->default(0);
            $table->bigInteger('total_transactions')->default(0);
            $table->decimal('total_volume', 20, 2)->default(0);
            $table->integer('pdv_actifs')->default(0);
            
            // Détails transactions
            $table->bigInteger('total_depots')->default(0);
            $table->decimal('total_depots_amount', 20, 2)->default(0);
            $table->bigInteger('total_retraits')->default(0);
            $table->decimal('total_retraits_amount', 20, 2)->default(0);
            $table->bigInteger('total_transfers')->default(0);
            $table->decimal('total_transfers_amount', 20, 2)->default(0);
            
            // Commissions
            $table->decimal('total_commission_pdv', 20, 2)->default(0);
            $table->decimal('total_commission_dealers', 20, 2)->default(0);
            
            $table->timestamps();
            
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_analytics_cache');
    }
};
