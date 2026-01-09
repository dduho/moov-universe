<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\User::first();
if ($admin) {
    $token = $admin->createToken('test-rentability')->plainTextToken;
    echo "Token: " . $token . PHP_EOL;
    echo "User: " . $admin->name . " (" . $admin->email . ")" . PHP_EOL;
} else {
    echo "Aucun utilisateur trouvé" . PHP_EOL;
}