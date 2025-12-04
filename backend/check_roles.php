<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📋 RÔLES DANS LA BASE DE DONNÉES:\n";
echo str_repeat("=", 60) . "\n\n";

$roles = DB::table('roles')->get(['id', 'name', 'display_name', 'description']);

foreach ($roles as $role) {
    echo "ID: {$role->id}\n";
    echo "Nom technique: {$role->name}\n";
    echo "Nom d'affichage: {$role->display_name}\n";
    echo "Description: {$role->description}\n";
    
    $userCount = DB::table('users')->where('role_id', $role->id)->count();
    echo "Utilisateurs: {$userCount}\n";
    echo str_repeat("-", 60) . "\n\n";
}

echo "\n👥 RÉPARTITION DES UTILISATEURS PAR RÔLE:\n";
echo str_repeat("=", 60) . "\n\n";

$users = DB::table('users')
    ->join('roles', 'users.role_id', '=', 'roles.id')
    ->leftJoin('organizations', 'users.organization_id', '=', 'organizations.id')
    ->select('users.name as user_name', 'users.email', 'roles.name as role_name', 'roles.display_name', 'organizations.name as org_name')
    ->get();

foreach ($users as $user) {
    echo "👤 {$user->user_name} ({$user->email})\n";
    echo "   Rôle: {$user->display_name} ({$user->role_name})\n";
    echo "   Organisation: " . ($user->org_name ?? 'N/A') . "\n\n";
}

