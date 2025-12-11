#!/bin/bash

#=============================================================================
# Script de déploiement - Moov Universe
# Usage: ./deploy.sh [options]
# Options:
#   --backend-only    Déploie uniquement le backend
#   --frontend-only   Déploie uniquement le frontend
#   --no-migrate      Ne pas exécuter les migrations
#   --fresh-migrate   Réinitialise la base de données (ATTENTION: perte de données)
#=============================================================================

set -e  # Arrêter le script en cas d'erreur

# ============================================
# CONFIGURATION - À MODIFIER SELON VOTRE SERVEUR
# ============================================

# Répertoire du projet
PROJECT_DIR="/data/www/moov-universe"

# Répertoire du backend Laravel
BACKEND_DIR="$PROJECT_DIR/backend"

# Répertoire du frontend Vue.js
FRONTEND_DIR="$PROJECT_DIR/frontend"

# Répertoire où Nginx sert le frontend (dist)
FRONTEND_PUBLIC_DIR="/data/www/moov-universe/frontend/dist"

# Branche Git à déployer
GIT_BRANCH="main"

# Utilisateur web (www-data pour Nginx/Apache sur Ubuntu)
WEB_USER="www-data"
WEB_GROUP="www-data"

# URL de l'API pour le frontend
API_URL="https://10.80.16.51:8443/api"

# Désactiver PWA (true si certificat SSL auto-signé)
DISABLE_PWA="true"

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================
# FONCTIONS
# ============================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

show_banner() {
    echo ""
    echo -e "${BLUE}╔═══════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║         🚀 MOOV UNIVERSE - DÉPLOIEMENT 🚀             ║${NC}"
    echo -e "${BLUE}╚═══════════════════════════════════════════════════════╝${NC}"
    echo ""
}

check_requirements() {
    log_info "Vérification des prérequis..."
    
    command -v git >/dev/null 2>&1 || { log_error "Git n'est pas installé"; exit 1; }
    command -v php >/dev/null 2>&1 || { log_error "PHP n'est pas installé"; exit 1; }
    command -v composer >/dev/null 2>&1 || { log_error "Composer n'est pas installé"; exit 1; }
    command -v node >/dev/null 2>&1 || { log_error "Node.js n'est pas installé"; exit 1; }
    command -v npm >/dev/null 2>&1 || { log_error "NPM n'est pas installé"; exit 1; }
    
    log_success "Tous les prérequis sont installés"
}

pull_latest_code() {
    log_info "Récupération du code depuis Git..."
    
    cd "$PROJECT_DIR"
    
    # Ajouter le répertoire aux répertoires sûrs pour Git
    sudo git config --global --add safe.directory $PROJECT_DIR
    
    # S'assurer que les permissions du dossier .git sont correctes
    if [ -d ".git" ]; then
        sudo chown -R $WEB_USER:$WEB_GROUP .git
        sudo chmod -R u+w .git
    fi
    
    # Sauvegarder les modifications locales si nécessaire
    if [[ -n $(git status -s) ]]; then
        log_warning "Modifications locales détectées, création d'un stash..."
        git stash
    fi
    
    git fetch origin
    git checkout $GIT_BRANCH
    git pull origin $GIT_BRANCH
    
    # Corriger les permissions après le pull
    sudo chown -R $WEB_USER:$WEB_GROUP .
    
    # Garder .git pour l'utilisateur de déploiement
    sudo chown -R moov:moov .git
    
    log_success "Code mis à jour depuis la branche $GIT_BRANCH"
}

deploy_backend() {
    log_info "Déploiement du backend Laravel..."
    
    cd "$BACKEND_DIR"
    
    # Mode maintenance
    log_info "Activation du mode maintenance..."
    php artisan down || true
    
    # Installation des dépendances
    log_info "Installation des dépendances Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Génération de la clé d'application si nécessaire
    log_info "Vérification de la clé d'application..."
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        php artisan key:generate --force
        log_success "Clé d'application générée"
    fi
    
    # Migrations
    if [[ "$NO_MIGRATE" != "true" ]]; then
        if [[ "$FRESH_MIGRATE" == "true" ]]; then
            log_warning "Réinitialisation de la base de données..."
            php artisan migrate:fresh --seed --force
        else
            log_info "Exécution des migrations..."
            php artisan migrate --force
        fi
    else
        log_warning "Migrations ignorées (--no-migrate)"
    fi
    
    # Optimisations Laravel
    log_info "Optimisation de Laravel..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    
    # Nettoyage des caches obsolètes
    php artisan cache:clear
    
    # Liens de stockage
    php artisan storage:link 2>/dev/null || true
    
    # Permissions
    log_info "Configuration des permissions..."
    sudo chown -R $WEB_USER:$WEB_GROUP storage bootstrap/cache
    sudo chmod -R 775 storage bootstrap/cache
    
    # Désactivation du mode maintenance
    log_info "Désactivation du mode maintenance..."
    php artisan up
    
    log_success "Backend déployé avec succès"
}

deploy_frontend() {
    log_info "Déploiement du frontend Vue.js..."
    
    cd "$FRONTEND_DIR"
    
    # Création du fichier .env pour la production
    log_info "Configuration de l'environnement..."
    cat > .env.production << EOF
VITE_API_URL=$API_URL
VITE_DISABLE_PWA=$DISABLE_PWA
EOF
    
    # Installation des dépendances
    log_info "Installation des dépendances NPM..."
    npm ci --production=false
    
    # Build de production
    log_info "Build de production..."
    npm run build
    
    # Suppression des fichiers Service Worker si PWA désactivé
    if [[ "$DISABLE_PWA" == "true" ]]; then
        log_info "Suppression des fichiers Service Worker..."
        rm -f "$FRONTEND_DIR/dist/sw.js" 2>/dev/null || true
        rm -f "$FRONTEND_DIR/dist/workbox-"*.js 2>/dev/null || true
        rm -f "$FRONTEND_DIR/dist/registerSW.js" 2>/dev/null || true
    fi
    
    # Permissions
    log_info "Configuration des permissions..."
    sudo chown -R $WEB_USER:$WEB_GROUP "$FRONTEND_DIR/dist"
    sudo chmod -R 755 "$FRONTEND_DIR/dist"
    
    log_success "Frontend déployé avec succès"
}

reload_services() {
    log_info "Redémarrage des services..."
    
    # Redémarrer PHP-FPM si installé
    if systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
        systemctl restart php8.3-fpm
        log_success "PHP-FPM 8.3 redémarré"
    elif systemctl is-active --quiet php8.2-fpm 2>/dev/null; then
        systemctl restart php8.2-fpm
        log_success "PHP-FPM 8.2 redémarré"
    elif systemctl is-active --quiet php8.1-fpm 2>/dev/null; then
        systemctl restart php8.1-fpm
        log_success "PHP-FPM 8.1 redémarré"
    elif systemctl is-active --quiet php-fpm 2>/dev/null; then
        systemctl restart php-fpm
        log_success "PHP-FPM redémarré"
    fi
    
    # Redémarrer Nginx
    if systemctl is-active --quiet nginx; then
        nginx -t && systemctl restart nginx
        log_success "Nginx redémarré"
    fi
    
    # Redémarrer Apache si utilisé
    if systemctl is-active --quiet apache2; then
        systemctl restart apache2
        log_success "Apache redémarré"
    fi
}

show_summary() {
    echo ""
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║           ✅ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS          ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "📁 Projet: ${BLUE}$PROJECT_DIR${NC}"
    echo -e "🌿 Branche: ${BLUE}$GIT_BRANCH${NC}"
    echo -e "🕐 Date: ${BLUE}$(date '+%Y-%m-%d %H:%M:%S')${NC}"
    echo ""
}

# ============================================
# TRAITEMENT DES ARGUMENTS
# ============================================

BACKEND_ONLY=false
FRONTEND_ONLY=false
NO_MIGRATE=false
FRESH_MIGRATE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --backend-only)
            BACKEND_ONLY=true
            shift
            ;;
        --frontend-only)
            FRONTEND_ONLY=true
            shift
            ;;
        --no-migrate)
            NO_MIGRATE=true
            shift
            ;;
        --fresh-migrate)
            FRESH_MIGRATE=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --backend-only    Déploie uniquement le backend"
            echo "  --frontend-only   Déploie uniquement le frontend"
            echo "  --no-migrate      Ne pas exécuter les migrations"
            echo "  --fresh-migrate   Réinitialise la base de données (ATTENTION!)"
            echo "  --help, -h        Affiche cette aide"
            exit 0
            ;;
        *)
            log_error "Option inconnue: $1"
            exit 1
            ;;
    esac
done

# ============================================
# EXÉCUTION PRINCIPALE
# ============================================

show_banner

check_requirements
pull_latest_code

if [[ "$FRONTEND_ONLY" == "true" ]]; then
    deploy_frontend
elif [[ "$BACKEND_ONLY" == "true" ]]; then
    deploy_backend
else
    deploy_backend
    deploy_frontend
fi

reload_services
show_summary
