<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\GeoValidationService;

echo "=== Test des nouvelles données de polygones précis ===\n\n";

// Initialiser le service
$geoService = new GeoValidationService();

// Tests de validation de coordonnées avec coordonnées plus précises
$testCases = [
    // Test Maritime (Lomé)
    ['lat' => 6.1319, 'lng' => 1.2228, 'region' => 'MARITIME', 'description' => 'Lomé centre'],
    ['lat' => 6.2094, 'lng' => 1.1847, 'region' => 'MARITIME', 'description' => 'Aflao (frontière Ghana)'],

    // Test Plateaux - coordonnées ajustées
    ['lat' => 7.3589, 'lng' => 0.8258, 'region' => 'PLATEAUX', 'description' => 'Atakpamé'],
    ['lat' => 7.1907, 'lng' => 0.6033, 'region' => 'PLATEAUX', 'description' => 'Kpalimé'],

    // Test Centrale - coordonnées ajustées
    ['lat' => 8.1333, 'lng' => 1.1667, 'region' => 'CENTRALE', 'description' => 'Sokodé'],
    ['lat' => 8.2833, 'lng' => 0.9833, 'region' => 'CENTRALE', 'description' => 'Bafilo'],

    // Test Kara - coordonnées ajustées
    ['lat' => 9.5511, 'lng' => 1.1861, 'region' => 'KARA', 'description' => 'Kara centre'],
    ['lat' => 9.7667, 'lng' => 0.8167, 'region' => 'KARA', 'description' => 'Bassar'],

    // Test Savanes
    ['lat' => 10.9092, 'lng' => 0.2076, 'region' => 'SAVANES', 'description' => 'Dapaong'],
    ['lat' => 10.0667, 'lng' => 0.2167, 'region' => 'SAVANES', 'description' => 'Mango'],

    // Test points hors du Togo
    ['lat' => 5.5, 'lng' => 1.0, 'region' => 'MARITIME', 'description' => 'Hors Togo (sud)'],
    ['lat' => 12.0, 'lng' => 0.5, 'region' => 'SAVANES', 'description' => 'Hors Togo (nord)'],
    ['lat' => 8.0, 'lng' => -1.0, 'region' => 'PLATEAUX', 'description' => 'Hors Togo (ouest)'],
    ['lat' => 8.0, 'lng' => 2.5, 'region' => 'CENTRALE', 'description' => 'Hors Togo (est)'],
];

echo "Test de validation des coordonnées :\n";
echo str_repeat("-", 80) . "\n";

foreach ($testCases as $test) {
    $result = $geoService->validateRegionCoordinates($test['lat'], $test['lng'], $test['region']);

    $status = $result['is_valid'] ? '✓ VALIDE' : '✗ INVALIDE';
    $alertType = $result['has_alert'] ? ($result['alert_type'] ?? 'unknown') : 'none';

    echo sprintf("%-25s | %-12s | %-8s | %s\n",
        substr($test['description'], 0, 25),
        $status,
        $alertType,
        $result['message'] ?? 'OK'
    );
}

echo "\n" . str_repeat("-", 80) . "\n";

// Test de détermination de région
echo "\nTest de détermination automatique de région :\n";
echo str_repeat("-", 80) . "\n";

$regionTests = [
    ['lat' => 6.1319, 'lng' => 1.2228, 'expected' => 'MARITIME'],
    ['lat' => 7.3589, 'lng' => 0.8258, 'expected' => 'PLATEAUX'],
    ['lat' => 8.1333, 'lng' => 1.1667, 'expected' => 'CENTRALE'],
    ['lat' => 9.5511, 'lng' => 1.1861, 'expected' => 'KARA'],
    ['lat' => 10.9092, 'lng' => 0.2076, 'expected' => 'SAVANES'],
];

foreach ($regionTests as $test) {
    $region = $geoService->getRegionFromCoordinates($test['lat'], $test['lng']);

    $detected = $region ? $region['region'] : 'null';
    $correct = ($detected === $test['expected']) ? '✓' : '✗';

    echo sprintf("%-15s | %-10s | %-10s | %s\n",
        "({$test['lat']}, {$test['lng']})",
        $test['expected'],
        $detected,
        $correct
    );
}

echo "\n" . str_repeat("-", 80) . "\n";

// Test des bounds des régions
echo "\nTest des bounding boxes des régions :\n";
echo str_repeat("-", 80) . "\n";

$regionInfo = $geoService->getRegionInfo();
echo "Informations des régions :\n";
foreach ($regionInfo as $code => $info) {
    echo "{$code}: {$info['name']} - Points: {$info['polygon_points']} - Bounds: [{$info['bounds']['minLat']}, {$info['bounds']['minLng']}] to [{$info['bounds']['maxLat']}, {$info['bounds']['maxLng']}]\n";
}

echo "\nTest de points spécifiques :\n";
$boundsTests = [
    ['lat' => 7.1907, 'lng' => 0.6033, 'description' => 'Kpalimé (PLATEAUX)'],
    ['lat' => 8.1333, 'lng' => 1.1667, 'description' => 'Sokodé (CENTRALE)'],
    ['lat' => 9.5511, 'lng' => 1.1861, 'description' => 'Kara (KARA)'],
    ['lat' => 10.0667, 'lng' => 0.2167, 'description' => 'Mango (SAVANES)'],
];

foreach ($boundsTests as $test) {
    echo "Point: {$test['description']} ({$test['lat']}, {$test['lng']})\n";

    foreach ($regionInfo as $regionCode => $info) {
        $bounds = $info['bounds'];
        $inBounds = ($test['lat'] >= $bounds['minLat'] && $test['lat'] <= $bounds['maxLat'] &&
                    $test['lng'] >= $bounds['minLng'] && $test['lng'] <= $bounds['maxLng']);

        if ($inBounds) {
            echo "  → Dans bounds de {$info['name']} ({$regionCode})\n";
        }
    }
    echo "\n";
}

?>