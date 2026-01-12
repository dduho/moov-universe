<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Rôles disponibles:" . PHP_EOL;
$roles = App\Models\Role::all(['id', 'name']);
foreach($roles as $role) {
    echo "- {$role->name} (ID: {$role->id})" . PHP_EOL;
}

echo PHP_EOL . "Utilisateurs et leurs rôles:" . PHP_EOL;
$users = App\Models\User::with('role')->get(['id', 'name', 'email', 'role_id']);
foreach($users as $user) {
    $roleName = $user->role ? $user->role->name : 'Aucun rôle';
    echo "- {$user->name} ({$user->email}): {$roleName}" . PHP_EOL;
}