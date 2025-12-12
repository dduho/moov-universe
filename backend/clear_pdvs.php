<?php

/**
 * Script pour vider la liste des PDV (Points de Vente)
 * ATTENTION: Cette action est IRRÉVERSIBLE !
 */

require_once __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PointOfSale;

echo "=== SCRIPT DE VIDAGE DES PDV ===\n";
echo "ATTENTION: Cette action va supprimer TOUS les PDV de la base de données !\n\n";

// Compter les PDV actuels
$currentCount = PointOfSale::count();
echo "Nombre de PDV actuellement en base: {$currentCount}\n\n";

// Demander confirmation
echo "Êtes-vous sûr de vouloir continuer ? (tapez 'OUI' pour confirmer): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtoupper($confirmation) !== 'OUI') {
    echo "Opération annulée.\n";
    exit(0);
}

echo "\nSuppression en cours...\n";

try {
    // Supprimer tous les PDV
    $deletedCount = PointOfSale::query()->delete();

    echo "✅ Succès: {$deletedCount} PDV supprimés.\n";
    echo "La liste des PDV a été vidée.\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la suppression: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nOpération terminée.\n";