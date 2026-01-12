<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Structure de la table point_of_sales:" . PHP_EOL . PHP_EOL;

$columns = DB::select('SHOW COLUMNS FROM point_of_sales');
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})" . PHP_EOL;
}