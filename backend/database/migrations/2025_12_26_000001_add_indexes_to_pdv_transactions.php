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
        Schema::table('pdv_transactions', function (Blueprint $table) {
            // Index sur pdv_numero pour les recherches par PDV
            $table->index('pdv_numero', 'idx_pdv_numero');
            
            // Index sur transaction_date pour les filtres par période
            $table->index('transaction_date', 'idx_transaction_date');
            
            // Index composite pour les requêtes combinant les deux
            $table->index(['pdv_numero', 'transaction_date'], 'idx_pdv_date');
            
            // Index sur transaction_date et retrait_keycost pour les analyses de CA
            $table->index(['transaction_date', 'retrait_keycost'], 'idx_date_ca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdv_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_pdv_numero');
            $table->dropIndex('idx_transaction_date');
            $table->dropIndex('idx_pdv_date');
            $table->dropIndex('idx_date_ca');
        });
    }
};
