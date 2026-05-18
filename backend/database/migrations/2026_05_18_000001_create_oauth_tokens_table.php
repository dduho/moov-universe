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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index(); // 'outlook', 'google', etc.
            $table->string('mailbox')->index(); // email address
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->datetime('access_token_expires_at')->nullable();
            $table->datetime('refresh_token_expires_at')->nullable();
            $table->json('scope')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->datetime('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'mailbox']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
