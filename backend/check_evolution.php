<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$now = Carbon::now();
$start = $now->copy()->startOfMonth();
$end = $now->copy()->endOfMonth();

echo "Période: {$start->format('Y-m-d')} à {$end->format('Y-m-d')}\n\n";

// Vérifier les données d'évolution
$evolution = DB::table('pdv_transactions')
    ->whereBetween('transaction_date', [$start, $end])
    ->selectRaw('
        DATE(transaction_date) as date,
        COUNT(*) as count,
        SUM(retrait_keycost) as ca
    ')
    ->groupBy('date')
    ->orderBy('date')
    ->get();

echo "Jours avec transactions dans le mois:\n";
foreach($evolution as $e) {
    echo "  {$e->date} - " . number_format($e->count) . " tx - CA: " . number_format($e->ca, 2) . " XOF\n";
}

echo "\nTotal: " . count($evolution) . " jours\n";
