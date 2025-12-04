<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PointOfSale;
use App\Models\PointOfSaleUpload;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Récupérer le PDV existant comme modèle
$template = PointOfSale::first();
if (!$template) {
    echo "Aucun PDV existant pour servir de modèle\n";
    exit(1);
}

// Récupérer tous les dealers (organizations)
$dealers = Organization::all();
if ($dealers->isEmpty()) {
    echo "Aucun dealer trouvé\n";
    exit(1);
}

// Récupérer tous les utilisateurs pour les créateurs
$users = User::all();
if ($users->isEmpty()) {
    echo "Aucun utilisateur trouvé\n";
    exit(1);
}

// Récupérer tous les fichiers disponibles
$idDocFiles = Storage::disk('public')->files('uploads/id_documents');
$photoFiles = Storage::disk('public')->files('uploads/photos');
$fiscalFiles = Storage::disk('public')->files('uploads/fiscal_documents');

echo "Fichiers disponibles:\n";
echo "  - Documents d'identité: " . count($idDocFiles) . "\n";
echo "  - Photos: " . count($photoFiles) . "\n";
echo "  - Documents fiscaux: " . count($fiscalFiles) . "\n\n";

// Listes de noms et données
$noms = [
    'ETS LA JOIE', 'BOUTIQUE ESPOIR', 'KIOSQUE DIVINE', 'CHEZ MARCEL', 'AU BON COIN',
    'LA REFERENCE', 'CHEZ FATOU', 'KIOSQUE CENTRAL', 'BOUTIQUE MODERNE', 'ETS PROVIDENCE',
    'CHEZ KOFI', 'LA COLOMBE', 'BOUTIQUE SOLEIL', 'KIOSQUE DU MARCHE', 'ETS RENAISSANCE',
    'CHEZ ADJOUA', 'LA PALMIER', 'BOUTIQUE LUMIERE', 'KIOSQUE EXPRESS', 'ETS VICTOIRE',
    'CHEZ AKOUA', 'LA GRÂCE', 'BOUTIQUE EXCELLENCE', 'KIOSQUE RAPIDE', 'ETS HARMONIE',
    'CHEZ AMAVI', 'LA SAGESSE', 'BOUTIQUE PRESTIGE', 'KIOSQUE FLEURI', 'ETS PROSPERITE',
    'CHEZ KOKU', 'LA PAIX', 'BOUTIQUE ELEGANCE', 'KIOSQUE SOURIRE', 'ETS BONHEUR',
    'CHEZ YAO', 'LA CHANCE', 'BOUTIQUE ROYALE', 'KIOSQUE MIRACLE', 'ETS PATIENCE',
    'CHEZ AMA', 'LA FOI', 'BOUTIQUE SUPREME', 'KIOSQUE ETOILE', 'ETS SUCCES',
    'CHEZ KOKOU', 'LA VIE', 'BOUTIQUE DIAMANT', 'KIOSQUE PERLE', 'ETS GLOIRE'
];

$prenoms = ['Kossi', 'Akossiwa', 'Koffi', 'Afi', 'Kodjo', 'Adjoa', 'Kokou', 'Akoua', 'Yao', 'Ama', 'Kwami', 'Abla', 'Komlan', 'Edem', 'Amavi'];
$noms_famille = ['KEZIE', 'MENSAH', 'AGBEKO', 'DOSSOU', 'KOFFI', 'AMEGAH', 'BAWA', 'TEKO', 'AMOUZOU', 'AHETO', 'DOGBE', 'KOKU', 'ATSU', 'AYEVA', 'GBENOU'];

$regions = ['MARITIME', 'PLATEAUX', 'CENTRALE', 'KARA', 'SAVANES'];
$prefectures = ['Golfe', 'Lacs', 'Vo', 'Yoto', 'Zio', 'Kloto', 'Ogou', 'Haho', 'Tchaoudjo', 'Sotouboua', 'Kozah', 'Binah', 'Oti', 'Tone', 'Cinkassé'];
$villes = ['Lomé', 'Aného', 'Tsévié', 'Tabligbo', 'Kpalimé', 'Atakpamé', 'Sokodé', 'Kara', 'Bassar', 'Dapaong', 'Mango'];
$quartiers = ['Adidogomé', 'Nyékonakpoé', 'Agoè', 'Tokoin', 'Bè', 'Amoutivé', 'Hédzranawoé', 'Kodjoviakopé', 'Centre-ville', 'Zongo', 'Hanoukopé'];
$communes = ['Avé_1', 'Avé_2', 'Kpogan', 'Vakpossito', 'Gapé', 'Kloto_1', 'Kloto_2', 'Ogou_1', 'Ogou_2', 'Tchaoudjo_1', 'Kozah_1', 'Oti_1'];
$cantons = ['Avé-Centre', 'Avé-Kpodji', 'Vakpossito', 'Gapé-Centre', 'Kloto-Nord', 'Kloto-Sud', 'Ogou-Est', 'Tchaoudjo-Centre', 'Kozah-Centre', 'Oti-Nord'];

$profils = ['DISTROWNIF', 'DISTROWNIF+', 'DISTROWNIF-LIGHT', 'DISTROTC', 'PREMIUM', 'STANDARD', 'BASIC'];
$etats_support = ['BON', 'MAUVAIS'];

echo "Génération de 50 PDV...\n\n";

for ($i = 0; $i < 50; $i++) {
    // Sélectionner aléatoirement
    $dealer = $dealers->random();
    $creator = $users->random();
    $region = $regions[array_rand($regions)];
    
    // Générer un numéro Flooz unique
    $numeroFlooz = '228' . str_pad(rand(90000000, 99999999), 8, '0', STR_PAD_LEFT);
    
    // Coordonnées GPS aléatoires (Togo)
    $latitude = rand(60000000, 115000000) / 10000000; // Entre 6.0 et 11.5
    $longitude = rand(-2000000, 18000000) / 10000000; // Entre -0.2 et 1.8
    
    // Créer le PDV
    $pdv = PointOfSale::create([
        'organization_id' => $dealer->id,
        'created_by' => $creator->id,
        'nom_point' => $noms[$i],
        'numero_flooz' => $numeroFlooz,
        'dealer_name' => $dealer->name,
        'firstname' => $prenoms[array_rand($prenoms)],
        'lastname' => $noms_famille[array_rand($noms_famille)],
        'nom_gerant' => $prenoms[array_rand($prenoms)] . ' ' . $noms_famille[array_rand($noms_famille)],
        'prenom_gerant' => $prenoms[array_rand($prenoms)],
        'numero_gerant' => '228' . str_pad(rand(90000000, 99999999), 8, '0', STR_PAD_LEFT),
        'region' => $region,
        'prefecture' => $prefectures[array_rand($prefectures)],
        'commune' => $communes[array_rand($communes)],
        'canton' => $cantons[array_rand($cantons)],
        'ville' => $villes[array_rand($villes)],
        'quartier' => $quartiers[array_rand($quartiers)],
        'profil_pdv' => $profils[array_rand($profils)],
        'etat_support' => $etats_support[array_rand($etats_support)],
        'latitude' => $latitude,
        'longitude' => $longitude,
        'status' => ['pending', 'validated', 'rejected'][rand(0, 2)],
    ]);
    
    // Ajouter 0 à 2 fichiers aléatoires
    $nbFiles = rand(0, 2);
    $addedFiles = 0;
    
    if ($nbFiles > 0 && !empty($idDocFiles)) {
        // Ajouter 1 document d'identité
        $file = $idDocFiles[array_rand($idDocFiles)];
        preg_match('/^([a-f0-9\-]{36})/', basename($file), $matches);
        if (!empty($matches[1])) {
            PointOfSaleUpload::create([
                'point_of_sale_id' => $pdv->id,
                'upload_id' => $matches[1],
                'type' => 'id_document',
            ]);
            $addedFiles++;
        }
    }
    
    if ($nbFiles > 1) {
        // Ajouter soit une photo soit un document fiscal
        $type = rand(0, 1) === 0 ? 'photo' : 'fiscal';
        $files = $type === 'photo' ? $photoFiles : $fiscalFiles;
        
        if (!empty($files)) {
            $file = $files[array_rand($files)];
            preg_match('/^([a-f0-9\-]{36})/', basename($file), $matches);
            if (!empty($matches[1])) {
                PointOfSaleUpload::create([
                    'point_of_sale_id' => $pdv->id,
                    'upload_id' => $matches[1],
                    'type' => $type === 'photo' ? 'photo' : 'fiscal_document',
                ]);
                $addedFiles++;
            }
        }
    }
    
    echo "✓ PDV #" . ($i + 1) . ": {$pdv->nom_point} - {$pdv->numero_flooz} ({$pdv->status}) - {$addedFiles} fichier(s)\n";
    echo "  Dealer: {$dealer->name} | Région: {$region} | Ville: {$pdv->ville}\n\n";
}

echo "\n✅ 50 PDV créés avec succès !\n";
echo "\nRésumé par statut:\n";
echo "  - En attente: " . PointOfSale::where('status', 'pending')->count() . "\n";
echo "  - Validés: " . PointOfSale::where('status', 'validated')->count() . "\n";
echo "  - Rejetés: " . PointOfSale::where('status', 'rejected')->count() . "\n";
echo "\nTotal PDV: " . PointOfSale::count() . "\n";
