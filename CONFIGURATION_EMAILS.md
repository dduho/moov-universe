# Configuration des Emails - Moov Universe

## Vue d'ensemble

Le système d'emails de Moov Universe utilise Laravel Mail avec des templates HTML brandés aux couleurs Moov (#FF6B00). Les emails sont envoyés pour toutes les actions liées aux tâches.

## 📧 Types d'emails configurés

1. **TaskAssignedMail** - Quand une tâche est assignée à un commercial
2. **TaskCompletedMail** - Quand une tâche est complétée (envoyé aux admins)
3. **TaskValidatedMail** - Quand une tâche est validée par un admin
4. **TaskRevisionRequestedMail** - Quand une révision est demandée

## 🎨 Templates

Tous les templates utilisent la charte graphique Moov :
- Couleur principale : `#FF6B00` (Orange Moov)
- Dégradés : `#FF6B00` → `#E55A00`
- Design responsive avec inline CSS pour compatibilité email
- Footer avec copyright et informations de contact

## ⚙️ Configuration pour Tests Locaux (Mailpit)

### 1. Modifier le fichier `.env`

Ajouter/modifier ces lignes :

```env
# Configuration mail pour tests locaux
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="contact@universe.moov-africa.tg"
MAIL_FROM_NAME="Moov Universe"

# URL du frontend pour les liens dans les emails
FRONTEND_URL=http://localhost:5173
```

### 2. Installer et lancer Mailpit

**Windows (avec Chocolatey)**
```bash
choco install mailpit
```

**Windows (sans gestionnaire de packages)**
- Télécharger depuis https://github.com/axllent/mailpit/releases
- Extraire l'exécutable mailpit.exe
- Lancer mailpit.exe

**macOS (avec Homebrew)**
```bash
brew install mailpit
```

**Lancer Mailpit**
```bash
mailpit
```

### 3. Accéder à l'interface web

Ouvrez http://localhost:8025 pour voir tous les emails envoyés.

## 🌍 Configuration pour Production

### Option 1 : Brevo (ex-Sendinblue) - GRATUIT 300 emails/jour

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre_email_brevo
MAIL_PASSWORD=votre_cle_smtp_brevo
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contact@universe.moov-africa.tg"
MAIL_FROM_NAME="Moov Universe"
```

**Étapes :**
1. Créer un compte sur https://www.brevo.com
2. Aller dans Settings → SMTP & API
3. Créer une clé SMTP
4. Vérifier le domaine moov-africa.tg (ajouter les enregistrements DNS requis)

### Option 2 : Gmail SMTP (Tests uniquement)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=mot_de_passe_application
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contact@universe.moov-africa.tg"
MAIL_FROM_NAME="Moov Universe"
```

⚠️ **Note** : Créer un "mot de passe d'application" dans les paramètres de sécurité Google.

### Option 3 : Serveur SMTP propre (Production recommandée)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.moov-africa.tg
MAIL_PORT=587
MAIL_USERNAME=contact@universe.moov-africa.tg
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contact@universe.moov-africa.tg"
MAIL_FROM_NAME="Moov Universe"
```

## 🧪 Tester l'envoi d'emails

### Via Tinker

```bash
cd backend
php artisan tinker
```

```php
// Charger une tâche
$task = \App\Models\Task::first();

// Tester l'email d'assignation
Mail::to('test@example.com')->send(new \App\Mail\TaskAssignedMail($task));

// Tester l'email de validation
Mail::to('test@example.com')->send(new \App\Mail\TaskValidatedMail($task));

// Tester l'email de révision
Mail::to('test@example.com')->send(new \App\Mail\TaskRevisionRequestedMail($task));

// Tester l'email de complétion (besoin d'un utilisateur admin)
$admin = \App\Models\User::whereHas('role', fn($q) => $q->where('name', 'admin'))->first();
Mail::to('test@example.com')->send(new \App\Mail\TaskCompletedMail($task, $admin));
```

### Via l'application

1. Lancer Mailpit : http://localhost:8025
2. Dans l'application, assigner une tâche
3. Vérifier que l'email apparaît dans Mailpit

## 📋 Checklist de déploiement

- [ ] Configurer le serveur SMTP de production
- [ ] Vérifier le domaine moov-africa.tg auprès du fournisseur SMTP
- [ ] Ajouter les enregistrements DNS (SPF, DKIM, DMARC)
- [ ] Tester l'envoi d'emails en production
- [ ] Configurer les queues pour envoi asynchrone (optionnel mais recommandé)
- [ ] Mettre en place la surveillance des erreurs d'envoi
- [ ] Ajuster FRONTEND_URL pour l'URL de production

## 🚀 Optimisations (Optionnel)

### 1. Queues pour envoi asynchrone

```bash
php artisan queue:table
php artisan migrate
```

Modifier `.env` :
```env
QUEUE_CONNECTION=database
```

Modifier les Mailable pour implémenter `ShouldQueue` :
```php
use Illuminate\Contracts\Queue\ShouldQueue;

class TaskAssignedMail extends Mailable implements ShouldQueue
{
    // ...
}
```

Lancer le worker :
```bash
php artisan queue:work
```

### 2. Rate Limiting

Ajouter dans `app/Providers/AppServiceProvider.php` :
```php
use Illuminate\Support\Facades\RateLimiter;

public function boot()
{
    RateLimiter::for('emails', function ($job) {
        return Limit::perMinute(50);
    });
}
```

## 🔍 Logs et Debug

- Les logs d'envoi sont dans `storage/logs/laravel.log`
- Pour logger les emails sans les envoyer : `MAIL_MAILER=log`
- Pour voir le HTML généré : utiliser Mailpit ou `MAIL_MAILER=array` + tests

## 📞 Support

Pour toute question sur la configuration des emails :
- Vérifier les logs : `tail -f storage/logs/laravel.log`
- Tester la connexion SMTP
- Vérifier la configuration DNS du domaine

---

**Version** : 1.0  
**Dernière mise à jour** : Décembre 2024  
**Contact** : contact@universe.moov-africa.tg
