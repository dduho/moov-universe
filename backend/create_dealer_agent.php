<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

// Get dealer_agent role
$dealerAgentRole = Role::where('name', 'dealer_agent')->first();

if (!$dealerAgentRole) {
    echo "❌ Erreur: Le rôle 'dealer_agent' n'existe pas.\n";
    echo "   Exécutez d'abord: php artisan migrate\n";
    exit(1);
}

// Get first organization
$organization = Organization::first();

if (!$organization) {
    echo "❌ Erreur: Aucune organisation trouvée.\n";
    exit(1);
}

// Create dealer agent user
$agent = User::create([
    'name' => 'Commercial Test',
    'email' => 'agent@test.com',
    'password' => Hash::make('password'),
    'role_id' => $dealerAgentRole->id,
    'organization_id' => $organization->id,
    'is_active' => true,
]);

echo "✅ Utilisateur 'dealer_agent' créé avec succès!\n\n";
echo "📧 Email: agent@test.com\n";
echo "🔑 Mot de passe: password\n";
echo "🏢 Organisation: {$organization->name}\n";
echo "👤 Rôle: {$dealerAgentRole->display_name}\n\n";
echo "⚠️  Cet utilisateur ne peut voir QUE les PDV qu'il a créés lui-même.\n";
echo "   Pour tester, connectez-vous et créez des PDV avec ce compte.\n";

