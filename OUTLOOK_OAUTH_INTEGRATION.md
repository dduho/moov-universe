# Microsoft Outlook Integration - Transaction Import via OAuth2

Cette documentation couvre l'intégration OAuth2 Microsoft Graph pour importer automatiquement les fichiers de transactions depuis une boîte mail Outlook.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Outlook Mailbox                          │
│              dduho@moov-africa.tg                           │
│         (FLOOZ TOGO folder)                                │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────▼─────────────┐
        │  Microsoft Graph API      │
        │ (OAuth2 Access Token)     │
        └────────────┬──────────────┘
                     │
        ┌────────────▼────────────────────────────┐
        │  ImportOutlookTransactions Command      │
        │  (cron daily @ 08:30)                   │
        └────────────┬─────────────────────────────┘
                     │
        ┌────────────▼────────────────────────────┐
        │  TransactionImportController             │
        │  (parseExcel + upsert)                   │
        └────────────┬─────────────────────────────┘
                     │
        ┌────────────▼──────────────────────────────┐
        │  PdvTransaction Table                     │
        │  + OutlookImportHistory (audit)           │
        └───────────────────────────────────────────┘
```

## Configuration Steps

### 1. Remplir les variables d'environnement

Copie ta configuration fournie dans `.env`:

```bash
# Azure OAuth2
AZURE_TENANT_ID=f91b4edd-ae9e-48df-8671-c46c9fa39a0f
AZURE_CLIENT_ID=67ab8307-2d99-43bd-b64a-02073ce61e3a
AZURE_CLIENT_SECRET=1fb6285b-35d5-401f-a855-ca3c3f029185

# Outlook Mailbox
OUTLOOK_MAILBOX_EMAIL=dduho@moov-africa.tg
OUTLOOK_MAIL_FOLDER="FLOOZ TOGO"
OUTLOOK_SUBJECT_FILTER="Agents consolidated reporting"
OUTLOOK_FILENAME_PATTERN="All Agent Consolidated Report_*.xlsx"
OUTLOOK_ALLOWED_EXTENSIONS=xlsx,xls
OUTLOOK_MAX_FILE_MB=500
OUTLOOK_MARK_AS_READ=true
OUTLOOK_MOVE_PROCESSED_TO=Processed
OUTLOOK_MOVE_FAILED_TO=Failed

# Enable Outlook import
OUTLOOK_IMPORT_ENABLED=true
OUTLOOK_IMPORT_TIME=08:30
OUTLOOK_IMPORT_TIMEZONE=Africa/Douala

# APP_URL doit être correct (utilisé pour le callback OAuth)
APP_URL=https://universe.moov-africa.tg
```

### 2. Exécuter les migrations

```bash
php artisan migrate
```

Cela crée deux tables:
- `oauth_tokens`: stocke les tokens de rafraîchissement OAuth
- `outlook_import_history`: historique complet des imports avec métadonnées

### 3. Configurer le consentement OAuth (une seule fois)

Un utilisateur avec accès à la boîte mail doit donner son consentement à l'application:

1. Ouvre le lien d'autorisation:
```
https://universe.moov-africa.tg/api/oauth/authorize
```

2. L'utilisateur se connecte à Microsoft avec `dduho@moov-africa.tg`.

3. Microsoft demande le consentement pour les permissions Mail.Read + Mail.ReadWrite.

4. L'utilisateur clique "Accepter".

5. L'application reçoit automatiquement un token de rafraîchissement qui sera stocké en base.

**C'est terminé!** Le processus n'a besoin d'être fait qu'une seule fois.

### 4. Vérifier le status OAuth

Endpoint admin pour vérifier que le token est bien stocké:

```bash
curl -X GET "https://universe.moov-africa.tg/api/oauth/status" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

Réponse:
```json
{
  "status": "configured",
  "mailbox": "dduho@moov-africa.tg",
  "access_token_valid": true,
  "refresh_token_valid": true,
  "access_token_expires_at": "2026-05-18T15:30:00Z",
  "last_used_at": "2026-05-18T14:25:00Z"
}
```

## Execution Flow

### Automatique (Cron Journalière)

La commande s'exécute automatiquement chaque jour à **08:30** (configurable):

```bash
# Manuellement:
php artisan transactions:import-outlook
```

**Flux:**
1. Connecte à Microsoft Graph avec le Refresh Token stocké
2. Récupère les messages non lus du dossier "FLOOZ TOGO"
3. Filtre par sujet: "Agents consolidated reporting"
4. Pour chaque message, télécharge les pièces jointes
5. Valide le nom du fichier contre le pattern: `All Agent Consolidated Report_*.xlsx`
6. Importe le fichier Excel via la logique existante (`TransactionImportController`)
7. Marque le message comme lu
8. Déplace vers le dossier "Processed"
9. Enregistre l'historique en base

### Gestion des erreurs

Si l'import échoue:
- Le message reste non lu (sauf si OUTLOOK_MARK_AS_READ=false)
- Le message est déplacé vers le dossier "Failed"
- L'erreur est loggée en base dans `outlook_import_history` avec status="failed"
- La commande cron continue traiter les autres messages

### Historique complet

La table `outlook_import_history` enregistre:
- `message_id`: ID Microsoft pour déduplication
- `filename`, `subject`: métadonnées du mail
- `file_size_bytes`, `file_hash`: SHA256 pour audit
- `received_at`, `processed_at`: timestamps
- `status`: success, failed, partial
- `transactions_imported`, `transactions_updated`, `transactions_skipped`: résultats
- `error_message`: si erreur
- `retry_count`, `last_retry_at`: pour les retries

Query historique:
```sql
SELECT * FROM outlook_import_history
WHERE mailbox = 'dduho@moov-africa.tg'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY created_at DESC;
```

## Classes créées

### Services
- **OutlookGraphService** (`app/Services/OutlookGraphService.php`)
  - Gère l'authentification OAuth2
  - Rafraîchit les tokens automatiquement
  - Interface avec Microsoft Graph API
  - Récupère messages, attachments, déplace/marque messages

### Controllers
- **OutlookOAuthController** (`app/Http/Controllers/OutlookOAuthController.php`)
  - `GET /api/oauth/authorize`: Redirect user to Microsoft login
  - `GET /api/oauth/callback`: Handle OAuth callback
  - `GET /api/oauth/status`: Check token status (admin only)
  - `DELETE /api/oauth/token`: Revoke token (admin only)

### Commands
- **ImportOutlookTransactions** (`app/Console/Commands/ImportOutlookTransactions.php`)
  - `php artisan transactions:import-outlook`
  - Exécutée quotidiennement par le scheduler

### Models
- **OAuthToken** (`app/Models/OAuthToken.php`)
  - Stocke les tokens d'authentification
  - Gère la validité des tokens
  - Métodes: `isAccessTokenValid()`, `isRefreshTokenValid()`, `updateToken()`, etc.

- **OutlookImportHistory** (`app/Models/OutlookImportHistory.php`)
  - Audit complet de chaque import
  - Scopes: `byMessageId()`, `recent()`, `retryable()`
  - Métodes: `markAsSuccess()`, `markAsFailed()`, `markAsPartial()`

## Sécurité

### Token Management
- Les Access Tokens sont stockés en base (valides 1h, auto-rafraîchis)
- Les Refresh Tokens sont stockés en base (n'expire pas par défaut pour Outlook)
- Logs de chaque appel API pour audit
- Tokens révoqués automatiquement si config `OUTLOOK_IMPORT_ENABLED=false`

### Idempotence
- Chaque message a un unique `message_id` (Microsoft)
- Avant d'importer, on vérifie si déjà traité en base
- Si déjà traité avec succès, on skip (pas de doublon)
- Hash SHA256 du fichier pour détection supplémentaire

### Permissions
- Routes OAuth publiques pour le callback
- Routes status/revoke réservées aux admins (middleware CheckRole:admin)
- Commande cron exécutée par système (pas d'accès utilisateur)

## Troubleshooting

### "OAuth token not found"
→ L'utilisateur n'a pas encore cliqué sur `/api/oauth/authorize`

### "Token expired"
→ Le Refresh Token est expiré. Relancer l'autorisation OAuth.

### "Folder not found"
→ Le dossier "FLOOZ TOGO" n'existe pas ou mal nommé. Vérifier dans Outlook.

### "Filename doesn't match pattern"
→ Le fichier n'a pas le bon format. Pattern attendu: `All Agent Consolidated Report_*.xlsx`

### Messages restent dans Failed
→ Vérifier les logs: `storage/logs/outlook-import.log`
→ Vérifier l'erreur en base: `SELECT error_message FROM outlook_import_history WHERE status='failed' ORDER BY created_at DESC;`

## Désactiver l'intégration

Pour désactiver temporairement l'import Outlook sans supprimer la config:

```bash
# .env
OUTLOOK_IMPORT_ENABLED=false
```

Cela désactive la commande cron sans affecter les données existantes.

## Logs

- **Cron output**: `storage/logs/outlook-import.log` (rotaté quotidiennement)
- **Errors**: `storage/logs/laravel.log` (stack traces complets)
- **Audit**: Table `outlook_import_history` (queryable)

## Prochaines étapes (optionnel)

1. **Webhook au lieu de Cron**: Configurer une règle Exchange pour Push au lieu de Pull
2. **Retry automatique**: Relancer les imports échoués (ajouté dans `OutlookImportHistory::retryable()`)
3. **Dashboard**: Écran d'historique import pour les admins
4. **Notification**: Email après import (succès/erreur)
5. **Multiboîte**: Supporter plusieurs boîtes mail sources

---

**Date**: Mai 2026  
**Version**: 1.0
