<?php
// Script de test des timeouts - simule un traitement long
echo "Début du test de timeout...\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

// Simuler un traitement de 5 minutes (300 secondes)
for ($i = 1; $i <= 60; $i++) {  // 60 itérations de 5 secondes = 5 minutes
    echo "Itération $i/60 - " . date('H:i:s') . "\n";
    sleep(5);  // Pause de 5 secondes

    // Forcer l'envoi des données (flush)
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

echo "Test terminé avec succès !\n";
echo "Timestamp final: " . date('Y-m-d H:i:s') . "\n";
?>