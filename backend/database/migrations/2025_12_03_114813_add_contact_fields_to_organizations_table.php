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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('contact_firstname')->nullable()->after('name');
            $table->string('contact_lastname')->nullable()->after('contact_firstname');
            $table->string('contact_phone', 20)->nullable()->after('contact_lastname');
            $table->string('contact_email')->nullable()->after('contact_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['contact_firstname', 'contact_lastname', 'contact_phone', 'contact_email']);
        });
    }
};
