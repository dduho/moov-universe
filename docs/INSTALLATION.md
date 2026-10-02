# Guide d'Installation - Moov Money Universe

Ce guide vous accompagne dans l'installation et la configuration de la plateforme Moov Money Universe.

## Table des Matières

1. [Prérequis](#prérequis)
2. [Installation avec Docker (Recommandé)](#installation-avec-docker-recommandé)
3. [Installation Locale](#installation-locale)
4. [Configuration](#configuration)
5. [Premiers Pas](#premiers-pas)
6. [Dépannage](#dépannage)

## Prérequis

### Pour installation avec Docker
- Docker Desktop (Windows/Mac) ou Docker Engine (Linux)
- Docker Compose v2.0+
- Git
- Au minimum 4GB de RAM disponible
- 10GB d'espace disque

### Pour installation locale
- PHP 8.2 ou supérieur avec extension GD activée
- Composer 2.0+
- Node.js 20+ et npm
- MySQL 8.0+
- Git

## Installation avec Docker (Recommandé)

### Étape 1: Cloner le repository

```bash
git clone https://github.com/dduho/moov-universe.git
cd moov-universe
```

### Étape 2: Configurer l'environnement backend

```bash
cd backend
cp .env.example .env
```

Ouvrez le fichier `.env` et vérifiez que ces variables sont correctes:

```env
APP_NAME="Moov Money Universe"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=moov_universe
DB_USERNAME=moov_user
DB_PASSWORD=moov_password

SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:3000
FRONTEND_URL=http://localhost:5173
```

### Étape 3: Retourner au répertoire racine

```bash
cd ..
```

### Étape 4: Lancer les conteneurs Docker

```bash
docker-compose up -d
```

Cette commande va:
1. Télécharger les images Docker nécessaires
2. Créer un conteneur MySQL avec la base de données
3. Construire et démarrer le conteneur backend Laravel
4. Construire et démarrer le conteneur frontend Vue.js
5. Installer automatiquement toutes les dépendances
6. Exécuter les migrations de base de données
7. Peupler la base avec les données initiales (seeders)

### Étape 5: Vérifier que tout fonctionne

Vérifier les logs des conteneurs:

```bash
# Logs de tous les services
docker-compose logs

# Logs du backend uniquement
docker-compose logs backend

# Logs du frontend uniquement
docker-compose logs frontend

# Logs de la base de données
docker-compose logs db
```

### Étape 6: Accéder à l'application

- **Frontend**: Ouvrez http://localhost:5173 dans votre navigateur
- **Backend API**: http://localhost:8000/api
- **Base de données**: localhost:3306

### Étape 7: Se connecter

Utilisez les identifiants par défaut:
- **Email**: admin@moov.tg
- **Mot de passe**: password

## Installation Locale

### Backend Laravel

#### 1. Vérifier les prérequis PHP

Assurez-vous que l'extension GD est activée:

```bash
php -m | grep gd
```

Si GD n'apparaît pas, éditez votre fichier `php.ini` et décommentez:

```ini
extension=gd
```

Localisez votre php.ini avec:

```bash
php --ini
```

#### 2. Installation des dépendances

```bash
cd backend
composer install
```

#### 3. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

#### 4. Configuration de la base de données

Créez la base de données MySQL:

```bash
mysql -u root -p
```

Dans le shell MySQL:

```sql
CREATE DATABASE moov_universe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moov_user'@'localhost' IDENTIFIED BY 'moov_password';
GRANT ALL PRIVILEGES ON moov_universe.* TO 'moov_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Modifiez le fichier `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moov_universe
DB_USERNAME=moov_user
DB_PASSWORD=moov_password
```

#### 5. Exécuter les migrations

```bash
php artisan migrate:fresh --seed
```

Cette commande va:
- Créer toutes les tables
- Insérer les rôles (admin, dealer, commercial)
- Créer l'utilisateur admin par défaut
- Insérer la hiérarchie géographique du Togo
- Configurer les paramètres système

#### 6. Démarrer le serveur

```bash
php artisan serve
```

Le backend sera accessible sur http://localhost:8000

### Frontend Vue.js

#### 1. Installation des dépendances

Ouvrez un nouveau terminal:

```bash
cd frontend
npm install
```

#### 2. Configuration de l'environnement

Créez le fichier `.env`:

```bash
echo "VITE_API_URL=http://localhost:8000/api" > .env
```

#### 3. Démarrer le serveur de développement

```bash
npm run dev
```

Le frontend sera accessible sur http://localhost:5173

## Configuration

### Paramètres système

Les paramètres système sont stockés dans la table `system_settings`. Vous pouvez les modifier via la base de données ou créer une interface d'administration.

#### Distance d'alerte de proximité

Par défaut: 300 mètres

Pour modifier:

```sql
UPDATE system_settings 
SET value = '500' 
WHERE key = 'proximity_alert_distance';
```

#### Précision GPS maximale

Par défaut: 30 mètres

```sql
UPDATE system_settings 
SET value = '50' 
WHERE key = 'gps_accuracy_max';
```

### Ajouter des utilisateurs

Vous pouvez créer des utilisateurs via l'API ou directement en base:

```php
php artisan tinker

// Créer un dealer
$dealer = App\Models\User::create([
    'name' => 'Nom du Dealer',
    'email' => 'dealer@example.com',
    'password' => bcrypt('password'),
    'role_id' => 2, // Role dealer
    'organization_id' => 1, // ID de l'organisation
    'is_active' => true,
]);
```

### Ajouter des organisations

```php
php artisan tinker

$org = App\Models\Organization::create([
    'name' => 'Nom de l\'organisation',
    'code' => 'ORG001',
    'phone' => '+228XXXXXXXX',
    'email' => 'contact@org.com',
    'address' => 'Adresse complète',
    'is_active' => true,
]);
```

## Premiers Pas

### 1. Se connecter en tant qu'admin

- Accédez à http://localhost:5173
- Connectez-vous avec admin@moov.tg / password
- Vous arrivez sur le tableau de bord

### 2. Créer une organisation (Admin uniquement)

Les organisations représentent les dealers:

1. (À implémenter) Interface d'administration des organisations
2. Pour l'instant, utilisez PHP Artisan Tinker ou SQL direct

### 3. Créer un utilisateur dealer

1. Créez une organisation
2. Créez un utilisateur avec le rôle "dealer" lié à cette organisation

### 4. Importer des PDV en masse (Admin uniquement)

1. Connectez-vous en tant qu'admin
2. Allez dans le menu Administration > Importer PDV
3. Téléchargez le modèle Excel
4. Remplissez le fichier avec vos données
5. Sélectionnez le dealer auquel attribuer les PDV
6. Glissez-déposez le fichier ou cliquez pour le sélectionner
7. Vérifiez la prévisualisation (valides/invalides/doublons)
8. Confirmez l'import
9. Tous les PDV importés seront automatiquement validés

### 5. Créer un PDV manuellement

1. Connectez-vous en tant que dealer ou commercial
2. Cliquez sur "Créer un PDV"
3. Remplissez le formulaire en 5 étapes
4. Le PDV sera créé avec le statut "pending"

### 6. Valider un PDV (Admin)

1. Connectez-vous en tant qu'admin
2. Allez dans "Validation"
3. Sélectionnez un PDV en attente
4. Cliquez sur "Valider" ou "Rejeter"

## Dépannage

### Docker

#### Les conteneurs ne démarrent pas

```bash
# Arrêter tous les conteneurs
docker-compose down

# Supprimer les volumes (ATTENTION: Perte de données)
docker-compose down -v

# Reconstruire les images
docker-compose build --no-cache

# Redémarrer
docker-compose up -d
```

#### Erreur de connexion à la base de données

Vérifiez que le conteneur MySQL est bien démarré:

```bash
docker-compose ps
```

Si le conteneur `moov_db` est en erreur, regardez les logs:

```bash
docker-compose logs db
```

#### Le backend ne répond pas

```bash
# Redémarrer le backend
docker-compose restart backend

# Voir les logs
docker-compose logs -f backend
```

### Installation locale

#### Erreur de migration

```bash
# Réinitialiser la base de données
php artisan migrate:fresh --seed
```

#### Erreur de clé

```bash
# Régénérer la clé
php artisan key:generate
```

#### CORS errors

Vérifiez que `SANCTUM_STATEFUL_DOMAINS` et `FRONTEND_URL` sont correctement configurés dans `.env`:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

#### Port déjà utilisé

Si le port 8000 ou 5173 est déjà utilisé:

**Backend:**
```bash
php artisan serve --port=8001
```

Et modifiez `VITE_API_URL` dans le frontend:
```env
VITE_API_URL=http://localhost:8001/api
```

**Frontend:**
```bash
npm run dev -- --port 5174
```

## Commandes Utiles

### Docker

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Voir les logs
docker-compose logs -f

# Reconstruire les images
docker-compose build

# Exécuter des commandes dans un conteneur
docker-compose exec backend php artisan migrate
docker-compose exec frontend npm run build
```

### Laravel

```bash
# Migrations
php artisan migrate
php artisan migrate:fresh --seed
php artisan migrate:rollback

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Tinker (console interactive)
php artisan tinker
```

### Vue.js

```bash
# Développement
npm run dev

# Build production
npm run build

# Preview production build
npm run preview
```

## Support

Pour toute question ou problème:
1. Vérifiez les logs
2. Consultez ce guide de dépannage
3. Contactez l'équipe de développement

## Prochaines Étapes

Après l'installation réussie:

1. Familiarisez-vous avec l'interface
2. Créez quelques organisations de test
3. Créez des utilisateurs de test pour chaque rôle
4. Testez le workflow complet de création et validation de PDV
5. Explorez la carte interactive
6. Testez les exports XML/CSV

Bon développement! 🚀
