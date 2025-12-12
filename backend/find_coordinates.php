<?php

/**
 * Script pour vérifier les vraies coordonnées GPS d'Agoú-Gare
 * et tester différents points dans Plateaux
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeoValidationService;

echo "=== RECHERCHE DES COORDONNÉES D'AGOÚ-GARE ===\n\n";

$geoService = new GeoValidationService();

// Tester plusieurs points possibles dans la région Plateaux
$testPoints = [
    ['lat' => 6.85, 'lng' => 0.75, 'description' => 'Coordonnées estimées Agoú-Gare'],
    ['lat' => 6.90, 'lng' => 0.63, 'description' => 'Kpalimé (Plateaux)'],
    ['lat' => 6.95, 'lng' => 0.65, 'description' => 'Près de Kpalimé'],
    ['lat' => 7.00, 'lng' => 0.70, 'description' => 'Centre Plateaux'],
    ['lat' => 7.50, 'lng' => 0.90, 'description' => 'Centre du polygone Plateaux'],
    ['lat' => 6.85, 'lng' => 0.90, 'description' => 'Sud-Est Plateaux'],
    ['lat' => 7.20, 'lng' => 0.60, 'description' => 'Ouest Plateaux'],
];

foreach ($testPoints as $point) {
    $result = $geoService->getRegionFromCoordinates($point['lat'], $point['lng']);
    echo sprintf("%-35s (%.2f, %.2f) -> %s\n",
        $point['description'],
        $point['lng'],
        $point['lat'],
        $result ? $result['region'] . ' - ' . $result['name'] : 'null'
    );
}

echo "\n=== VÉRIFICATION DES BOUNDS PLATEAUX ===\n";
echo "Bounds Plateaux: minLat=6.80, maxLat=8.30, minLng=0.30, maxLng=1.50\n\n";

// Tester si les points sont dans les bounds
foreach ($testPoints as $point) {
    $inBounds = $point['lat'] >= 6.80 && $point['lat'] <= 8.30 &&
                $point['lng'] >= 0.30 && $point['lng'] <= 1.50;
    echo sprintf("%-35s -> %s bounds\n",
        $point['description'],
        $inBounds ? 'DANS les' : 'HORS des'
    );
}