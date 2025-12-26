#!/bin/bash
# Script pour pré-calculer les statistiques de plusieurs jours
# Usage: ./backfill_analytics.sh [nombre_de_jours]

DAYS=${1:-30}  # Par défaut 30 jours

# Déterminer le répertoire du script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "🔄 Pré-calcul des analytics pour les $DAYS derniers jours..."

for i in $(seq 0 $DAYS); do
  DATE=$(date -d "$i days ago" +%Y-%m-%d)
  echo "📊 Calcul pour $DATE..."
  php artisan analytics:cache-daily $DATE
done

echo "✅ Terminé !"
