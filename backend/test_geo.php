<?php

/**
 * Script de test pour les fonctions de validation géographique
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GeoValidationService;

echo "=== TEST DES FONCTIONS DE VALIDATION GÉOGRAPHIQUE ===\n\n";

$geoService = new GeoValidationService();

// Test 1: Point dans Lomé (devrait retourner MARITIME)
echo "Test 1: Point dans Lomé (1.25, 6.15)\n";
$result = $geoService->getRegionFromCoordinates(6.15, 1.25);
echo "Résultat: " . ($result ? $result['region'] . " - " . $result['name'] : "null") . "\n\n";

// Test 2: Point dans Plateaux (Agoú-Gare, Assahoun-Fiagbé)
echo "Test 2: Point dans Plateaux (Agoú-Gare, Assahoun-Fiagbé) (0.75, 6.85)\n";
$result = $geoService->getRegionFromCoordinates(6.85, 0.75);
echo "Résultat: " . ($result ? $result['region'] . " - " . $result['name'] : "null") . "\n\n";

// Test 3: Point dans Maritime (près de la frontière Ghana)
echo "Test 3: Point dans Maritime (0.95, 6.75)\n";
$result = $geoService->getRegionFromCoordinates(6.75, 0.95);
echo "Résultat: " . ($result ? $result['region'] . " - " . $result['name'] : "null") . "\n\n";

// Test 4: Point hors du Togo
echo "Test 4: Point hors du Togo (hors frontières)\n";
$result = $geoService->getRegionFromCoordinates(5.0, 0.0);
echo "Résultat: " . ($result ? $result['region'] . " - " . $result['name'] : "null") . "\n\n";

// Test 5: Validation complète pour un PDV
echo "Test 5: Validation complète - PDV déclaré Plateaux avec coordonnées Agoú-Gare\n";
$validation = $geoService->validateRegionCoordinates(6.85, 0.75, 'PLATEAUX');
echo "Validation: " . json_encode($validation, JSON_PRETTY_PRINT) . "\n\n";

echo "=== FIN DES TESTS ===\n";