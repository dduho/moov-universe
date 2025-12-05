<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Modifier l'enum pour inclure 'taches_completes'
        DB::statement("ALTER TABLE point_of_sale_tags MODIFY COLUMN tag ENUM('en_revision', 'taches_a_valider', 'taches_completes')");
    }

    public function down()
    {
        // Supprimer les tags 'taches_completes' avant de modifier l'enum
        DB::table('point_of_sale_tags')->where('tag', 'taches_completes')->delete();
        DB::statement("ALTER TABLE point_of_sale_tags MODIFY COLUMN tag ENUM('en_revision', 'taches_a_valider')");
    }
};
