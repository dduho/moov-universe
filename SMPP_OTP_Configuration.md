# Documentation SMPP - SMSC Moov Money Togo

Guide d'intégration pour l'envoi de SMS (OTP, notifications) via le SMSC Moov, quel que soit le langage ou la plateforme utilisée.

---

## 1. Connexion au SMSC

### Paramètres de connexion

| Paramètre       | Valeur             | Description                              |
|------------------|--------------------|------------------------------------------|
| Hôte             | `10.82.11.30`      | Adresse IP du SMSC                       |
| Port             | `2775`             | Port SMPP                                |
| `system_id`      | `username_envoyé`  | Identifiant de l'application             |
| `password`       | `passwor`          | Mot de passe                             |
| Type de bind     | `bind_transceiver` | Permet l'envoi et la réception           |
| Version SMPP     | `3.4`              | Version du protocole                     |

### Contrainte importante

> ⚠️ **Le SMSC n'autorise qu'un seul bind simultané par `system_id`.**
> Toute tentative de second bind retourne `command_status = 0x00000008` (System Error).
>
> **Conséquence** : si plusieurs services doivent envoyer des SMS, un seul processus doit maintenir la connexion SMPP et les autres doivent lui déléguer l'envoi (via HTTP, message queue, etc.).

### Reconnexion

Le SMSC peut couper la connexion en cas d'inactivité prolongée. Implémentez :
- Des **enquire_link** réguliers (toutes les 30 secondes recommandé) pour maintenir la session active
- Une **reconnexion automatique** avec back-off exponentiel en cas de déconnexion

---

## 2. Envoi de SMS (`submit_sm`)

### TON / NPI (Type of Number / Numbering Plan Indicator)

Combinaison **validée par tests exhaustifs** - toute autre combinaison est rejetée par ce SMSC.

| Champ PDU             | Valeur | Signification         | Exemple                   |
|------------------------|--------|-----------------------|---------------------------|
| `source_addr_ton`      | **5**  | Alphanumeric          | Nom de l'expéditeur : `MoovApps`, `999901` |
| `source_addr_npi`      | **0**  | Unknown               | -                         |
| `dest_addr_ton`        | **1**  | International         | Numéro au format E.164    |
| `dest_addr_npi`        | **1**  | ISDN (E.164)          | -                         |

### Format des adresses

| Champ PDU        | Format          | Exemple          | Remarques                                       |
|------------------|-----------------|------------------|-------------------------------------------------|
| `source_addr`    | Alphanumeric    | `MoovApps`       | Max 11 caractères, pas de numéro de téléphone. Sender principal ; si l'envoi échoue, `SmppService` retente automatiquement avec le short code `999901`. |
| `destination_addr` | International | `22899990010`    | Indicatif pays (228) + numéro national, sans `+` |

### Autres champs `submit_sm`

| Champ PDU             | Valeur recommandée | Description                           |
|------------------------|--------------------|---------------------------------------|
| `data_coding`          | `0`                | Encodage par défaut (GSM 7-bit)       |
| `registered_delivery`  | `0`                | Pas d'accusé de réception (ou `1` si souhaité) |

### Codes d'erreur fréquents

| `command_status` | Code                  | Cause                                  | Solution                              |
|-------------------|-----------------------|----------------------------------------|---------------------------------------|
| `0x00000008`      | `ESME_RSYSERR`        | System Error - souvent un 2e bind      | Un seul bind par `system_id`          |
| `0x0000000A`      | `ESME_RINVSRCADR`     | Adresse source invalide                | Vérifier `source_addr_ton=5`, `source_addr_npi=0` |
| `0x0000000B`      | `ESME_RINVDSTADR`     | Adresse destination invalide           | Vérifier `dest_addr_ton=1`, `dest_addr_npi=1` + format E.164 sans `+` |
| `0x00000045`      | `ESME_RSUBMITFAIL`    | Échec d'envoi générique                | Vérifier le format du message et la connectivité |

### Exemple de PDU `submit_sm`

```
submit_sm {
  service_type:        ""
  source_addr_ton:     5
  source_addr_npi:     0
  source_addr:         "MoovApps"
  dest_addr_ton:       1
  dest_addr_npi:       1
  destination_addr:    "22899990010"
  data_coding:         0
  short_message:       "Votre code: 482917. Valide 5 min."
  registered_delivery: 0
}
```

---

## 3. Bonnes pratiques

### Sécurité des credentials

- Ne jamais committer les identifiants SMPP dans le code source
- Utiliser des variables d'environnement ou un gestionnaire de secrets
- Restreindre l'accès réseau au SMSC (firewall, VPN)

### Gestion de la connexion

- **Un seul processus** doit maintenir le bind SMPP
- Envoyer un `enquire_link` toutes les ~30s pour détecter les déconnexions
- Implémenter un back-off exponentiel pour la reconnexion (ex : 1s, 2s, 4s, 8s… max 60s)
- Logger chaque `submit_sm_resp` avec le `message_id` retourné pour le suivi

### Format des numéros

- Toujours envoyer au format international **sans le `+`** : `22899990010`
- Préfixer systématiquement avec l'indicatif pays (`228` pour le Togo)

### Contenu des SMS

- Le SMS est limité à **160 caractères** en GSM 7-bit (70 en UCS-2 pour les caractères spéciaux)
- Éviter les caractères hors alphabet GSM 7-bit (accents, emoji) sauf si `data_coding` est adapté
- Pour les OTP : inclure le nom de l'application, le code, la durée de validité

---

## 4. Dépannage rapide

| Symptôme | Cause probable | Action |
|----------|----------------|--------|
| Bind rejeté (`status=8`) | Un autre processus est déjà connecté | Arrêter l'autre instance ou centraliser le bind |
| `status=0x0B` à l'envoi | TON/NPI destination incorrects | `dest_addr_ton=1`, `dest_addr_npi=1` |
| `status=0x0A` à l'envoi | TON/NPI source incorrects | `source_addr_ton=5`, `source_addr_npi=0` |
| Connexion coupée sans erreur | Pas d'enquire_link | Envoyer un enquire_link toutes les 30s |
| Timeout à la connexion | Réseau inaccessible | Vérifier l'accès au `10.82.11.30:2775` (firewall, VPN, routage) |
| SMS envoyé mais non reçu | Numéro mal formaté | Vérifier le format E.164 sans `+` : `228XXXXXXXX` |

---

## 5. Références

- [Spécification SMPP v3.4](https://smpp.org/SMPP_v3_4_Issue1_2.pdf) - protocole complet
- Codes TON : 0=Unknown, 1=International, 2=National, 3=Network, 5=Alphanumeric
- Codes NPI : 0=Unknown, 1=ISDN (E.164), 3=Data, 6=Land Mobile, 9=Private
