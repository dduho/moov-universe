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
        Schema::create('outlook_import_history', function (Blueprint $table) {
            $table->id();
            $table->string('mailbox')->index(); // dduho@moov-africa.tg
            $table->string('message_id')->unique(); // Microsoft message ID (for idempotence)
            $table->string('filename');
            $table->string('subject');
            $table->integer('file_size_bytes')->nullable();
            $table->string('file_hash')->nullable(); // SHA256 pour déduplication
            $table->dateTime('received_at')->index();
            $table->enum('status', ['success', 'failed', 'partial', 'pending'])->default('pending');
            $table->integer('transactions_imported')->default(0);
            $table->integer('transactions_updated')->default(0);
            $table->integer('transactions_skipped')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->dateTime('last_retry_at')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['mailbox', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlook_import_history');
    }
};
