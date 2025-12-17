#!/bin/bash

# Script de déploiement pour corriger les configurations PHP en production
# À exécuter sur le serveur Ubuntu avec sudo

echo "=== CORRECTION CONFIGURATION PHP PRODUCTION ==="
echo ""

# 1. Modifier php.ini pour FPM
echo "1. Modification de /etc/php/8.3/fpm/php.ini..."
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 500M/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 2G/' /etc/php/8.3/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 900/' /etc/php/8.3/fpm/php.ini

# 2. Modifier php.ini pour CLI (au cas où)
echo "2. Modification de /etc/php/8.3/cli/php.ini..."
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 500M/' /etc/php/8.3/cli/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 500M/' /etc/php/8.3/cli/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 2G/' /etc/php/8.3/cli/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 900/' /etc/php/8.3/cli/php.ini

# 3. Redémarrer les services
echo "3. Redémarrage des services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# 4. Vérifier les changements
echo "4. Vérification des nouvelles valeurs..."
echo "PHP-FPM:"
php -c /etc/php/8.3/fpm/php.ini -r "echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL; echo 'post_max_size: ' . ini_get('post_max_size') . PHP_EOL; echo 'memory_limit: ' . ini_get('memory_limit') . PHP_EOL; echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;"

echo ""
echo "=== CONFIGURATION TERMINÉE ==="
echo "Les limites PHP ont été mises à jour. Teste maintenant l'import de fichiers."