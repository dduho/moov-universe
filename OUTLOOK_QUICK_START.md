# Checklist Outlook OAuth2 - Implémentation Rapide

## 1. ✅ Code créé (FAIT)

```
backend/
├── database/migrations/
│   ├── 2026_05_18_000001_create_oauth_tokens_table.php
│   └── 2026_05_18_000002_create_outlook_import_history_table.php
├── app/
│   ├── Models/
│   │   ├── OAuthToken.php
│   │   └── OutlookImportHistory.php
│   ├── Services/
│   │   └── OutlookGraphService.php
│   ├── Http/Controllers/
│   │   └── OutlookOAuthController.php
│   └── Console/Commands/
│       └── ImportOutlookTransactions.php
├── config/
│   └── services.php (mise à jour)
├── routes/
│   └── api.php (mise à jour)
├── app/Console/
│   └── Kernel.php (mise à jour - scheduler)
├── .env.example (mise à jour)
└── OUTLOOK_OAUTH_INTEGRATION.md (documentation complète)
```

## 2. 📋 Étapes de déploiement

### A. Backend setup (terminal)
```bash
# 1. Migrer les tables
php artisan migrate

# 2. Vérifier que les routes existent
php artisan route:list | grep oauth

# 3. Tester la commande (optionnel, avant autorisation)
php artisan transactions:import-outlook
# → Erreur attendue: "No OAuth token found" (normal, pas encore autorisé)
```

### B. Configuration .env
```bash
# Copier dans .env (remplacer par tes vraies valeurs):
AZURE_TENANT_ID=f91b4edd-ae9e-48df-8671-c46c9fa39a0f
AZURE_CLIENT_ID=67ab8307-2d99-43bd-b64a-02073ce61e3a
AZURE_CLIENT_SECRET=1fb6285b-35d5-401f-a855-ca3c3f029185
APP_URL=https://universe.moov-africa.tg

OUTLOOK_MAILBOX_EMAIL=dduho@moov-africa.tg
OUTLOOK_MAIL_FOLDER="FLOOZ TOGO"
OUTLOOK_SUBJECT_FILTER="Agents consolidated reporting"
OUTLOOK_FILENAME_PATTERN="All Agent Consolidated Report_*.xlsx"
OUTLOOK_ALLOWED_EXTENSIONS=xlsx,xls
OUTLOOK_MAX_FILE_MB=500
OUTLOOK_MARK_AS_READ=true
OUTLOOK_MOVE_PROCESSED_TO=Processed
OUTLOOK_MOVE_FAILED_TO=Failed

OUTLOOK_IMPORT_ENABLED=true
OUTLOOK_IMPORT_TIME=08:30
OUTLOOK_IMPORT_TIMEZONE=Africa/Douala
```

### C. Autorisation OAuth (1 fois)
```bash
# 1. Ouvrir dans un navigateur:
https://universe.moov-africa.tg/api/oauth/authorize

# 2. L'utilisateur (dduho) se connecte
# 3. Accepte les permissions (Mail.Read, Mail.ReadWrite)
# 4. Redirect automatique vers /oauth/callback
# 5. Token de rafraîchissement stocké en base de données ✅

# 6. Vérifier le status (en curl ou via admin panel):
curl -X GET "https://universe.moov-africa.tg/api/oauth/status" \
  -H "Authorization: Bearer {admin_token}"
```

## 3. 🧪 Test

### Test manuel de la commande
```bash
# Récupérer 1 message non lu, importer, marquer comme lu, archiver
php artisan transactions:import-outlook

# Sortie attendue:
# Starting Outlook transaction import from dduho@moov-africa.tg
# Found N message(s) to process
# Processing message: [subject]
# ├─ Downloading: filename.xlsx
# └─ ✓ Import succeeded: X imported, Y updated, Z skipped
# Import completed: N success, 0 failures
```

### Vérifier la base de données
```sql
-- Voir les tokens stockés
SELECT * FROM oauth_tokens WHERE provider='outlook';

-- Voir l'historique d'imports
SELECT * FROM outlook_import_history
ORDER BY created_at DESC
LIMIT 10;

-- Compter les succès/erreurs
SELECT status, COUNT(*) as count
FROM outlook_import_history
GROUP BY status;
```

## 4. ⏰ Scheduler

La commande s'exécute automatiquement:
- **Quand**: Tous les jours à 08:30 (configurable: OUTLOOK_IMPORT_TIME)
- **Fuseau horaire**: Africa/Douala (configurable: OUTLOOK_IMPORT_TIMEZONE)
- **Logs**: `storage/logs/outlook-import.log`

Pour tester le scheduler:
```bash
# Simuler une exécution du scheduler (5 min de simulation):
php artisan schedule:run

# ou forcer l'exécution manuelle:
php artisan transactions:import-outlook
```

## 5. 🔍 Monitoring

### Endpoints d'administration
```bash
# Voir le status OAuth (nécessite token admin)
GET /api/oauth/status

# Révoquer le token (supprimer et relancer l'autorisation)
DELETE /api/oauth/token

# Historique imports
GET /api/activity-logs?action=import&entity=transaction
```

### Logs à vérifier
```bash
# Logs cron quotidiens
tail -f storage/logs/outlook-import.log

# Logs erreurs
tail -f storage/logs/laravel.log | grep Outlook

# Query base (PHP artisan tinker)
php artisan tinker
>>> OutlookImportHistory::where('status', 'failed')->get(['message_id', 'error_message']);
```

## 6. 🆘 Troubleshooting rapide

| Erreur | Cause | Fix |
|--------|-------|-----|
| `No OAuth token found` | Pas d'autorisation | Cliquer `/api/oauth/authorize` |
| `Token expired` | Refresh token expiré | Relancer autorisation |
| `Folder not found` | Dossier mal nommé | Vérifier exact nom "FLOOZ TOGO" |
| `Filename doesn't match pattern` | Format fichier incorrect | Doit être `All Agent Consolidated Report_*.xlsx` |
| `HTTP 401` | Access token expiré | Auto-rafraîchi, relancer si problème |

## 7. 📦 Déploiement production

```bash
# Sur serveur production:
1. php artisan migrate              # Créer tables
2. .env rempli (secrets sécurisés) # Via vault ou env vars
3. Autorisation OAuth              # Un admin clique authorize link
4. Vérifier cron (Laravel Cron)    # `php artisan schedule:run` en cron minutaire
5. Monitorage                       # Logs + DB queries
```

## 8. 🎯 Démarrage rapide (TL;DR)

```bash
# 1. Migrer
php artisan migrate

# 2. Configurer .env (copier-coller les 15 variables Outlook)

# 3. Autoriser (1 fois):
# Ouvrir: https://universe.moov-africa.tg/api/oauth/authorize

# 4. Activer
# OUTLOOK_IMPORT_ENABLED=true

# 5. Test
php artisan transactions:import-outlook

# ✅ Done! Le cron s'exécutera chaque jour à 08:30
```

---

**Questions?** Voir OUTLOOK_OAUTH_INTEGRATION.md pour tous les détails.
