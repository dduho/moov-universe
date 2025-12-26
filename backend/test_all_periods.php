<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

foreach(['day', 'week', 'month', 'quarter'] as $period) {
    $request = \Illuminate\Http\Request::create('/api/analytics/transactions', 'GET', [
        'period' => $period
    ]);

    $controller = new \App\Http\Controllers\TransactionAnalyticsController();
    $response = $controller->getAnalytics($request);

    $data = json_decode($response->getContent(), true);

    echo "\n=== Période: {$period} ===\n";
    echo "Date range: {$data['date_range']['start']} à {$data['date_range']['end']}\n";
    echo "Points d'évolution: " . count($data['evolution']) . "\n";
    
    if (count($data['evolution']) > 0) {
        echo "Premier point: {$data['evolution'][0]['label']} - " . number_format($data['evolution'][0]['chiffre_affaire'], 2) . " XOF\n";
        if (count($data['evolution']) > 1) {
            $last = end($data['evolution']);
            echo "Dernier point: {$last['label']} - " . number_format($last['chiffre_affaire'], 2) . " XOF\n";
        }
    }
}
