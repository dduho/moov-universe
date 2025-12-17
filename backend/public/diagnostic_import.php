<?php
// Script de diagnostic pour l'import de transactions
echo "=== DIAGNOSTIC IMPORT TRANSACTIONS ===\n\n";

// Vérifier les limites PHP
echo "Limites PHP actuelles :\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n";
echo "- max_execution_time: " . ini_get('max_execution_time') . "\n\n";

// Vérifier les extensions nécessaires
echo "Extensions PHP :\n";
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'xml', 'zip', 'gd'];
foreach ($required_extensions as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? '✓' : '✗') . "\n";
}

// Vérifier PhpSpreadsheet
echo "\nPhpSpreadsheet: ";
if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
    echo "✓\n";
} else {
    echo "✗ (installer via composer)\n";
}

// Vérifier la base de données
echo "\nConnexion base de données: ";
try {
    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo "✓\n";

    // Vérifier la table pdv_transactions
    $stmt = $pdo->query("SHOW TABLES LIKE 'pdv_transactions'");
    if ($stmt->rowCount() > 0) {
        echo "Table pdv_transactions: ✓\n";

        // Compter les enregistrements
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pdv_transactions");
        $count = $stmt->fetch()['count'];
        echo "Enregistrements dans pdv_transactions: $count\n";
    } else {
        echo "Table pdv_transactions: ✗ (migration manquante)\n";
    }

} catch (Exception $e) {
    echo "✗ (" . $e->getMessage() . ")\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
?>