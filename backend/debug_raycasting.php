<?php

/**
 * Script de débogage détaillé pour l'algorithme Ray Casting
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeoValidationService;

echo "=== DÉBOGAGE DÉTAILLÉ DE L'ALGORITHME ===\n\n";

$geoService = new GeoValidationService();

// Fonction de débogage pour isPointInPolygon
function debugIsPointInPolygon($lat, $lng, $polygon) {
    echo "Test du point ($lng, $lat) dans le polygone:\n";
    $inside = false;
    $n = count($polygon);

    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $xi = $polygon[$i][0]; $yi = $polygon[$i][1];
        $xj = $polygon[$j][0]; $yj = $polygon[$j][1];

        echo "  Côté $i: ($xi, $yi) -> ($xj, $yj)\n";

        // Ignorer les côtés horizontaux (même latitude)
        if ($yi == $yj) {
            echo "    -> Côté horizontal ignoré\n";
            continue;
        }

        $condition1 = (($yi > $lat) !== ($yj > $lat));
        $condition2 = ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

        echo "    Condition 1 (($yi > $lat) != ($yj > $lat)): " . ($condition1 ? 'TRUE' : 'FALSE') . "\n";
        echo "    Condition 2 (intersection): " . ($condition2 ? 'TRUE' : 'FALSE') . "\n";

        if ($condition1 && $condition2) {
            $inside = !$inside;
            echo "    -> CROISEMENT! inside = " . ($inside ? 'TRUE' : 'FALSE') . "\n";
        } else {
            echo "    -> Pas de croisement\n";
        }
    }

    echo "Résultat final: " . ($inside ? 'DANS le polygone' : 'HORS du polygone') . "\n\n";
    return $inside;
}

// Test avec le polygone Plateaux et le point problématique
$plateauxPolygon = [
    [0.9000, 6.8000],  // Sud-Ouest
    [0.3000, 8.3000],  // Nord-Ouest
    [1.5000, 8.3000],  // Nord-Est
    [1.5000, 7.0000],  // Sud-Est
    [0.9000, 6.8000]   // Fermeture
];

$testLat = 6.85;
$testLng = 0.75;

echo "Test du point ($testLng, $testLat) dans PLATEAUX:\n";
debugIsPointInPolygon($testLat, $testLng, $plateauxPolygon);

// Test avec un point qui devrait être dans le polygone
echo "Test avec un point au centre du polygone (0.9, 7.5):\n";
debugIsPointInPolygon(7.5, 0.9, $plateauxPolygon);

// Test avec un point clairement à l'extérieur
echo "Test avec un point à l'extérieur (0.2, 7.0):\n";
debugIsPointInPolygon(7.0, 0.2, $plateauxPolygon);