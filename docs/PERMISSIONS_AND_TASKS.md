# Système de Permissions et de Tâches - Moov Universe

## 📋 Vue d'ensemble

Ce document décrit le système de permissions par rôle et le système de gestion des tâches implémenté dans Moov Universe.

## 👥 Rôles et Permissions

### 1. **Administrateur (Admin)**
✅ **Accès complet au système**

**Peut :**
- Voir tous les PDV de toutes les organisations
- Créer, modifier, supprimer des PDV
- Valider ou rejeter des PDV
- Créer et assigner des tâches
- Valider des tâches ou demander des révisions
- Accéder aux pages : Validation, Utilisateurs, Paramètres, Dealers, Logs
- Importer et exporter des PDV (toutes organisations)
- Voir toutes les statistiques et logs

**Ne peut pas :**
- Rien (accès total)

---

### 2. **Propriétaire Dealer (dealer_owner)**
✅ **Gestion de son organisation**

**Peut :**
- Voir tous les PDV de son organisation
- Modifier les PDV de son organisation
- Voir les logs de son organisation
- Voir les statistiques de son organisation
- Utiliser la carte interactive (PDV de son organisation)
- Importer et exporter des PDV (son organisation uniquement)
- Voir les tâches de son organisation
- Rechercher uniquement dans son organisation

**Ne peut pas :**
- Accéder aux pages : Validation, Utilisateurs, Paramètres
- Voir les PDV d'autres organisations
- Créer ou valider des tâches
- Valider ou rejeter des PDV

---

### 3. **Commercial (commercial)**
✅ **Accès limité à ses créations et tâches**

**Peut :**
- Voir uniquement les PDV qu'il a créés
- Voir les PDV sur lesquels une tâche lui a été assignée
- Modifier les PDV qu'il a créés
- Compléter les tâches qui lui sont assignées
- Resoumettre une tâche après révision
- Voir ses propres statistiques

**Ne peut pas :**
- Voir les PDV d'autres commerciaux
- Accéder aux pages : Validation, Utilisateurs, Paramètres, Dealers
- Créer, valider ou supprimer des tâches
- Valider ou rejeter des PDV
- Importer ou exporter des PDV

---

## 📝 Système de Tâches

### Architecture

**Tables créées :**
1. `tasks` - Stocke les tâches assignées
2. `point_of_sale_tags` - Gère les tags des PDV (en_revision, taches_a_valider)

**Modèles :**
- `Task` - Modèle de tâche avec relations
- `PointOfSaleTag` - Modèle de tag avec relation au PDV

### Flux de travail

#### 1. **Création d'une tâche (Admin uniquement)**

```
Admin sur fiche PDV
  → Clique "Créer une tâche"
  → Sélectionne un commercial (du même dealer que le PDV)
  → Entre titre et description
  → Crée la tâche
  
Résultat :
  ✓ Tâche créée avec status "pending"
  ✓ Tag "en_revision" ajouté au PDV
  ✓ Notification envoyée au commercial
  ✓ Notification envoyée aux propriétaires
```

**Règles de validation :**
- Le commercial doit appartenir au même dealer que le PDV
- Le commercial doit avoir le rôle "commercial"
- Seuls les admin peuvent créer des tâches

---

#### 2. **Complétion d'une tâche (Commercial)**

```
Commercial voit la tâche
  → Effectue le travail demandé
  → Clique "Marquer comme complétée"
  → Confirme la complétion
  
Résultat :
  ✓ Tâche status → "completed"
  ✓ Tag "en_revision" retiré
  ✓ Tag "taches_a_valider" ajouté
  ✓ Date completed_at enregistrée
  ✓ Notification envoyée aux admin
```

**Règles :**
- Seul le commercial assigné peut compléter sa tâche
- Status doit être "pending", "in_progress" ou "revision_requested"

---

#### 3. **Validation d'une tâche (Admin)**

```
Admin voit la tâche complétée
  → Examine le travail
  → Clique "Valider"
  
Résultat :
  ✓ Tâche status → "validated"
  ✓ Date validated_at enregistrée
  ✓ Si toutes tâches validées → tous tags retirés du PDV
  ✓ Sinon → tag "taches_a_valider" retiré
  ✓ Notification envoyée au commercial
```

---

#### 4. **Demande de révision (Admin)**

```
Admin voit la tâche complétée
  → Examine le travail
  → Clique "Demander révision"
  → Entre un feedback
  → Envoie la demande
  
Résultat :
  ✓ Tâche status → "revision_requested"
  ✓ Feedback enregistré dans admin_feedback
  ✓ Tag "taches_a_valider" retiré
  ✓ Tag "en_revision" ajouté
  ✓ Notification envoyée au commercial avec feedback
```

Le commercial peut alors retravailler et resoumettre (retour à l'étape 2).

---

### Statuts des tâches

| Statut | Description | Qui peut agir |
|--------|-------------|---------------|
| `pending` | Tâche nouvellement créée | Commercial assigné |
| `in_progress` | En cours de traitement | Commercial assigné |
| `completed` | Tâche complétée, en attente de validation | Admin |
| `validated` | Tâche validée par admin | Aucune action |
| `revision_requested` | Révision demandée par admin | Commercial assigné |

---

### Tags des PDV

| Tag | Signification | Condition |
|-----|---------------|-----------|
| `en_revision` | Une tâche est en cours ou en révision | Au moins une tâche pending/in_progress/revision_requested |
| `taches_a_valider` | Une tâche est complétée | Au moins une tâche completed |

**Retrait automatique :**
- Tous les tags sont retirés quand toutes les tâches sont validées
- "en_revision" retiré quand tâche complétée
- "taches_a_valider" retiré quand tâche validée ou révision demandée

---

## 🎨 Interface Utilisateur

### Composants créés

1. **TaskModal.vue**
   - Modal de création de tâche
   - Sélection commercial (filtrée par dealer)
   - Formulaire titre + description

2. **TaskList.vue**
   - Liste des tâches sur un PDV
   - Actions selon le rôle (compléter, valider, réviser)
   - Affichage des feedbacks admin
   - Tags visuels du PDV

3. **RevisionModal.vue**
   - Modal pour demander une révision
   - Champ feedback requis

4. **TaskListView.vue**
   - Page "Mes tâches"
   - Filtres par statut
   - Statistiques (total, pending, in_progress, completed, validated)
   - Clic sur tâche → navigation vers PDV

---

### Navigation

**Ajout dans Navbar :**
- Lien "Mes tâches" dans la navigation principale
- Accessible à tous les rôles (commerciaux, owners, admin)
- Badge de notification possible (future implémentation)

**Ajout dans PDV Detail :**
- Section "Tâches" dans la colonne droite
- Bouton "Créer une tâche" (admin uniquement)
- Liste des tâches avec actions contextuelles

---

## 🔒 Contrôles d'accès

### Backend (API)

**Middleware :**
- `auth:sanctum` - Authentification requise
- `CheckRole:admin` - Admin uniquement

**Vérifications dans controllers :**
```php
// PointOfSaleController
if ($user->isAdmin()) {
    // Voir tous les PDV
} elseif ($user->isDealerOwner()) {
    // PDV de son organisation
} elseif ($user->isCommercial()) {
    // PDV créés + PDV avec tâches assignées
}

// TaskController
if ($user->isCommercial()) {
    // Uniquement ses tâches
} elseif ($user->isDealerOwner()) {
    // Tâches de son organisation
}
```

---

### Frontend (Vue)

**Guards de route :**
```javascript
meta: { 
  requiresAuth: true,
  requiresAdmin: true  // Pour /validation, /users, /settings, /pdv/import
}
```

**Vérifications dans composants :**
```vue
v-if="authStore.isAdmin"  <!-- Boutons admin uniquement -->
v-if="canComplete(task)"  <!-- Actions commerciales -->
```

---

## 📊 Requêtes API

### Endpoints Tâches

| Méthode | Route | Rôle | Description |
|---------|-------|------|-------------|
| GET | `/tasks` | Tous | Liste des tâches (filtrées par rôle) |
| GET | `/tasks/{id}` | Tous | Détails d'une tâche |
| POST | `/tasks` | Admin | Créer une tâche |
| POST | `/tasks/{id}/complete` | Commercial | Compléter sa tâche |
| POST | `/tasks/{id}/validate` | Admin | Valider une tâche |
| POST | `/tasks/{id}/request-revision` | Admin | Demander révision |
| DELETE | `/tasks/{id}` | Admin | Supprimer une tâche |
| GET | `/tasks/commercials/{pdvId}` | Admin | Liste commerciaux du dealer |

---

### Endpoints PDV (Modifications)

**Liste PDV** - `/point-of-sales`
```php
// Commercial voit maintenant :
$query->where(function($q) use ($user) {
    $q->where('created_by', $user->id)
      ->orWhereHas('tasks', function($taskQuery) use ($user) {
          $taskQuery->where('assigned_to', $user->id);
      });
});
```

**Import/Export** - Restrictions par organisation
- Admin : toutes organisations
- Owner : son organisation uniquement
- Commercial : aucun accès

---

## 🔔 Notifications (À implémenter)

### Événements déclencheurs

1. **Tâche créée**
   - → Commercial assigné
   - → Propriétaires du dealer

2. **Tâche complétée**
   - → Admin

3. **Tâche validée**
   - → Commercial assigné

4. **Révision demandée**
   - → Commercial assigné (avec feedback)

### Structure notification
```json
{
  "type": "task_created|task_completed|task_validated|revision_requested",
  "data": {
    "task_id": 123,
    "pdv_id": 456,
    "pdv_name": "Boutique Test",
    "message": "Une nouvelle tâche vous a été assignée",
    "feedback": "Vérifier la date d'expiration..." // Si révision
  }
}
```

---

## 📝 Exemples d'utilisation

### Cas 1 : Admin crée une tâche

```javascript
// Frontend - PointOfSaleDetail.vue
<TaskList :pdv="pos" @tasks-updated="loadPOS" />

// Clic "Créer une tâche"
// → TaskModal.vue s'ouvre
// → Charge commerciaux du dealer
// → Admin sélectionne commercial, entre titre/description
// → Submit → TaskService.createTask()

// Backend - TaskController@store
// → Vérifie commercial appartient au dealer
// → Crée tâche status="pending"
// → Ajoute tag "en_revision" au PDV
// → (TODO) Envoie notifications
```

---

### Cas 2 : Commercial complète une tâche

```javascript
// Frontend - TaskListView.vue ou TaskList.vue
// Commercial voit sa tâche status="pending"
// Clic "Marquer comme complétée"
// → Confirmation
// → TaskService.completeTask(taskId)

// Backend - TaskController@complete
// → Vérifie task.assigned_to === user.id
// → task.status → "completed"
// → task.completed_at → now()
// → Retire tag "en_revision"
// → Ajoute tag "taches_a_valider"
// → (TODO) Notifie admin
```

---

### Cas 3 : Admin valide/demande révision

```javascript
// Frontend - TaskList.vue (sur PDV Detail)
// Admin voit tâche status="completed"

// Option A : Valider
// Clic "Valider"
// → TaskService.validateTask(taskId)
// → task.status → "validated"
// → Si toutes tâches validées → retire tous tags

// Option B : Demander révision
// Clic "Demander révision"
// → RevisionModal.vue s'ouvre
// → Admin entre feedback
// → TaskService.requestRevision(taskId, feedback)
// → task.status → "revision_requested"
// → task.admin_feedback → feedback
// → Tag "taches_a_valider" → "en_revision"
```

---

## 🧪 Tests à effectuer

### Permissions
- [ ] Admin voit tous les PDV
- [ ] Owner voit uniquement son organisation
- [ ] Commercial voit uniquement ses PDV + PDV avec tâches
- [ ] Commercial ne peut pas accéder à /validation, /users, /settings
- [ ] Owner ne peut pas accéder à /validation, /users, /settings

### Tâches
- [ ] Admin peut créer une tâche
- [ ] Sélection commerciaux limitée au dealer du PDV
- [ ] Tag "en_revision" ajouté à la création
- [ ] Commercial peut compléter sa tâche
- [ ] Tag "taches_a_valider" ajouté à la complétion
- [ ] Admin peut valider une tâche
- [ ] Tags retirés quand toutes tâches validées
- [ ] Admin peut demander révision
- [ ] Feedback affiché au commercial
- [ ] Commercial peut resoumettre après révision

### UI
- [ ] TaskModal charge les commerciaux du dealer
- [ ] TaskList affiche correctement les tâches et tags
- [ ] Actions affichées selon le rôle
- [ ] TaskListView filtre par statut
- [ ] Statistiques correctes
- [ ] Navigation vers PDV fonctionne

---

## 🚀 Prochaines étapes

1. **Notifications en temps réel**
   - Implémenter Laravel Echo + WebSockets
   - Envoyer notifications lors des événements tâches
   - Badge de compteur dans Navbar

2. **Historique des tâches**
   - Conserver historique complet (qui a fait quoi et quand)
   - Page dédiée pour voir l'historique d'un PDV

3. **Tableaux de bord améliorés**
   - Stats tâches dans Dashboard
   - Performance commerciaux (tâches complétées, délai moyen)
   - Graphiques évolution tâches

4. **Filtres avancés**
   - Filtrer tâches par commercial
   - Filtrer tâches par PDV
   - Filtrer par plage de dates

5. **Rappels automatiques**
   - Email si tâche pending depuis X jours
   - Notification si révision non traitée

---

## 📚 Fichiers modifiés/créés

### Backend
- ✅ `database/migrations/2025_12_05_120000_create_tasks_table.php`
- ✅ `app/Models/Task.php`
- ✅ `app/Models/PointOfSaleTag.php`
- ✅ `app/Models/PointOfSale.php` (ajout relations tasks et tags)
- ✅ `app/Models/User.php` (ajout isCommercial(), relations tasks)
- ✅ `app/Http/Controllers/TaskController.php`
- ✅ `app/Http/Controllers/PointOfSaleController.php` (filtre commerciaux)
- ✅ `routes/api.php` (routes tasks)

### Frontend
- ✅ `src/services/TaskService.js`
- ✅ `src/components/TaskModal.vue`
- ✅ `src/components/TaskList.vue`
- ✅ `src/components/RevisionModal.vue`
- ✅ `src/views/TaskListView.vue`
- ✅ `src/views/PointOfSaleDetail.vue` (intégration TaskList)
- ✅ `src/router/index.js` (route /tasks)
- ✅ `src/components/Navbar.vue` (lien "Mes tâches")

---

## 💡 Notes importantes

1. **Sécurité**
   - Toutes les actions sont vérifiées côté backend
   - Les commerciaux ne peuvent agir que sur leurs propres tâches
   - Les admin ont un contrôle total

2. **Performance**
   - Utiliser eager loading pour les relations (tasks, tags)
   - Paginer les listes de tâches
   - Indexer les colonnes fréquemment filtrées

3. **UX**
   - Feedbacks clairs pour chaque action
   - Confirmations pour actions importantes
   - Indicateurs visuels (tags, badges)

4. **Extensibilité**
   - Le système peut être étendu avec d'autres types de tâches
   - Les tags peuvent être utilisés pour d'autres workflows
   - Les notifications peuvent être personnalisées par rôle

---

**Dernière mise à jour :** 5 décembre 2025  
**Version :** 1.0  
**Auteur :** GitHub Copilot
