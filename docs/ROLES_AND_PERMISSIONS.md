# 🔐 Système de Rôles et Permissions

## Vue d'ensemble

Le système de gestion des PDV Moov Money utilise deux types d'utilisateurs principaux avec des permissions distinctes.

---

## 👥 Types d'utilisateurs

### 1. 🏢 Administrateurs Moov Money (`admin`)

**Description** : Employés de Moov Money qui supervisent l'ensemble du système.

**Caractéristiques** :
- Appartiennent à Moov Money (pas à un dealer spécifique)
- Ont une vue complète sur tous les dealers et tous les PDV
- Peuvent gérer l'ensemble de la plateforme

**Permissions** :
- ✅ **Visualisation** : Tous les dealers, tous les PDV, toutes les statistiques
- ✅ **Gestion des Dealers** : Créer, modifier, désactiver des organisations dealers
- ✅ **Gestion des Utilisateurs** : Créer, modifier, désactiver tous les utilisateurs (admin et dealers)
- ✅ **Validation des PDV** : Valider ou rejeter tous les PDV en attente
- ✅ **Statistiques globales** : Accès aux statistiques agrégées de tous les dealers
- ✅ **Exports** : Exporter les données de tous les dealers
- ✅ **Administration système** : Accès aux logs d'activité, paramètres système

**Restrictions** :
- ❌ Aucune restriction d'accès aux données

---

### 2. 🏪 Utilisateurs Dealer (`dealer`)

**Description** : Utilisateurs qui appartiennent à une organisation dealer spécifique.

**Caractéristiques** :
- Liés à un dealer unique via `organization_id`
- Peuvent être n'importe qui : responsable, commercial, employé du dealer
- Vue limitée aux données de leur propre organisation

**Permissions** :
- ✅ **Visualisation** : Uniquement les PDV de leur organisation
- ✅ **Gestion des PDV** : Créer, modifier les PDV de leur organisation
- ✅ **Statistiques** : Voir les statistiques de leur organisation uniquement
- ✅ **Exports** : Exporter les données de leur organisation uniquement

**Restrictions** :
- ❌ **Pas d'accès** aux PDV d'autres dealers
- ❌ **Pas d'accès** aux statistiques globales
- ❌ **Pas d'accès** à la gestion des dealers
- ❌ **Pas d'accès** à la gestion des utilisateurs (sauf peut-être leur profil)
- ❌ **Pas d'accès** à l'administration système
- ❌ **Pas de validation** des PDV (réservé aux admins)

---

## 🔒 Filtrage des données (Scoping)

### Backend - Automatic Scoping

Le système applique automatiquement le filtrage des données selon le rôle de l'utilisateur :

```php
// Dans les contrôleurs
$user = $request->user();
$query = PointOfSale::query();

if (!$user->isAdmin()) {
    // Les dealers ne voient que leurs PDV
    $query->where('organization_id', $user->organization_id);
}
```

### Middleware `ScopeOrganization`

Un middleware dédié est appliqué sur les routes sensibles pour s'assurer que :
- Les admins peuvent accéder à toutes les organisations
- Les dealers n'accèdent qu'aux données de leur organisation
- Les dealers sans organisation sont bloqués

### Protection des routes

```php
// Routes réservées aux admins uniquement
Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
    Route::resource('organizations', OrganizationController::class);
    Route::get('/statistics/by-organization', [StatisticsController::class, 'byOrganization']);
    Route::resource('users', UserController::class);
});

// Routes accessibles aux admins et dealers (avec filtrage automatique)
Route::get('/point-of-sales', [PointOfSaleController::class, 'index']);
Route::get('/statistics/dashboard', [StatisticsController::class, 'dashboard']);
```

---

## 📊 Exemples de comportement

### Scénario 1 : Admin consulte les statistiques

**Requête** : `GET /api/statistics/dashboard`

**Résultat** :
```json
{
  "stats": {
    "total": 500,          // Tous les PDV de tous les dealers
    "pending": 120,
    "validated": 350,
    "rejected": 30
  },
  "by_organization": [    // Top 10 dealers
    { "name": "Dealer A", "pdv_count": 150 },
    { "name": "Dealer B", "pdv_count": 120 }
  ]
}
```

---

### Scénario 2 : Dealer consulte les statistiques

**Utilisateur** : `user.organization_id = 5` (Dealer "Centre Ville")

**Requête** : `GET /api/statistics/dashboard`

**Résultat** :
```json
{
  "stats": {
    "total": 45,           // Uniquement les PDV du Dealer "Centre Ville"
    "pending": 12,
    "validated": 30,
    "rejected": 3
  },
  "by_organization": null  // Pas d'accès aux stats des autres dealers
}
```

---

### Scénario 3 : Dealer tente d'accéder à un PDV d'un autre dealer

**Utilisateur** : `user.organization_id = 5`

**Requête** : `GET /api/point-of-sales/123`

**PDV 123** : `organization_id = 8` (autre dealer)

**Résultat** : `404 Not Found` (le PDV est filtré automatiquement, donc invisible)

---

## 🎨 Interface utilisateur

### Menu de navigation pour Admin

- 📊 Dashboard (toutes les stats)
- 📍 Liste PDV (tous les dealers)
- 🗺️ Carte (tous les PDV)
- **Administration** (menu déroulant) :
  - 👥 Utilisateurs
  - 🏢 Dealers
  - ✅ File de validation
  - 📈 Statistiques
  - 📋 Logs d'activité

---

### Menu de navigation pour Dealer

- 📊 Dashboard (stats de leur organisation)
- 📍 Liste PDV (leurs PDV uniquement)
- 🗺️ Carte (leurs PDV uniquement)
- ➕ Nouveau PDV

**Pas d'accès à** :
- ❌ Gestion des utilisateurs
- ❌ Gestion des dealers
- ❌ File de validation
- ❌ Logs d'activité

---

## 🛡️ Sécurité

### Au niveau du backend

1. **Authentification** : Tokens Sanctum pour toutes les requêtes
2. **Autorisation** : Middleware `CheckRole` pour les routes admin
3. **Filtrage** : Middleware `ScopeOrganization` pour limiter l'accès aux données
4. **Validation** : Vérification que l'utilisateur dealer a bien un `organization_id`

### Au niveau du frontend

1. **Navigation conditionnelle** : Menu adapté selon le rôle
2. **Affichage conditionnel** : Boutons "Valider", "Administration" masqués pour les dealers
3. **Routes protégées** : Redirection si accès non autorisé
4. **Feedback utilisateur** : Messages clairs en cas d'accès refusé

---

## 🔧 Méthodes utilitaires (User Model)

```php
// Vérifier le rôle
$user->isAdmin();  // true si admin
$user->isDealer(); // true si dealer

// Vérifier l'accès à une organisation
$user->canAccessOrganization($organizationId);
// Retourne true si admin OU si dealer avec même organization_id

// Obtenir les IDs d'organisations accessibles
$user->getAccessibleOrganizationIds();
// Retourne null pour admin (= tous)
// Retourne [$organization_id] pour dealer
```

---

## 📝 Notes importantes

1. **Un dealer = Une organisation** : Chaque utilisateur dealer est lié à une seule organisation
2. **Admins sans organisation** : Les admins n'ont pas d'`organization_id` (NULL)
3. **Validation centralisée** : Seuls les admins peuvent valider/rejeter des PDV
4. **Auto-filtrage** : Le filtrage par organisation est automatique, pas besoin de le gérer manuellement dans chaque vue

---

## 🚀 Migration des rôles existants

Si vous aviez un rôle `commercial` :

1. **Option 1** : Convertir en `dealer` (utilisateur d'une organisation)
2. **Option 2** : Convertir en `admin` (si c'était un employé Moov)

**Script de migration** :

```sql
-- Convertir tous les commercials en dealers
UPDATE users 
SET role_id = (SELECT id FROM roles WHERE name = 'dealer')
WHERE role_id = (SELECT id FROM roles WHERE name = 'commercial');

-- Supprimer le rôle commercial
DELETE FROM roles WHERE name = 'commercial';
```

---

## ✅ Checklist d'implémentation

- [x] RoleSeeder mis à jour (2 rôles : admin, dealer)
- [x] Middleware `CheckRole` fonctionnel
- [x] Middleware `ScopeOrganization` créé
- [x] User Model : méthodes `canAccessOrganization()` et `getAccessibleOrganizationIds()`
- [x] PointOfSaleController : filtrage automatique
- [x] StatisticsController : filtrage automatique
- [ ] Frontend : menu conditionnel selon le rôle
- [ ] Frontend : masquer les actions admin pour les dealers
- [ ] Tests : vérifier les permissions
- [ ] Documentation utilisateur

---

## 📞 Support

Pour toute question sur les rôles et permissions :
- Consulter ce document
- Vérifier les middleware dans `app/Http/Middleware/`
- Consulter le User Model pour les méthodes utilitaires
