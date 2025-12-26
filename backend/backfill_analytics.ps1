# Script PowerShell pour pré-calculer les statistiques de plusieurs jours
# Usage: .\backfill_analytics.ps1 -Days 30

param (
    [int]$Days = 30
)

Write-Host "🔄 Pré-calcul des analytics pour les $Days derniers jours..." -ForegroundColor Cyan

for ($i = 0; $i -le $Days; $i++) {
    $date = (Get-Date).AddDays(-$i).ToString("yyyy-MM-dd")
    Write-Host "📊 Calcul pour $date..." -ForegroundColor Yellow
    php artisan analytics:cache-daily $date
}

Write-Host "✅ Terminé !" -ForegroundColor Green
