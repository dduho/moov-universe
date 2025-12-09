<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Met à jour les utilisateurs existants pour ne pas forcer le changement de mot de passe
     */
    public function up(): void
    {
        // Les utilisateurs existants n'ont pas besoin de changer leur mot de passe
        DB::table('users')->update([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire pour le rollback
    }
};
