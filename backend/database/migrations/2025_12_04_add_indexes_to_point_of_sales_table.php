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
        Schema::table('point_of_sales', function (Blueprint $table) {
            // Index sur les colonnes fréquemment filtrées
            $table->index('status'); // Filtrage par statut (pending, validated, rejected)
            $table->index('region'); // Filtrage par région
            $table->index('prefecture'); // Filtrage par préfecture
            $table->index('organization_id'); // Déjà foreign key mais besoin d'index explicite
            $table->index('created_at'); // Tri par date de création
            
            // Index composites pour les requêtes courantes
            $table->index(['status', 'created_at']); // Liste des PDV par statut triés par date
            $table->index(['organization_id', 'status']); // PDV d'une organisation par statut
            $table->index(['region', 'status']); // PDV d'une région par statut
            
            // Index pour la recherche
            $table->index('numero_flooz'); // Déjà unique mais utile pour recherche
            $table->index('shortcode'); // Déjà unique mais utile pour recherche
            $table->index('nom_point'); // Recherche par nom de point
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_of_sales', function (Blueprint $table) {
            // Suppression des index simples
            $table->dropIndex(['status']);
            $table->dropIndex(['region']);
            $table->dropIndex(['prefecture']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['created_at']);
            
            // Suppression des index composites
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['region', 'status']);
            
            // Suppression des index de recherche
            $table->dropIndex(['numero_flooz']);
            $table->dropIndex(['shortcode']);
            $table->dropIndex(['nom_point']);
        });
    }
};
