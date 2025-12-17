#!/bin/bash

# Script de déploiement pour corriger les timeouts en production
# À exécuter sur le serveur Ubuntu avec sudo

echo "=== CORRECTION TIMEOUTS PRODUCTION ==="
echo ""

# 1. Modifier la configuration PHP-FPM pour les longs traitements
echo "1. Modification de /etc/php/8.3/fpm/pool.d/www.conf..."
sudo sed -i 's/request_terminate_timeout = .*/request_terminate_timeout = 900/' /etc/php/8.3/fpm/pool.d/www.conf

# 2. Modifier nginx.conf si pas déjà fait
echo "2. Vérification et mise à jour de nginx.conf..."
NGINX_CONF="/etc/nginx/sites-available/moov-universe"
if [ -f "$NGINX_CONF" ]; then
    # Ajouter les timeouts FastCGI si pas présents
    if ! grep -q "fastcgi_read_timeout" "$NGINX_CONF"; then
        sudo sed -i '/include fastcgi_params;/a \
        \
        # Timeouts augmentés pour les imports de fichiers volumineux\
        fastcgi_read_timeout 900s;\
        fastcgi_send_timeout 900s;\
        fastcgi_connect_timeout 60s;\
        fastcgi_buffer_size 128k;\
        fastcgi_buffers 4 256k;\
        fastcgi_busy_buffers_size 256k;' "$NGINX_CONF"
        echo "Timeouts FastCGI ajoutés à nginx.conf"
    else
        echo "Timeouts FastCGI déjà présents dans nginx.conf"
    fi
else
    echo "⚠️  nginx.conf non trouvé à $NGINX_CONF"
    echo "Copie le fichier nginx.conf depuis le repo vers $NGINX_CONF"
fi

# 3. Redémarrer les services
echo "3. Redémarrage des services..."
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx

# 4. Vérifier les changements
echo "4. Vérification des configurations..."
echo "PHP-FPM request_terminate_timeout:"
grep "request_terminate_timeout" /etc/php/8.3/fpm/pool.d/www.conf || echo "Non trouvé"

echo ""
echo "NGINX fastcgi_read_timeout:"
grep "fastcgi_read_timeout" /etc/nginx/sites-available/moov-universe || echo "Non trouvé"

echo ""
echo "=== CONFIGURATION TERMINÉE ==="
echo "Les timeouts ont été augmentés à 15 minutes. Teste maintenant l'import de fichiers."