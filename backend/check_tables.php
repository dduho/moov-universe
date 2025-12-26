<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');

echo "Tables dans la base:\n";
foreach ($tables as $table) {
    $tableName = array_values((array) $table)[0];
    echo "- $tableName\n";
}

// Vérifier la structure de point_of_sales
echo "\nStructure de point_of_sales:\n";
$columns = DB::select('DESCRIBE point_of_sales');
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}
