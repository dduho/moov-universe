# Optimisation Analytics - Guide d'utilisation

## 🚀 Système d'optimisation pour gros volumes (25 000+ PDV)

### 1. Installation automatique (Production)

**Déploiement complet avec configuration du scheduler** :
```bash
cd /path/to/moov-universe
chmod +x deploy.sh

# Déploiement standard (pré-calcule 7 derniers jours)
./deploy.sh

# Déploiement avec historique complet (30 jours)
./deploy.sh --init-analytics
```

Le script `deploy.sh` configure automatiquement :
- ✅ Crontab système pour Laravel Scheduler
- ✅ Pré-calcul des 7 derniers jours (première fois uniquement)
- ✅ Pré-calcul de 30 jours si option `--init-analytics`
- ✅ Vérification des tâches planifiées

### 2. Installation manuelle (Dev ou configuration custom)

Si vous ne pouvez pas utiliser `deploy.sh` :

```bash
cd backend
php artisan migrate
```

Cela va créer :
- Les index sur `pdv_transactions` (pdv_numero, transaction_date, etc.)
- La table `daily_analytics_cache` pour stocker les stats pré-calculées

### 2. Cache quotidien automatique

Pour pré-calculer les statistiques d'hier (à exécuter chaque jour via CRON) :

```bash
php artisan analytics:cache-daily
```

Pour une date spécifique :

```bash
php artisan analytics:cache-daily 2025-12-25
```

### 3. Configuration CRON (recommandé)

#### Option A : Serveur Linux/Unix (sans Docker)

**Automatique** :
```bash
chmod +x setup_cron.sh
./setup_cron.sh
```

**Manuel** :
```bash
crontab -e
```

Ajoutez cette ligne :
```bash
* * * * * cd /path/to/moov-universe/backend && php artisan schedule:run >> /dev/null 2>&1
```

Remplacez `/path/to/moov-universe` par le chemin absolu de votre projet.

#### Option B : Docker (Production)

Le scheduler est déjà configuré dans `docker-compose.yml`. Lancez simplement :
```bash
docker-compose up -d scheduler
```

#### Option C : Laravel Forge / Cloud

Sur Laravel Forge ou services cloud :
```bash
* * * * * cd /home/forge/moov-universe/backend && php artisan schedule:run >> /dev/null 2>&1
```

#### Vérifier la configuration

```bash
# Lister les tâches planifiées
cd backend
php artisan schedule:list

# Tester manuellement
php artisan schedule:run

# Vérifier le cron système
crontab -l

# Utiliser le script de vérification
chmod +x check_scheduler.sh
./check_scheduler.sh
```

**✅ Le scheduler Laravel exécutera automatiquement :**
- Import SFTP : Tous les jours à 08:30
- Cache Analytics : Tous les jours à 09:00 (juste après l'import)

### 4. Optimisations appliquées

#### a) Index de base de données
- `pdv_numero` : Recherches par PDV
- `transaction_date` : Filtres temporels
- `(pdv_numero, transaction_date)` : Requêtes combinées
- `(transaction_date, retrait_keycost)` : Analyses de CA

#### b) Agrégations SQL
- Toutes les statistiques calculées en SQL (SUM, COUNT, GROUP BY)
- Aucune donnée chargée en mémoire PHP
- Jointures optimisées pour Top PDV et Top Dealers

#### c) Cache multi-niveaux
- **Cache Laravel** : 15 minutes par période/date
- **Cache quotidien** : Table pré-agrégée pour les journées complètes
- Clés de cache uniques par période

#### d) Requêtes optimisées
- Top 10 PDV : 1 requête agrégée + 1 requête pour les infos PDV
- Top 10 Dealers : JOIN optimisé avec GROUP BY
- Évolution : DATE_FORMAT en SQL

### 5. Performance attendue

**Avant optimisation :**
- Charge 500 000+ lignes en mémoire
- Temps : 30-60 secondes
- RAM : 500 MB+

**Après optimisation :**
- Agrégation SQL pure
- Temps : < 2 secondes (sans cache), < 100ms (avec cache)
- RAM : < 50 MB

### 6. Maintenance

#### Recalculer plusieurs jours
```bash
# Recalculer les 30 derniers jours
for i in {1..30}; do
  php artisan analytics:cache-daily $(date -d "$i days ago" +%Y-%m-%d)
done
```

#### Vider le cache Laravel
```bash
php artisan cache:clear
```

#### Vérifier les données en cache
```bash
php artisan tinker
> \App\Models\DailyAnalyticsCache::latest()->first()
```

### 7. Monitoring

Pour monitorer les performances, ajoutez des logs :

```php
// Dans TransactionAnalyticsController
\Log::info("Analytics query took: " . $elapsed . "ms");
```

### 8. Sécurité

- ✅ Route protégée par middleware `admin`
- ✅ Cache avec clés uniques
- ✅ Pas d'injection SQL (Query Builder)
