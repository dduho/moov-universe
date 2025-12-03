<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;

$admin = User::where('email', 'admin@moov.tg')->first();
$adminRole = Role::where('name', 'admin')->first();

if ($admin && $adminRole) {
    $admin->role_id = $adminRole->id;
    $admin->save();
    echo "✅ Admin role updated successfully!\n";
    echo "User: {$admin->name}\n";
    echo "Email: {$admin->email}\n";
    echo "Role ID: {$admin->role_id}\n";
} else {
    if (!$admin) {
        echo "❌ Admin user not found\n";
    }
    if (!$adminRole) {
        echo "❌ Admin role not found\n";
    }
}
