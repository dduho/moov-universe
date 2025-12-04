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
        // Créer les nouveaux rôles
        DB::table('roles')->insert([
            [
                'name' => 'dealer_owner',
                'display_name' => 'Propriétaire Dealer',
                'description' => 'Propriétaire d\'un Dealer avec accès à tous les PDV de son organisation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'dealer_agent',
                'display_name' => 'Commercial Dealer',
                'description' => 'Commercial d\'un Dealer avec accès uniquement aux PDV qu\'il a créés',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Récupérer les IDs des rôles
        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        $dealerOwnerRole = DB::table('roles')->where('name', 'dealer_owner')->first();

        if ($dealerRole && $dealerOwnerRole) {
            // Convertir tous les utilisateurs 'dealer' existants en 'dealer_owner'
            DB::table('users')
                ->where('role_id', $dealerRole->id)
                ->update(['role_id' => $dealerOwnerRole->id]);

            // Supprimer l'ancien rôle 'dealer'
            DB::table('roles')->where('name', 'dealer')->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer le rôle 'dealer'
        DB::table('roles')->insert([
            'name' => 'dealer',
            'display_name' => 'Utilisateur Dealer',
            'description' => 'Utilisateur appartenant à un Dealer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        $dealerOwnerRole = DB::table('roles')->where('name', 'dealer_owner')->first();
        $dealerAgentRole = DB::table('roles')->where('name', 'dealer_agent')->first();

        if ($dealerRole) {
            // Convertir dealer_owner et dealer_agent en dealer
            if ($dealerOwnerRole) {
                DB::table('users')
                    ->where('role_id', $dealerOwnerRole->id)
                    ->update(['role_id' => $dealerRole->id]);
            }
            if ($dealerAgentRole) {
                DB::table('users')
                    ->where('role_id', $dealerAgentRole->id)
                    ->update(['role_id' => $dealerRole->id]);
            }
        }

        // Supprimer les nouveaux rôles
        DB::table('roles')->whereIn('name', ['dealer_owner', 'dealer_agent'])->delete();
    }
};
