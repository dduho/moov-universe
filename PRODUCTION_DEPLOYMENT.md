# 🚀 Checklist Déploiement Production - Analytics

## Avant le déploiement

### 1. Backend - Vérifications
```bash
cd backend

# Vérifier les migrations
php artisan migrate:status

# Tester la commande de cache
php artisan analytics:cache-daily --help

# Vérifier le scheduler
php artisan schedule:list
```

### 2. Base de données

**Indexes requis** :
```bash
php artisan migrate

# Vérifier que ces migrations ont été exécutées :
# - 2025_12_26_000001_add_indexes_to_pdv_transactions
# - 2025_12_26_000002_create_daily_analytics_cache_table
```

**Vérifier les tables** :
```bash
php artisan tinker --execute="
echo 'Tables existantes:\n';
echo 'pdv_transactions: ' . DB::table('pdv_transactions')->count() . ' lignes\n';
echo 'daily_analytics_cache: ' . DB::table('daily_analytics_cache')->count() . ' lignes\n';
"
```

## Configuration Production

### 1. Variables d'environnement (.env)

```env
# Cache (recommandé: redis en production)
CACHE_DRIVER=redis
# ou si pas de redis:
CACHE_DRIVER=file

# Queue (optionnel mais recommandé)
QUEUE_CONNECTION=redis

# Timezone
APP_TIMEZONE=Africa/Lome
```

### 2. Configuration du CRON

#### A. Sans Docker (VPS/Serveur dédié)

```bash
# Exécuter le script de configuration
chmod +x setup_cron.sh
./setup_cron.sh

# Vérifier
crontab -l
```

La ligne ajoutée sera :
```
* * * * * cd /path/to/moov-universe/backend && php artisan schedule:run >> /dev/null 2>&1
```

#### B. Avec Docker

```bash
# Le service scheduler est déjà configuré
docker-compose up -d scheduler

# Vérifier les logs
docker logs moov_scheduler -f
```

#### C. Laravel Forge

1. Aller dans Server > Scheduler
2. Ajouter :
   ```
   * * * * * cd /home/forge/moov-universe/backend && php artisan schedule:run
   ```

### 3. Premiers calculs de cache

```bash
cd backend

# Pré-calculer les 30 derniers jours (Windows PowerShell)
.\backfill_analytics.ps1 -Days 30

# Ou Linux/Mac
chmod +x backfill_analytics.sh
./backfill_analytics.sh 30
```

## Vérification Post-Déploiement

### 1. Exécuter le script de vérification

```bash
chmod +x check_scheduler.sh
./check_scheduler.sh
```

### 2. Vérifications manuelles

```bash
cd backend

# 1. Le scheduler est-il configuré ?
crontab -l | grep schedule:run

# 2. Les tâches sont-elles listées ?
php artisan schedule:list

# 3. Le cache quotidien fonctionne-t-il ?
php artisan analytics:cache-daily

# 4. L'API retourne-t-elle des données ?
curl http://localhost:8000/api/analytics/transactions?period=day

# 5. Les logs sont-ils créés ?
tail -f storage/logs/analytics-cache.log
```

### 3. Test de performance

```bash
# Mesurer le temps de réponse
time curl http://localhost:8000/api/analytics/transactions?period=month

# Devrait être < 2 secondes (première fois)
# Devrait être < 100ms (avec cache)
```

## Monitoring Production

### 1. Logs à surveiller

```bash
# Logs du scheduler
tail -f backend/storage/logs/analytics-cache.log

# Logs Laravel généraux
tail -f backend/storage/logs/laravel.log

# Logs Docker (si utilisé)
docker logs moov_scheduler -f
docker logs moov_backend -f
```

### 2. Alertes à mettre en place

- ❌ Commande `analytics:cache-daily` échoue
- ❌ Temps de réponse API > 5 secondes
- ❌ Cache quotidien pas mis à jour depuis 24h
- ⚠️ Nombre de PDV actifs chute brutalement

### 3. Indicateurs clés

```bash
# Vérifier quotidiennement
php artisan tinker --execute="
\$latest = \App\Models\DailyAnalyticsCache::latest('date')->first();
echo 'Dernière mise à jour: ' . \$latest->date . '\n';
echo 'CA total: ' . number_format(\$latest->total_ca, 2) . ' XOF\n';
echo 'PDV actifs: ' . number_format(\$latest->pdv_actifs) . '\n';
"
```

## Troubleshooting

### Problème : Le scheduler ne s'exécute pas

**Vérifier** :
```bash
# 1. Le cron système tourne-t-il ?
sudo systemctl status cron

# 2. Le crontab est-il configuré ?
crontab -l

# 3. Les permissions sont-elles bonnes ?
ls -la backend/storage/logs
chmod -R 775 backend/storage
```

**Test manuel** :
```bash
cd backend
php artisan schedule:run -v
```

### Problème : La commande analytics échoue

**Debug** :
```bash
cd backend
php artisan analytics:cache-daily -v

# Vérifier les permissions
ls -la storage/logs

# Vérifier la connexion DB
php artisan tinker --execute="DB::connection()->getPdo();"
```

### Problème : Performances lentes

**Vérifier** :
```bash
# 1. Les indexes existent-ils ?
php artisan tinker --execute="
DB::select('SHOW INDEX FROM pdv_transactions');
"

# 2. Le cache fonctionne-t-il ?
php artisan cache:clear
php artisan config:cache

# 3. Redis est-il accessible ?
redis-cli ping
```

## Optimisations supplémentaires (si besoin)

### 1. Redis pour le cache

```env
# .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

```bash
# Installer redis
sudo apt install redis-server
composer require predis/predis
```

### 2. Queue pour les calculs lourds

```php
// Dans app/Console/Kernel.php
$schedule->command('analytics:cache-daily')
         ->dailyAt('09:00')
         ->runInBackground(); // Exécute en arrière-plan
```

### 3. Supervisor pour garantir l'exécution

```bash
# /etc/supervisor/conf.d/moov-scheduler.conf
[program:moov-scheduler]
process_name=%(program_name)s
command=php /path/to/backend/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/scheduler.log
```

## Contacts & Support

En cas de problème en production :
1. Vérifier les logs : `backend/storage/logs/`
2. Exécuter le script de diagnostic : `./check_scheduler.sh`
3. Tester manuellement : `php artisan analytics:cache-daily`

## Dates importantes

- **Import SFTP** : 08:30 quotidien
- **Cache Analytics** : 09:00 quotidien (30 min après import)
- **Rétention cache Laravel** : 15 minutes
- **Rétention logs** : 7 jours (configurable dans `config/logging.php`)
