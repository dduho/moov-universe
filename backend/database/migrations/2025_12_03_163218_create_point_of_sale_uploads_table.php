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
        Schema::create('point_of_sale_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained()->onDelete('cascade');
            $table->string('upload_id'); // UUID from uploads table
            $table->string('file_path')->nullable(); // Chemin du fichier stocké
            $table->enum('type', ['id_document', 'photo', 'fiscal_document']);
            $table->timestamps();

            $table->index(['point_of_sale_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_of_sale_uploads');
    }
};
