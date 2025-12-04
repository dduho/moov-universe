<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PointOfSale;

echo "Correction des champs vides dans les PDV...\n\n";

$prenoms = ['Kossi', 'Akossiwa', 'Koffi', 'Afi', 'Kodjo', 'Adjoa', 'Kokou', 'Akoua', 'Yao', 'Ama', 'Kwami', 'Abla', 'Komlan', 'Edem', 'Amavi'];
$noms_famille = ['KEZIE', 'MENSAH', 'AGBEKO', 'DOSSOU', 'KOFFI', 'AMEGAH', 'BAWA', 'TEKO', 'AMOUZOU', 'AHETO', 'DOGBE', 'KOKU', 'ATSU', 'AYEVA', 'GBENOU'];
$profils = ['DISTROWNIF', 'DISTROWNIF+', 'DISTROWNIF-LIGHT', 'DISTROTC', 'PREMIUM', 'STANDARD', 'BASIC'];
$etats_support = ['BON', 'MAUVAIS'];

$pdvs = PointOfSale::all();
$updated = 0;

foreach ($pdvs as $pdv) {
    $changes = [];
    
    // Shortcode
    if (empty($pdv->shortcode)) {
        $pdv->shortcode = '*' . rand(100, 999) . '*' . rand(1, 99) . '#';
        $changes[] = 'shortcode';
    }
    
    // Profil
    if (empty($pdv->profil)) {
        $pdv->profil = $profils[array_rand($profils)];
        $changes[] = 'profil';
    }
    
    // Firstname/Lastname (propriétaire)
    if (empty($pdv->firstname)) {
        $pdv->firstname = $prenoms[array_rand($prenoms)];
        $changes[] = 'firstname';
    }
    
    if (empty($pdv->lastname)) {
        $pdv->lastname = $noms_famille[array_rand($noms_famille)];
        $changes[] = 'lastname';
    }
    
    // Numéro propriétaire
    if (empty($pdv->numero_proprietaire)) {
        $pdv->numero_proprietaire = '228' . str_pad(rand(90000000, 99999999), 8, '0', STR_PAD_LEFT);
        $changes[] = 'numero_proprietaire';
    }
    
    // Autre contact
    if (empty($pdv->autre_contact)) {
        $pdv->autre_contact = '228' . str_pad(rand(90000000, 99999999), 8, '0', STR_PAD_LEFT);
        $changes[] = 'autre_contact';
    }
    
    // Etat support
    if (empty($pdv->etat_support)) {
        $pdv->etat_support = $etats_support[array_rand($etats_support)];
        $changes[] = 'etat_support';
    }
    
    // Type activité
    if (empty($pdv->type_activite)) {
        $pdv->type_activite = ['Commerce', 'Service', 'Boutique'][rand(0, 2)];
        $changes[] = 'type_activite';
    }
    
    // Genre
    if (empty($pdv->gender)) {
        $pdv->gender = ['M', 'F'][rand(0, 1)];
        $changes[] = 'gender';
    }
    
    // Date de naissance
    if (empty($pdv->date_of_birth)) {
        $year = rand(1970, 2000);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $pdv->date_of_birth = "{$year}-{$month}-{$day}";
        $changes[] = 'date_of_birth';
    }
    
    // Nationalité
    if (empty($pdv->nationality)) {
        $pdv->nationality = 'Togolaise';
        $changes[] = 'nationality';
    }
    
    // Profession
    if (empty($pdv->profession)) {
        $pdv->profession = ['Commerçant(e)', 'Vendeur(se)', 'Gérant(e)'][rand(0, 2)];
        $changes[] = 'profession';
    }
    
    // ID Description
    if (empty($pdv->id_description)) {
        $pdv->id_description = ['CNI', 'Passeport', 'Carte consulaire'][rand(0, 2)];
        $changes[] = 'id_description';
    }
    
    // ID Number
    if (empty($pdv->id_number)) {
        $pdv->id_number = 'TG' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $changes[] = 'id_number';
    }
    
    // ID Expiry
    if (empty($pdv->id_expiry_date)) {
        $year = rand(2025, 2035);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $pdv->id_expiry_date = "{$year}-{$month}-{$day}";
        $changes[] = 'id_expiry_date';
    }
    
    // NIF
    if (empty($pdv->nif)) {
        $pdv->nif = 'NIF' . rand(100000, 999999);
        $changes[] = 'nif';
    }
    
    // Régime fiscal
    if (empty($pdv->regime_fiscal)) {
        $pdv->regime_fiscal = ['Réel', 'Simplifié', 'Forfaitaire'][rand(0, 2)];
        $changes[] = 'regime_fiscal';
    }
    
    // Support visibilité
    if (empty($pdv->support_visibilite)) {
        $pdv->support_visibilite = ['Oui', 'Non'][rand(0, 1)];
        $changes[] = 'support_visibilite';
    }
    
    if (!empty($changes)) {
        $pdv->save();
        $updated++;
        echo "✓ PDV #{$pdv->id} - {$pdv->nom_point}: " . implode(', ', $changes) . "\n";
    }
}

echo "\n✅ {$updated} PDV mis à jour avec succès !\n";
echo "Total PDV: " . PointOfSale::count() . "\n";
