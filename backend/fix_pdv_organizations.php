<?php

/**
 * Script pour nettoyer le champ dealer_name des PDV
 * Le champ dealer_name ne doit plus être utilisé, on utilise organization.name via la relation
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PointOfSale;
use Illuminate\Support\Facades\DB;

echo "🔧 Nettoyage du champ dealer_name des PDV...\n\n";

// Compter les PDV avec dealer_name rempli
$totalWithDealerName = PointOfSale::whereNotNull('dealer_name')
    ->where('dealer_name', '!=', '')
    ->where('dealer_name', '!=', 'N/A')
    ->count();

echo "📊 " . $totalWithDealerName . " PDV ont un dealer_name à nettoyer\n\n";

if ($totalWithDealerName === 0) {
    echo "✨ Aucun PDV à nettoyer!\n";
    exit(0);
}

echo "⏳ Nettoyage en cours...\n";

// Mettre une chaîne vide à tous les dealer_name (NOT NULL constraint)
$updated = DB::table('point_of_sales')
    ->whereNotNull('dealer_name')
    ->where('dealer_name', '!=', '')
    ->update(['dealer_name' => '']);

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n";
echo "✅ PDV nettoyés: $updated\n";
echo "\n💡 Le champ dealer_name ne sera plus utilisé.\n";
echo "   Le nom du dealer s'affichera via organization.name\n";

echo "\n✨ Script terminé!\n";
