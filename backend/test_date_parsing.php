<?php

// Simuler la lecture d'une valeur de date depuis Excel
$testDates = [
    "2024-09-04 0:00:00",
    "2027-11-17 0:00:00",
    "2030-12-10",
    "10/12/2030 0:00:00",
];

echo "=== Test de normalisation des dates ===\n\n";

foreach ($testDates as $testDate) {
    $date = trim($testDate);
    
    // Normaliser le format de l'heure si présent (0:00:00 -> 00:00:00)
    $date = preg_replace('/(\d{4}-\d{2}-\d{2}) (\d):(\d{2}):(\d{2})/', '$1 0$2:$3:$4', $date);
    $date = preg_replace('/(\d{2}\/\d{2}\/\d{4}) (\d):(\d{2}):(\d{2})/', '$1 0$2:$3:$4', $date);
    
    echo "Original: $testDate\n";
    echo "Normalisé: $date\n";
    
    // Test avec les formats
    $formats = [
        'Y-m-d H:i:s',
        'Y-m-d',
        'd/m/Y H:i:s',
        'd/m/Y',
    ];
    
    foreach ($formats as $format) {
        $dateObj = \DateTime::createFromFormat($format, $date);
        if ($dateObj !== false) {
            $errors = \DateTime::getLastErrors();
            if ($errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                echo "✓ Format $format: SUCCESS - Converted to: " . $dateObj->format('Y-m-d') . "\n";
                break;
            }
        }
    }
    
    echo "\n";
}
