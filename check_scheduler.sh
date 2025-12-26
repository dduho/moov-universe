#!/bin/bash
# Script pour vérifier la configuration du scheduler Laravel

echo "🔍 Vérification de la configuration du Scheduler Laravel"
echo "=========================================================="
echo ""

cd backend

# 1. Vérifier que le cron système est configuré
echo "1️⃣  Vérification du crontab système..."
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo "   ✅ Crontab configuré:"
    crontab -l | grep "artisan schedule:run"
else
    echo "   ❌ Crontab NON configuré !"
    echo "   👉 Exécutez: ./setup_cron.sh"
fi
echo ""

# 2. Lister les tâches planifiées
echo "2️⃣  Tâches planifiées dans Laravel:"
php artisan schedule:list
echo ""

# 3. Tester l'exécution du scheduler
echo "3️⃣  Test d'exécution du scheduler..."
php artisan schedule:run -v
echo ""

# 4. Vérifier les logs
echo "4️⃣  Derniers logs analytics (si disponibles):"
if [ -f "storage/logs/analytics-cache.log" ]; then
    echo "   📝 Contenu de analytics-cache.log:"
    tail -n 20 storage/logs/analytics-cache.log
else
    echo "   ⚠️  Aucun log encore créé (normal si jamais exécuté)"
fi
echo ""

# 5. Vérifier les données en cache
echo "5️⃣  Vérification du cache quotidien:"
php artisan tinker --execute="
\$latest = \App\Models\DailyAnalyticsCache::latest('date')->first();
if (\$latest) {
    echo '   ✅ Dernière entrée: ' . \$latest->date . '\n';
    echo '   💰 CA: ' . number_format(\$latest->total_ca, 2) . ' XOF\n';
    echo '   📊 Transactions: ' . number_format(\$latest->total_transactions) . '\n';
    echo '   🏪 PDV actifs: ' . number_format(\$latest->pdv_actifs) . '\n';
} else {
    echo '   ⚠️  Aucune donnée en cache\n';
    echo '   👉 Exécutez: php artisan analytics:cache-daily\n';
}
"
echo ""

# 6. Recommandations
echo "📋 Recommandations pour la production:"
echo "   1. Vérifier que le cron est bien dans crontab: crontab -l"
echo "   2. Monitorer les logs: tail -f storage/logs/analytics-cache.log"
echo "   3. Vérifier l'exécution quotidienne"
echo "   4. S'assurer que le timezone du serveur est correct"
echo ""
echo "⏰ Timezone PHP actuel:"
php -r "echo date_default_timezone_get() . '\n';"
echo ""
