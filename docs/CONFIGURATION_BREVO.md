# Configuration Brevo (ex-Sendinblue) pour l'envoi d'emails

## 🎯 Pourquoi Brevo ?

- ✅ **300 emails/jour gratuits**
- ✅ Excellente délivrabilité
- ✅ Interface en français
- ✅ Statistiques détaillées (taux d'ouverture, clics, etc.)
- ✅ Conforme RGPD
- ✅ Support client réactif
- ✅ API REST complète

## 📝 Étapes de configuration

### 1. Créer un compte Brevo

1. Aller sur [https://www.brevo.com](https://www.brevo.com)
2. Cliquer sur "Inscription gratuite"
3. Remplir le formulaire avec les informations de votre entreprise
4. Confirmer l'email de vérification

### 2. Obtenir la clé API SMTP

1. Une fois connecté, aller dans **"Paramètres"** (Settings) en haut à droite
2. Cliquer sur **"SMTP & API"** dans le menu de gauche
3. Descendre jusqu'à la section **"SMTP"**
4. Noter les informations suivantes :
   - **Serveur SMTP** : `smtp-relay.brevo.com`
   - **Port** : `587` (TLS) ou `465` (SSL)
   - **Login** : Votre email de connexion
   - **Mot de passe SMTP** : Cliquer sur "Générer une nouvelle clé" si nécessaire

> 💡 **Important** : Le mot de passe SMTP est différent de votre mot de passe de connexion. Il faut le générer depuis l'interface.

### 3. (Optionnel mais recommandé) Vérifier votre domaine

Pour éviter que vos emails soient marqués comme spam :

1. Aller dans **"Expéditeurs et Domaines"** > **"Domaines"**
2. Ajouter votre domaine (ex: `moov.tg`)
3. Suivre les instructions pour ajouter les enregistrements DNS :
   - **SPF** : Enregistrement TXT pour autoriser Brevo à envoyer des emails
   - **DKIM** : Signature cryptographique pour l'authenticité
   - **DMARC** : Politique de gestion des emails frauduleux

**Exemple d'enregistrements DNS :**
```
Type: TXT
Nom: @
Valeur: v=spf1 include:spf.brevo.com ~all

Type: TXT
Nom: mail._domainkey
Valeur: [Fourni par Brevo]

Type: TXT
Nom: _dmarc
Valeur: v=DMARC1; p=none; rua=mailto:postmaster@votre-domaine.tg
```

### 4. Configurer Laravel

#### A. Mettre à jour le fichier `.env`

```env
# Configuration Brevo SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@moov.tg
MAIL_PASSWORD=votre_cle_smtp_brevo
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@moov.tg
MAIL_FROM_NAME="Moov Universe"
MAIL_NOTIFICATIONS_ENABLED=true
```

> ⚠️ **Attention** : 
> - `MAIL_USERNAME` = Votre email de connexion Brevo
> - `MAIL_PASSWORD` = La clé SMTP générée (pas votre mot de passe de connexion)
> - `MAIL_FROM_ADDRESS` = L'email expéditeur (doit être vérifié dans Brevo)

#### B. Vider le cache de configuration

```bash
cd backend
php artisan config:clear
php artisan config:cache
```

### 5. Tester l'envoi d'emails

#### Méthode 1 : Via Tinker

```bash
php artisan tinker
```

```php
Mail::raw('Test email depuis Moov Universe', function ($message) {
    $message->to('votre-email@example.com')
            ->subject('Test Brevo SMTP');
});
```

#### Méthode 2 : Créer une commande de test

Créer `backend/app/Console/Commands/TestEmail.php` :

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'email:test {email}';
    protected $description = 'Envoyer un email de test';

    public function handle()
    {
        $email = $this->argument('email');
        
        Mail::raw('Ceci est un email de test depuis Moov Universe via Brevo.', function ($message) use ($email) {
            $message->to($email)
                    ->subject('Test Brevo - Moov Universe');
        });

        $this->info("Email de test envoyé à {$email}");
    }
}
```

Puis exécuter :

```bash
php artisan email:test votre-email@example.com
```

### 6. Vérifier dans le tableau de bord Brevo

1. Aller dans **"Statistiques"** > **"Campagnes Email"**
2. Vous devriez voir votre email de test
3. Vérifier le statut :
   - ✅ **Envoyé** : L'email a été transmis
   - ✅ **Délivré** : L'email est arrivé dans la boîte de réception
   - ⚠️ **Bounced** : L'email a été rejeté (vérifier l'adresse)
   - ⚠️ **Spam** : Marqué comme spam (vérifier SPF/DKIM)

## 🚨 Résolution des problèmes courants

### Erreur "Authentication failed"

**Cause** : Login ou mot de passe SMTP incorrect

**Solution** :
1. Vérifier que `MAIL_USERNAME` est bien votre email de connexion Brevo
2. Générer une nouvelle clé SMTP depuis l'interface Brevo
3. Vider le cache : `php artisan config:clear`

### Emails marqués comme spam

**Cause** : Domaine non vérifié ou SPF/DKIM manquants

**Solution** :
1. Vérifier votre domaine dans Brevo
2. Ajouter les enregistrements SPF et DKIM dans votre DNS
3. Attendre 24-48h pour la propagation DNS
4. Utiliser un email `@votre-domaine.tg` (pas @gmail.com)

### Limite quotidienne atteinte

**Cause** : Plus de 300 emails envoyés en 24h (plan gratuit)

**Solution** :
1. Attendre 24h pour le reset du compteur
2. Ou passer à un plan payant (à partir de 25€/mois pour 20 000 emails/mois)

### Emails non reçus

**Cause** : Multiple possibilités

**Solution** :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier les logs Brevo : Dashboard > Statistiques
3. Vérifier le dossier spam du destinataire
4. Tester avec plusieurs adresses email différentes

### Port 587 bloqué

**Cause** : Pare-feu ou hébergeur bloquant le port

**Solution** :
1. Essayer le port 465 avec SSL :
   ```env
   MAIL_PORT=465
   MAIL_ENCRYPTION=ssl
   ```
2. Ou contacter l'hébergeur pour débloquer le port 587

## 📊 Monitoring et statistiques

Brevo fournit des statistiques détaillées :

- **Taux de délivrabilité** : % d'emails arrivés à destination
- **Taux d'ouverture** : % d'emails ouverts (si tracking activé)
- **Taux de clic** : % de clics sur les liens
- **Bounces** : Emails rejetés (hard/soft bounce)
- **Désabonnements** : Nombre de désinscriptions
- **Spam reports** : Signalements comme spam

### Activer le tracking (optionnel)

Dans votre code Laravel, vous pouvez activer le tracking :

```php
// Dans la notification ou le mailable
public function build()
{
    return $this->view('emails.notification')
                ->withSwiftMessage(function ($message) {
                    $message->getHeaders()->addTextHeader(
                        'X-Mailin-Tag', 
                        'notification-pdv'
                    );
                });
}
```

## 🔐 Bonnes pratiques de sécurité

1. **Ne jamais commiter les clés** : 
   - Garder `.env` hors du git
   - Utiliser `.env.example` comme template

2. **Utiliser des variables d'environnement** :
   ```bash
   # Sur le serveur de production
   export MAIL_PASSWORD="votre_cle_smtp"
   ```

3. **Renouveler les clés régulièrement** :
   - Générer une nouvelle clé SMTP tous les 6 mois
   - Révoquer les anciennes clés

4. **Limiter les permissions** :
   - Si vous avez plusieurs développeurs, créer des sous-comptes Brevo
   - Chacun avec sa propre clé SMTP

## 📈 Passer au plan payant

Quand votre plateforme grandit, vous pouvez passer à un plan payant :

| Plan | Prix | Emails/mois | Support |
|------|------|-------------|---------|
| **Gratuit** | 0€ | 300/jour | Email |
| **Lite** | 25€ | 20 000 | Email |
| **Business** | 65€ | 100 000 | Email + Chat |
| **Enterprise** | Sur devis | Illimité | Dédié |

> 💡 **Astuce** : Le plan Lite à 25€/mois est largement suffisant pour démarrer. Il offre aussi des fonctionnalités avancées comme le marketing automation.

## 🔄 Migration depuis un autre service

Si vous utilisez actuellement un autre service (Gmail, SendGrid, etc.) :

1. **Exporter vos templates** depuis l'ancien service
2. **Créer les templates** dans Brevo
3. **Changer la config** dans le `.env`
4. **Tester** avec quelques emails
5. **Désactiver** l'ancien service

## 📞 Support

- **Documentation** : https://developers.brevo.com/
- **Support email** : contact@brevo.com
- **Chat en ligne** : Disponible sur le dashboard
- **Communauté** : Forum communautaire actif

## ✅ Checklist de mise en production

- [ ] Compte Brevo créé et vérifié
- [ ] Clé SMTP générée
- [ ] Domaine vérifié avec SPF/DKIM
- [ ] Configuration `.env` en production
- [ ] Cache Laravel vidé
- [ ] Email de test envoyé et reçu
- [ ] Monitoring configuré
- [ ] Limites quotidiennes comprises
- [ ] Plan d'escalade prévu si nécessaire

## 🎓 Ressources supplémentaires

- [Guide officiel Brevo Laravel](https://developers.brevo.com/docs/laravel-package)
- [Documentation Laravel Mail](https://laravel.com/docs/10.x/mail)
- [Tester la délivrabilité](https://www.mail-tester.com/)
- [Vérifier SPF/DKIM](https://mxtoolbox.com/)

---

**Dernière mise à jour** : Janvier 2026
**Version Laravel** : 10.x
**Version Brevo API** : v3
