#!/bin/bash
# Script pour configurer le cron Laravel en production

echo "🔧 Configuration du CRON pour Laravel Scheduler..."

# Déterminer le chemin absolu du projet
PROJECT_PATH=$(cd "$(dirname "$0")" && pwd)
BACKEND_PATH="$PROJECT_PATH/backend"

echo "📁 Projet: $PROJECT_PATH"
echo "📁 Backend: $BACKEND_PATH"

# Vérifier que php et artisan existent
if [ ! -f "$BACKEND_PATH/artisan" ]; then
    echo "❌ Erreur: artisan non trouvé dans $BACKEND_PATH"
    exit 1
fi

# Détecter le binaire PHP
PHP_BIN=$(which php)
if [ -z "$PHP_BIN" ]; then
    echo "❌ Erreur: PHP non trouvé dans le PATH"
    exit 1
fi

echo "✅ PHP trouvé: $PHP_BIN"

# Créer la ligne crontab
CRON_LINE="* * * * * cd $BACKEND_PATH && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"

echo ""
echo "📋 Ligne crontab à ajouter:"
echo "$CRON_LINE"
echo ""

# Vérifier si le cron existe déjà
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo "⚠️  Un cron Laravel existe déjà:"
    crontab -l | grep "artisan schedule:run"
    echo ""
    read -p "Voulez-vous le remplacer ? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Configuration annulée"
        exit 0
    fi
    # Supprimer l'ancien cron
    crontab -l | grep -v "artisan schedule:run" | crontab -
fi

# Ajouter le nouveau cron
(crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -

echo ""
echo "✅ Cron configuré avec succès !"
echo ""
echo "📅 Tâches planifiées:"
echo "  - Import SFTP: Tous les jours à 08:30"
echo "  - Cache Analytics: Tous les jours à 09:00 (après import)"
echo ""
echo "🔍 Pour vérifier le cron:"
echo "  crontab -l"
echo ""
echo "📊 Pour tester manuellement:"
echo "  cd $BACKEND_PATH && php artisan schedule:run"
echo ""
echo "📝 Logs analytics disponibles dans:"
echo "  $BACKEND_PATH/storage/logs/analytics-cache.log"
echo ""
