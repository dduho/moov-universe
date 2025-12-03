<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PointOfSale;
use App\Models\PointOfSaleUpload;
use Illuminate\Support\Facades\Storage;

// Récupérer le dernier PDV créé
$pdv = PointOfSale::latest()->first();

if (!$pdv) {
    echo "Aucun PDV trouvé en base\n";
    exit(1);
}

echo "PDV trouvé: ID={$pdv->id}, Nom={$pdv->nom_point}\n";

// Lister tous les fichiers dans uploads
$allFiles = Storage::disk('public')->allFiles('uploads');
echo "Total fichiers trouvés: " . count($allFiles) . "\n\n";

// Grouper par type
$filesByType = [
    'id_documents' => [],
    'photos' => [],
    'fiscal_documents' => []
];

foreach ($allFiles as $file) {
    $basename = basename($file);
    
    // Extraire l'UUID (36 premiers caractères avant l'extension)
    preg_match('/^([a-f0-9\-]{36})/', $basename, $matches);
    
    if (!empty($matches[1])) {
        $uuid = $matches[1];
        
        // Déterminer le type selon le dossier
        if (str_contains($file, 'uploads/id_documents/')) {
            $filesByType['id_documents'][] = ['uuid' => $uuid, 'file' => $file];
        } elseif (str_contains($file, 'uploads/photos/')) {
            $filesByType['photos'][] = ['uuid' => $uuid, 'file' => $file];
        } elseif (str_contains($file, 'uploads/fiscal_documents/')) {
            $filesByType['fiscal_documents'][] = ['uuid' => $uuid, 'file' => $file];
        }
    }
}

echo "Documents d'identité: " . count($filesByType['id_documents']) . "\n";
echo "Photos: " . count($filesByType['photos']) . "\n";
echo "Documents fiscaux: " . count($filesByType['fiscal_documents']) . "\n\n";

// Créer les associations
$created = 0;

foreach ($filesByType as $type => $files) {
    // Mapper le nom du dossier au type de la table
    $dbType = match($type) {
        'id_documents' => 'id_document',
        'photos' => 'photo',
        'fiscal_documents' => 'fiscal_document',
    };
    
    foreach ($files as $fileInfo) {
        // Vérifier si l'association existe déjà
        $exists = PointOfSaleUpload::where('point_of_sale_id', $pdv->id)
            ->where('upload_id', $fileInfo['uuid'])
            ->exists();
        
        if (!$exists) {
            PointOfSaleUpload::create([
                'point_of_sale_id' => $pdv->id,
                'upload_id' => $fileInfo['uuid'],
                'type' => $dbType,
            ]);
            
            echo "✓ Lié: {$fileInfo['file']} (type: {$dbType})\n";
            $created++;
        } else {
            echo "- Déjà lié: {$fileInfo['file']}\n";
        }
    }
}

echo "\n✅ Total: {$created} fichiers liés au PDV #{$pdv->id}\n";

// Afficher le résumé
$pdv->load(['idDocuments', 'photos', 'fiscalDocuments']);
echo "\nRésumé:\n";
echo "  - Documents d'identité: " . $pdv->idDocuments->count() . "\n";
echo "  - Photos: " . $pdv->photos->count() . "\n";
echo "  - Documents fiscaux: " . $pdv->fiscalDocuments->count() . "\n";
