<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$dates = DB::table('pdv_transactions')
    ->selectRaw('DATE(transaction_date) as date, COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->limit(10)
    ->get();

echo "Dates avec transactions:\n";
foreach ($dates as $date) {
    echo $date->date . " - " . number_format($date->count) . " transactions\n";
}
