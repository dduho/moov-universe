<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Structure de la table pdv_transactions:" . PHP_EOL . PHP_EOL;

$columns = DB::select('SHOW COLUMNS FROM pdv_transactions');
foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})" . PHP_EOL;
}