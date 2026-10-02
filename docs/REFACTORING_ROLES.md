# Refactoring du Système de Rôles - Documentation

## Vue d'ensemble

Le système de rôles a été refactorisé pour introduire une hiérarchie au sein des dealers avec deux profils distincts : **Propriétaire Dealer** et **Commercial Dealer**.

## Nouveaux Rôles

### 1. Admin (inchangé)
- **Nom technique** : `admin`
- **Nom d'affichage** : Administrateur Moov Money
- **Permissions** :
  - Accès complet à tous les Dealers et PDV
  - Peut valider/rejeter les PDV
  - Gestion des utilisateurs et organisations
  - Accès aux paramètres système

### 2. Dealer Owner (nouveau - remplace "dealer")
- **Nom technique** : `dealer_owner`
- **Nom d'affichage** : Propriétaire Dealer
- **Permissions** :
  - Visualisation de TOUS les PDV de son organisation
  - Modification de TOUS les PDV de son organisation
  - Création de nouveaux PDV
  - Visualisation de tous les PDV de son organisation sur la carte
  - Gestion des commerciaux de son organisation (si implémenté)

### 3. Dealer Agent (nouveau)
- **Nom technique** : `dealer_agent`
- **Nom d'affichage** : Commercial Dealer
- **Permissions** :
  - Visualisation UNIQUEMENT des PDV qu'il a créés
  - Modification UNIQUEMENT des PDV qu'il a créés
  - Création de nouveaux PDV
  - Sur la carte : voir UNIQUEMENT ses propres PDV

## Modifications Techniques

### Backend

#### 1. Migration (`2025_12_03_190616_update_roles_for_dealer_hierarchy.php`)
- Création des rôles `dealer_owner` et `dealer_agent`
- Conversion automatique de tous les utilisateurs `dealer` en `dealer_owner`
- Suppression de l'ancien rôle `dealer`

#### 2. Modèle User (`app/Models/User.php`)
Nouvelles méthodes ajoutées :
```php
isDealerOwner()  // Vérifie si l'utilisateur est propriétaire dealer
isDealerAgent()  // Vérifie si l'utilisateur est commercial dealer
isDealer()       // Retourne true pour owner ET agent
canAccessPointOfSale($pointOfSale)  // Vérifie l'accès à un PDV spécifique
```

#### 3. PointOfSaleController
Logique de filtrage mise à jour :
- **Admin** : Voit tous les PDV
- **Dealer Owner** : Voit tous les PDV de son organisation
- **Dealer Agent** : Voit uniquement les PDV qu'il a créés (`created_by = user_id`)

Méthodes mises à jour :
- `index()` : Filtrage basé sur le rôle
- `show()` : Vérification avec `canAccessPointOfSale()`
- `update()` : Vérification avec `canAccessPointOfSale()`

#### 4. RoleSeeder
Mis à jour pour créer les 3 rôles : `admin`, `dealer_owner`, `dealer_agent`

### Frontend

#### 1. Auth Store (`frontend/src/stores/auth.js`)
Nouveaux getters ajoutés :
```javascript
isDealerOwner    // Vérifie si dealer_owner
isDealerAgent    // Vérifie si dealer_agent
isDealer         // True pour owner OU agent
userRole         // Retourne le nom du rôle
```

#### 2. Compatibilité
- Toutes les vérifications existantes `isDealer` continuent de fonctionner
- Les composants utilisant `isAdmin` ne sont pas affectés
- Aucune modification nécessaire dans les composants Vue existants

## Migration des Données

### Utilisateurs Existants
Tous les utilisateurs avec le rôle `dealer` ont été **automatiquement convertis** en `dealer_owner` lors de la migration.

### Vérification
Pour vérifier les rôles actuels :
```sql
SELECT u.name, u.email, r.name as role, r.display_name, o.name as organization
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN organizations o ON u.organization_id = o.id;
```

## Comptes de Test

### Admin
- Email : admin@moov.tg
- Mot de passe : password
- Accès : Tous les PDV

### Dealer Owner
- Email : dealer@somac.com (ou autres comptes dealers existants)
- Mot de passe : password
- Accès : Tous les PDV de SOMAC

### Dealer Agent (nouveau)
- Email : agent@test.com
- Mot de passe : password
- Accès : Uniquement les PDV créés par ce compte

## Tests Recommandés

### Test 1 : Dealer Owner
1. Se connecter avec `dealer@somac.com`
2. Vérifier que TOUS les PDV de SOMAC sont visibles
3. Vérifier l'accès à la carte avec tous les PDV de l'organisation
4. Tester la modification d'un PDV créé par un autre utilisateur (doit fonctionner)

### Test 2 : Dealer Agent
1. Se connecter avec `agent@test.com`
2. Créer 2-3 nouveaux PDV
3. Se déconnecter et reconnecter
4. Vérifier que SEULS les PDV créés par ce compte sont visibles
5. Sur la carte, vérifier que seuls ses PDV apparaissent
6. Tenter d'accéder directement à l'URL d'un PDV créé par un autre (doit retourner 403)

### Test 3 : Admin
1. Se connecter avec `admin@moov.tg`
2. Vérifier l'accès à TOUS les PDV de toutes les organisations
3. Tester la validation/rejet de PDV

## Points d'Attention

### Sécurité
- Les dealer_agent ne peuvent PAS voir les PDV des autres agents de leur organisation
- Les dealer_agent ne peuvent PAS modifier les PDV qu'ils n'ont pas créés
- L'accès direct via URL est protégé par `canAccessPointOfSale()`

### Performance
- Le filtrage est fait au niveau de la requête SQL (WHERE clause)
- Pas d'impact sur les performances

### Compatibilité
- **Rétrocompatible** : Les méthodes existantes continuent de fonctionner
- **Migration automatique** : Tous les utilisateurs dealer sont devenus dealer_owner
- **Pas de breaking change** : Le frontend existant fonctionne sans modification

## Fichiers Modifiés

### Backend
- `database/migrations/2025_12_03_190616_update_roles_for_dealer_hierarchy.php`
- `database/seeders/RoleSeeder.php`
- `app/Models/User.php`
- `app/Http/Controllers/PointOfSaleController.php`

### Frontend
- `frontend/src/stores/auth.js`

### Scripts Utilitaires
- `backend/create_dealer_agent.php` - Création de comptes dealer_agent

## Évolutions Futures Possibles

1. **Interface de gestion des commerciaux**
   - Les dealer_owner pourraient gérer leurs commerciaux
   - Affectation de territoires aux commerciaux

2. **Statistiques par commercial**
   - Tableau de bord pour chaque commercial
   - Comparaison des performances

3. **Hiérarchie plus complexe**
   - Superviseurs régionaux
   - Chefs d'équipe

4. **Notifications ciblées**
   - Alertes spécifiques pour les commerciaux
   - Notifications de validation pour le propriétaire

