<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PointOfSale;
use App\Models\Organization;
use App\Models\User;

// Get first organization and user
$organization = Organization::first();
$user = User::first();

if (!$organization || !$user) {
    echo "Erreur: Aucune organisation ou utilisateur trouvé.\n";
    exit(1);
}

// Coordonnées de base à Lomé (Avenue du 24 Janvier)
$baseLatitude = 6.1319;
$baseLongitude = 1.2228;

// Créer le premier PDV
$pdv1 = PointOfSale::create([
    'organization_id' => $organization->id,
    'dealer_name' => $organization->name,
    'numero_flooz' => '22890' . rand(100000, 999999),
    'shortcode' => '*' . rand(100, 999) . '*' . rand(10, 99) . '#',
    'nom_point' => 'Boutique Test Proximité A',
    'profil' => 'STANDARD',
    'type_activite' => 'Commerce',
    
    // Propriétaire
    'firstname' => 'Test',
    'lastname' => 'Proximité A',
    'date_of_birth' => '1985-05-15',
    'gender' => 'M',
    'nationality' => 'Togolaise',
    'profession' => 'Commerçant',
    'id_description' => 'CNI',
    'id_number' => 'TG' . rand(10000000, 99999999),
    'id_expiry_date' => '2030-12-31',
    
    // Localisation (Point A)
    'region' => 'MARITIME',
    'prefecture' => 'Golfe',
    'commune' => 'Lomé',
    'canton' => 'Lomé 1',
    'ville' => 'Lomé',
    'quartier' => 'Tokoin',
    'localisation' => 'Avenue du 24 Janvier, près du carrefour',
    'latitude' => $baseLatitude,
    'longitude' => $baseLongitude,
    
    // Contact
    'numero_proprietaire' => '228' . rand(90000000, 99999999),
    'autre_contact' => '228' . rand(90000000, 99999999),
    
    // Fiscal
    'nif' => 'NIF' . rand(100000, 999999),
    'regime_fiscal' => 'Simplifié',
    'support_visibilite' => 'Oui',
    
    // Metadata
    'created_by' => $user->id,
    'status' => 'validated',
    'validated_by' => $user->id,
    'validated_at' => now(),
]);

echo "✓ PDV 1 créé: {$pdv1->nom_point} (ID: {$pdv1->id})\n";
echo "  Coordonnées: {$pdv1->latitude}, {$pdv1->longitude}\n\n";

// Créer le second PDV à ~50 mètres (environ 0.00045 degrés)
// En déplaçant légèrement vers le nord-est
$pdv2Latitude = $baseLatitude + 0.00045;  // ~50m vers le nord
$pdv2Longitude = $baseLongitude + 0.00045; // ~50m vers l'est

$pdv2 = PointOfSale::create([
    'organization_id' => $organization->id,
    'dealer_name' => $organization->name,
    'numero_flooz' => '22890' . rand(100000, 999999),
    'shortcode' => '*' . rand(100, 999) . '*' . rand(10, 99) . '#',
    'nom_point' => 'Boutique Test Proximité B',
    'profil' => 'PREMIUM',
    'type_activite' => 'Service',
    
    // Propriétaire
    'firstname' => 'Test',
    'lastname' => 'Proximité B',
    'date_of_birth' => '1990-08-20',
    'gender' => 'F',
    'nationality' => 'Togolaise',
    'profession' => 'Vendeuse',
    'id_description' => 'Passeport',
    'id_number' => 'TG' . rand(10000000, 99999999),
    'id_expiry_date' => '2028-06-30',
    
    // Localisation (Point B - très proche de A)
    'region' => 'MARITIME',
    'prefecture' => 'Golfe',
    'commune' => 'Lomé',
    'canton' => 'Lomé 1',
    'ville' => 'Lomé',
    'quartier' => 'Tokoin',
    'localisation' => 'Avenue du 24 Janvier, 50m après le carrefour',
    'latitude' => $pdv2Latitude,
    'longitude' => $pdv2Longitude,
    
    // Contact
    'numero_proprietaire' => '228' . rand(90000000, 99999999),
    'autre_contact' => '228' . rand(90000000, 99999999),
    
    // Fiscal
    'nif' => 'NIF' . rand(100000, 999999),
    'regime_fiscal' => 'Réel',
    'support_visibilite' => 'Oui',
    
    // Metadata
    'created_by' => $user->id,
    'status' => 'validated',
    'validated_by' => $user->id,
    'validated_at' => now(),
]);

echo "✓ PDV 2 créé: {$pdv2->nom_point} (ID: {$pdv2->id})\n";
echo "  Coordonnées: {$pdv2->latitude}, {$pdv2->longitude}\n\n";

// Calculer la distance réelle entre les deux points
$earthRadius = 6371000; // en mètres

$dLat = deg2rad($pdv2Latitude - $baseLatitude);
$dLon = deg2rad($pdv2Longitude - $baseLongitude);

$a = sin($dLat/2) * sin($dLat/2) + 
     cos(deg2rad($baseLatitude)) * cos(deg2rad($pdv2Latitude)) * 
     sin($dLon/2) * sin($dLon/2);

$c = 2 * atan2(sqrt($a), sqrt(1-$a));
$distance = $earthRadius * $c;

echo "📏 Distance calculée entre les deux PDV: " . round($distance, 2) . " mètres\n";
echo "⚠️  Seuil de proximité configuré: 300m\n";
echo "\n";
echo "✅ Les deux PDV ont été créés avec succès!\n";
echo "   Visitez le PDV {$pdv1->id} ou {$pdv2->id} pour voir l'alerte de proximité.\n";

