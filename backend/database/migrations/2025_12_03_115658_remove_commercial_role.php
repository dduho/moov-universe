<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get dealer role ID
        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        
        if ($dealerRole) {
            // Convert all users with 'commercial' role to 'dealer' role
            $commercialRole = DB::table('roles')->where('name', 'commercial')->first();
            
            if ($commercialRole) {
                DB::table('users')
                    ->where('role_id', $commercialRole->id)
                    ->update(['role_id' => $dealerRole->id]);
                
                // Delete the commercial role
                DB::table('roles')->where('name', 'commercial')->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate commercial role
        $commercialRoleId = DB::table('roles')->insertGetId([
            'name' => 'commercial',
            'display_name' => 'Commercial',
            'description' => 'Commercial/Employé d\'un dealer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Note: Cannot reliably reverse the user role changes
        // as we don't know which dealers were originally commercials
    }
};
