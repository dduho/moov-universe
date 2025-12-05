<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'validated', 'revision_requested'])->default('pending');
            $table->text('admin_feedback')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Table pour les tags des PDV
        Schema::create('point_of_sale_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_of_sale_id')->constrained()->onDelete('cascade');
            $table->enum('tag', ['en_revision', 'taches_a_valider']);
            $table->timestamps();
            
            $table->unique(['point_of_sale_id', 'tag']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_of_sale_tags');
        Schema::dropIfExists('tasks');
    }
};
