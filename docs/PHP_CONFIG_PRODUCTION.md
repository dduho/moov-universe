# Configuration PHP pour Import de Transactions
# À ajouter dans le php.ini du serveur de production

# Limites de taille de fichiers
upload_max_filesize = 500M
post_max_size = 500M

# Limite de mémoire pour traiter les gros fichiers
memory_limit = 512M

# Temps d'exécution pour traiter 30000+ lignes
max_execution_time = 300
max_input_time = 300

# Pour les environnements de production avec Nginx/Apache, 
# il peut être nécessaire de configurer également :

# Nginx (dans /etc/nginx/nginx.conf ou site config) :
# client_max_body_size 500M;

# Apache (dans .htaccess ou config) :
# php_value upload_max_filesize 500M
# php_value post_max_size 500M
# php_value memory_limit 512M
# php_value max_execution_time 300
# php_value max_input_time 300
