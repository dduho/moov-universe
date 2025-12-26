<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Simuler une requête API
$request = \Illuminate\Http\Request::create('/api/analytics/transactions', 'GET', [
    'period' => 'month'
]);

$controller = new \App\Http\Controllers\TransactionAnalyticsController();
$response = $controller->getAnalytics($request);

$data = json_decode($response->getContent(), true);

echo "Période: {$data['period']}\n";
echo "Date range: {$data['date_range']['start']} à {$data['date_range']['end']}\n\n";

echo "Évolution (CA par jour):\n";
foreach($data['evolution'] as $e) {
    echo "  {$e['label']}: " . number_format($e['chiffre_affaire'], 2) . " XOF\n";
}

echo "\nTotal points: " . count($data['evolution']) . "\n";
