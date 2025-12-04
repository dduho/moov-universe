<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

// Get dealer_owner role
$dealerOwnerRole = Role::where('name', 'dealer_owner')->first();

if (!$dealerOwnerRole) {
    echo "❌ Erreur: Le rôle 'dealer_owner' n'existe pas.\n";
    exit(1);
}

// Get first organization
$organization = Organization::first();

if (!$organization) {
    echo "❌ Erreur: Aucune organisation trouvée.\n";
    exit(1);
}

// Create dealer owner user
$owner = User::create([
    'name' => 'Propriétaire SOMAC',
    'email' => 'owner@somac.com',
    'password' => Hash::make('password'),
    'role_id' => $dealerOwnerRole->id,
    'organization_id' => $organization->id,
    'is_active' => true,
]);

echo "✅ Utilisateur 'dealer_owner' créé avec succès!\n\n";
echo "📧 Email: owner@somac.com\n";
echo "🔑 Mot de passe: password\n";
echo "🏢 Organisation: {$organization->name}\n";
echo "👤 Rôle: {$dealerOwnerRole->display_name}\n\n";
echo "⚠️  Cet utilisateur peut voir TOUS les PDV de son organisation.\n";

