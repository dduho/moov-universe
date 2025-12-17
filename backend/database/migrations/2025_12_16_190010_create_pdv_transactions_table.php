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
        Schema::create('pdv_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('pdv_numero'); // Numéro Flooz du PDV
            $table->date('transaction_date'); // Date extraite de A6
            
            // Dépôts
            $table->integer('count_depot')->default(0);
            $table->decimal('sum_depot', 15, 2)->default(0);
            $table->decimal('pdv_depot_commission', 15, 2)->default(0);
            $table->decimal('dealer_depot_commission', 15, 2)->default(0);
            $table->decimal('pdv_depot_retenue', 15, 2)->default(0);
            $table->decimal('dealer_depot_retenue', 15, 2)->default(0);
            $table->decimal('depot_keycost', 15, 2)->default(0);
            $table->decimal('depot_customer_tva', 15, 2)->default(0);
            
            // Retraits
            $table->integer('count_retrait')->default(0);
            $table->decimal('sum_retrait', 15, 2)->default(0);
            $table->decimal('pdv_retrait_commission', 15, 2)->default(0);
            $table->decimal('dealer_retrait_commission', 15, 2)->default(0);
            $table->decimal('pdv_retrait_retenue', 15, 2)->default(0);
            $table->decimal('dealer_retrait_retenue', 15, 2)->default(0);
            $table->decimal('retrait_keycost', 15, 2)->default(0);
            $table->decimal('retrait_customer_tva', 15, 2)->default(0);
            
            // Transferts envoyés
            $table->integer('count_give_send')->default(0);
            $table->decimal('sum_give_send', 15, 2)->default(0);
            $table->integer('count_give_send_in_network')->default(0);
            $table->decimal('sum_give_send_in_network', 15, 2)->default(0);
            $table->integer('count_give_send_out_network')->default(0);
            $table->decimal('sum_give_send_out_network', 15, 2)->default(0);
            
            // Transferts reçus
            $table->integer('count_give_receive')->default(0);
            $table->decimal('sum_give_receive', 15, 2)->default(0);
            $table->integer('count_give_receive_in_network')->default(0);
            $table->decimal('sum_give_receive_in_network', 15, 2)->default(0);
            $table->integer('count_give_receive_out_network')->default(0);
            $table->decimal('sum_give_receive_out_network', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Index pour les recherches fréquentes
            $table->index('pdv_numero');
            $table->index('transaction_date');
            $table->unique(['pdv_numero', 'transaction_date']); // Une seule entrée par PDV par jour
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdv_transactions');
    }
};
