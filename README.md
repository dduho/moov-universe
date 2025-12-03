# Moov Money Universe

Plateforme de gestion des Points de Vente (PDV) Moov Money au Togo.

## Description

Cette application permet de créer et gérer une base de données des Points de Vente pour Moov Money, avec:
- Gestion des Dealers et leurs commerciaux
- Formulaire de création de PDV avec capture GPS
- Carte interactive des PDV (Leaflet/OpenStreetMap)
- Système d'alertes de proximité (< 300m)
- Workflow de validation par le staff Moov
- Export XML pour création d'organisations
- Statistiques complètes

## Stack Technique

- **Backend**: Laravel 10+ avec Sanctum pour l'authentification API
- **Frontend**: Vue.js 3 + Pinia + Tailwind CSS
- **Base de données**: MySQL 8.0
- **Carte**: Leaflet + OpenStreetMap
- **Conteneurisation**: Docker & Docker Compose

## Prérequis

- Docker & Docker Compose installés
- Git

OU pour installation locale:
- PHP 8.2+
- Composer
- Node.js 20+
- npm
- MySQL 8.0+

## Installation avec Docker (Recommandé)

### 1. Cloner le repository

```bash
git clone https://github.com/dduho/moov-universe.git
cd moov-universe
```

### 2. Configuration de l'environnement Backend

```bash
cd backend
cp .env.example .env
cd ..
```

### 3. Démarrer les services Docker

```bash
docker-compose up -d
```

Cette commande va:
- Créer et démarrer le conteneur MySQL
- Créer et démarrer le conteneur Laravel (backend)
- Créer et démarrer le conteneur Vue.js (frontend)
- Installer les dépendances
- Exécuter les migrations et seeders

### 4. Accéder à l'application

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000/api

### 5. Compte par défaut

- **Email**: admin@moov.tg
- **Mot de passe**: password

## Installation Locale (Sans Docker)

### Backend

```bash
cd backend

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env

# Générer la clé de l'application
php artisan key:generate

# Configurer la base de données dans .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moov_universe
DB_USERNAME=votre_user
DB_PASSWORD=votre_password

# Créer la base de données
mysql -u root -p
CREATE DATABASE moov_universe;
exit

# Exécuter les migrations et seeders
php artisan migrate:fresh --seed

# Démarrer le serveur
php artisan serve
```

### Frontend

```bash
cd frontend

# Installer les dépendances
npm install

# Configurer l'environnement
echo "VITE_API_URL=http://localhost:8000/api" > .env

# Démarrer le serveur de développement
npm run dev
```

## Structure du Projet

```
moov-universe/
├── backend/                    # Application Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/   # Contrôleurs API
│   │   │   └── Middleware/    # Middleware personnalisés
│   │   ├── Models/            # Modèles Eloquent
│   │   └── Services/          # Services métier
│   ├── database/
│   │   ├── migrations/        # Migrations de base de données
│   │   └── seeders/           # Seeders pour données initiales
│   └── routes/
│       └── api.php            # Routes API
├── frontend/                   # Application Vue.js
│   ├── src/
│   │   ├── components/        # Composants réutilisables
│   │   ├── views/             # Pages/Vues
│   │   ├── stores/            # Stores Pinia
│   │   ├── services/          # Services API
│   │   └── router/            # Configuration Vue Router
│   └── public/
└── docker-compose.yml          # Configuration Docker
```

## Système de Rôles

### 1. Administrateur Moov Money (admin)
- Accès complet à tous les PDV
- Validation/Rejet des soumissions
- Gestion des utilisateurs
- Statistiques globales
- Export des données

### 2. Dealer (dealer)
- Voir uniquement leurs PDV
- Créer des PDV
- Gérer leurs employés
- Tableau de bord de leur organisation

### 3. Commercial (commercial)
- Soumettre des formulaires de PDV
- Voir leurs PDV créés
- Modifier les PDV en attente

## Fonctionnalités Principales

### 1. Gestion des PDV
- Formulaire en 5 étapes:
  1. Informations Dealer
  2. Informations Gérant
  3. Localisation (GPS + hiérarchie administrative)
  4. Contacts et Fiscalité
  5. Visibilité et Validation
- Capture GPS avec vérification de précision (< 30m)
- Alertes de proximité (< 300m entre PDV validés)

### 2. Carte Interactive
- Visualisation de tous les PDV sur carte Leaflet
- Markers colorés par statut:
  - 🟢 Vert: Validé
  - 🟠 Orange: En attente
  - 🔴 Rouge: Rejeté
- Filtres par région, préfecture, dealer, statut

### 3. Workflow de Validation
- Soumission par commerciaux
- File de validation pour admins
- Validation/Rejet avec motif
- Notifications

### 4. Statistiques
- Dashboard avec KPIs
- Graphiques par région
- Graphiques par dealer (admin uniquement)
- Timeline des créations

### 5. Export
- Export XML pour intégration système
- Export CSV pour analyse
- Filtres personnalisables

## API Endpoints

### Authentification
- `POST /api/login` - Connexion
- `POST /api/logout` - Déconnexion
- `GET /api/me` - Utilisateur connecté

### Points de Vente
- `GET /api/point-of-sales` - Liste des PDV
- `POST /api/point-of-sales` - Créer un PDV
- `GET /api/point-of-sales/{id}` - Détails d'un PDV
- `PUT /api/point-of-sales/{id}` - Modifier un PDV
- `POST /api/point-of-sales/{id}/validate` - Valider (admin)
- `POST /api/point-of-sales/{id}/reject` - Rejeter (admin)
- `POST /api/point-of-sales/check-proximity` - Vérifier proximité

### Géographie
- `GET /api/geography/regions` - Liste des régions
- `GET /api/geography/prefectures` - Préfectures par région
- `GET /api/geography/communes` - Communes par préfecture
- `GET /api/geography/hierarchy` - Hiérarchie complète

### Statistiques
- `GET /api/statistics/dashboard` - Dashboard
- `GET /api/statistics/by-region` - Stats par région
- `GET /api/statistics/by-organization` - Stats par organisation (admin)

### Export
- `GET /api/export/xml` - Export XML
- `GET /api/export/csv` - Export CSV

## Hiérarchie Géographique du Togo

### Régions
1. **MARITIME**: Golfe, Agoè-Nyivé, Lacs, Vo, Yoto, Zio, Bas-Mono, Avé
2. **PLATEAUX**: Kloto, Agou, Akébou, Amou, Anié, Danyi, Est-Mono, Haho, Wawa
3. **CENTRALE**: Tchaoudjo, Blitta, Sotouboua, Tchamba, Mô
4. **KARA**: Kozah, Assoli, Bassar, Binah, Dankpen, Doufelgou, Kéran
5. **SAVANES**: Tône, Cinkassé, Kpendjal, Oti, Tandjouaré

## Charte Graphique Moov Money

### Couleurs
- **Orange Moov**: #FF6B00
- **Orange Clair**: #FF8C42
- **Orange Foncé**: #E55A00

### Utilisation dans Tailwind
```vue
<div class="bg-moov-orange text-white">
  <button class="hover:bg-moov-orange-dark">Action</button>
</div>
```

## Développement

### Commandes utiles Backend

```bash
# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Créer un modèle avec migration
php artisan make:model ModelName -m

# Créer un contrôleur
php artisan make:controller ControllerName

# Tests
php artisan test
```

### Commandes utiles Frontend

```bash
# Développement
npm run dev

# Build pour production
npm run build

# Preview build
npm run preview

# Linter
npm run lint
```

## Sécurité

- Authentification via Laravel Sanctum (tokens API)
- Middleware de vérification de rôles
- Middleware de vérification d'accès aux organisations
- Validation des données côté serveur
- Protection CSRF
- CORS configuré

## Support

Pour toute question ou problème, contacter l'équipe de développement.

## Licence

Propriétaire - Moov Money © 2025

