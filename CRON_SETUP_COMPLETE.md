# ✅ CRON Configuré - Guide Complet

## 🎯 Ce qui a été fait

### 1. Scheduler Laravel configuré ✅

**Fichier** : [backend/app/Console/Kernel.php](backend/app/Console/Kernel.php)

**Tâches planifiées** :

| Tâche | Heure | Description |
|-------|-------|-------------|
| `transactions:import-sftp` | 08:30 | Import des transactions depuis SFTP |
| `analytics:cache-daily` | 09:00 | Pré-calcul des analytics de J-1 |

**Sécurités activées** :
- `withoutOverlapping()` : Évite les exécutions parallèles
- `onOneServer()` : Une seule instance même avec plusieurs serveurs
- Logs automatiques dans `storage/logs/analytics-cache.log`

## 🚀 Déploiement Production

### Option 1 : Déploiement automatique (recommandé)

Le script de déploiement configure automatiquement le scheduler :

```bash
cd /path/to/moov-universe
chmod +x deploy.sh

# Déploiement standard
./deploy.sh

# Déploiement avec pré-calcul de 30 jours d'historique
./deploy.sh --init-analytics
```

**Options disponibles** :
```bash
./deploy.sh [options]

Options:
  --backend-only     Déploie uniquement le backend
  --frontend-only    Déploie uniquement le frontend  
  --no-migrate       Ne pas exécuter les migrations
  --init-analytics   Pré-calcule les analytics des 30 derniers jours
```

Le script `deploy.sh` va automatiquement :
- ✅ Configurer le crontab système
- ✅ Vérifier les tâches planifiées
- ✅ Pré-calculer les 7 derniers jours (première fois)
- ✅ Pré-calculer 30 jours si `--init-analytics`

### Option 2 : Docker

```bash
# Déjà configuré dans docker-compose.yml
docker-compose up -d scheduler
```

### Option 3 : Manuel

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne (remplacer le chemin)
* * * * * cd /path/to/moov-universe/backend && php artisan schedule:run >> /dev/null 2>&1
```

## 🔍 Vérification

### Commandes de vérification

```bash
# 1. Lister les tâches planifiées
php artisan schedule:list

# 2. Tester manuellement
php artisan schedule:run -v

# 3. Vérifier le cron système
crontab -l

# 4. Script de diagnostic complet
./check_scheduler.sh
```

### Résultat attendu

```
30 8 * * *  php artisan transactions:import-sftp
0  9 * * *  php artisan analytics:cache-daily
```

## 📊 Pré-calcul initial (une fois)

Avant la mise en production, pré-calculer l'historique :

**Windows (PowerShell)** :
```powershell
cd backend
.\backfill_analytics.ps1 -Days 30
```

**Linux/Mac** :
```bash
cd backend
chmod +x backfill_analytics.sh
./backfill_analytics.sh 30
```

Cela pré-calcule les 30 derniers jours pour un affichage instantané.

## 📝 Monitoring

### Logs à surveiller

```bash
# Logs du cache analytics
tail -f backend/storage/logs/analytics-cache.log

# Logs Laravel généraux
tail -f backend/storage/logs/laravel.log
```

### Vérifier que ça tourne

```bash
# Dernière mise à jour du cache
php artisan tinker --execute="
\$latest = \App\Models\DailyAnalyticsCache::latest('date')->first();
if (\$latest) {
    echo 'Dernière mise à jour: ' . \$latest->date . '\n';
    echo 'CA: ' . number_format(\$latest->total_ca, 2) . ' XOF\n';
}
"
```

## ⚡ Performance attendue

Avec cette configuration :

| Scénario | Temps de réponse |
|----------|------------------|
| **Sans cache** (première fois) | 1-2 secondes |
| **Avec cache Laravel** (15 min) | < 100 ms |
| **Avec cache quotidien** (jour) | < 50 ms |

## 🆘 Troubleshooting

### Le cron ne s'exécute pas

```bash
# 1. Vérifier que cron tourne
sudo systemctl status cron

# 2. Vérifier les permissions
ls -la backend/storage/logs
chmod -R 775 backend/storage

# 3. Tester manuellement
cd backend
php artisan schedule:run -v
```

### La commande échoue

```bash
# Mode debug
php artisan analytics:cache-daily -v

# Vérifier la connexion DB
php artisan tinker --execute="DB::connection()->getPdo();"
```

## 📚 Documentation

- **Guide complet** : `ANALYTICS_OPTIMIZATION.md`
- **Déploiement** : `PRODUCTION_DEPLOYMENT.md`
- **Scripts disponibles** :
  - `setup_cron.sh` : Configuration automatique du cron
  - `check_scheduler.sh` : Vérification complète
  - `backfill_analytics.sh/ps1` : Pré-calcul historique

## 🎉 Prochaines étapes

1. ✅ Déployer en production
2. ✅ Exécuter `setup_cron.sh` (ou configurer Docker)
3. ✅ Lancer le backfill : `./backfill_analytics.sh 30`
4. ✅ Vérifier le lendemain que le cache est mis à jour automatiquement
5. ✅ Monitorer les performances

---

**Configuration terminée !** Le système va maintenant :
- Importer les transactions tous les jours à 08:30
- Calculer les analytics tous les jours à 09:00
- Servir les données en < 100ms grâce au cache
