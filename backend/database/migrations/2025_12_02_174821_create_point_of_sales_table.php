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
        Schema::create('point_of_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');

            // Informations Dealer
            $table->integer('numero')->nullable(); // N° séquentiel
            $table->string('dealer_name');
            $table->string('numero_flooz');
            $table->string('shortcode')->nullable();

            // Informations PDV
            $table->string('nom_point');
            $table->string('profil')->nullable();
            $table->string('type_activite')->nullable();

            // Informations du Gérant
            $table->string('firstname');
            $table->string('lastname');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->string('id_description')->nullable();
            $table->string('id_number')->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('profession')->nullable();
            $table->enum('sexe_gerant', ['M', 'F'])->nullable();

            // Localisation Hiérarchique
            $table->enum('region', ['MARITIME', 'PLATEAUX', 'CENTRALE', 'KARA', 'SAVANES']);
            $table->string('prefecture');
            $table->string('commune')->nullable();
            $table->string('canton')->nullable();
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->text('localisation')->nullable();

            // Coordonnées GPS
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('gps_accuracy', 8, 2)->nullable();

            // Contacts
            $table->string('numero_proprietaire')->nullable();
            $table->string('autre_contact')->nullable();

            // Fiscalité
            $table->string('nif')->nullable();
            $table->string('regime_fiscal')->nullable();

            // Visibilité
            $table->string('support_visibilite')->nullable();
            $table->enum('etat_support', ['BON', 'MAUVAIS'])->nullable();

            // Autres
            $table->string('numero_cagnt')->nullable();

            $table->timestamp('validated_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_of_sales');
    }
};
