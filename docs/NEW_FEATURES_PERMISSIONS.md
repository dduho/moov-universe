# Nouvelles Fonctionnalités - Permissions et Accès

## Date de mise à jour : 26 décembre 2025

## 📋 Vue d'ensemble

Ce document définit les niveaux d'accès pour les nouvelles fonctionnalités implémentées dans Moov Universe.

---

## 🔐 Matrice des Permissions

| Fonctionnalité | Admin | Dealer Owner | Dealer Agent | Description |
|---|---|---|---|---|
| **Recherche Globale** | ✅ Complet | ✅ Limité à son org | ✅ Limité à ses PDV | Recherche multi-entités avec autocomplete |
| **Comparateur (#12)** | ✅ Uniquement | ❌ | ❌ | Comparaison de PDV, Dealers, Périodes |
| **Analytics Transactions** | ✅ Uniquement | ❌ | ❌ | Visualisation avancée des transactions |
| **Forecasting (#4)** | ✅ Uniquement | ❌ | ❌ | Prédictions CA basées sur tendances |
| **Recommandations AI (#19)** | ✅ Uniquement | ❌ | ❌ | Actions recommandées par PDV/dealer |
| **Détection Fraude (#18)** | ✅ Uniquement | ❌ | ❌ | Patterns suspects, scores de risque |
| **Géolocalisation Avancée (#16)** | ✅ Uniquement | ❌ | ❌ | Heatmap CA, clustering, zones à potentiel |
| **Mode Hors-ligne (#11)** | ✅ | ✅ | ✅ | Service Worker, cache local, sync |

---

## 🔍 Détails par Fonctionnalité

### 1. Recherche Globale (Accessible à tous - avec restrictions)

**Endpoints Backend :**
- `GET /api/search` - Recherche multi-entités
- `GET /api/search/suggestions` - Autocomplete

**Permissions :**
- **Admin** : Accès complet (tous PDV, tous dealers, toutes régions)
- **Dealer Owner** : Limité à son organisation (PDV de son org uniquement)
- **Dealer Agent** : Limité à ses créations (PDV qu'il a créés)

**Accès Frontend :**
- Bouton recherche dans Navbar (tous utilisateurs authentifiés)
- Raccourci clavier : `Ctrl+K` / `Cmd+K`
- Composant : `GlobalSearch.vue`

**Filtrage appliqué :**
```php
// Backend - GlobalSearchController.php
if ($user->isDealerOwner()) {
    $query->where('organization_id', $user->organization_id);
} elseif ($user->isDealerAgent()) {
    $query->where('created_by', $user->id);
}
```

---

### 2. Comparateur (#12) - Admin uniquement ⭐

**Endpoints Backend :**
- `POST /api/comparator/compare` - Comparaison principale
- `GET /api/pdv` - Recherche PDV pour sélection
- `GET /api/dealers` - Liste dealers

**Middleware appliqué :**
```php
Route::middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
    Route::post('/comparator/compare', [ComparatorController::class, 'compare']);
    Route::get('/pdv', [ComparatorController::class, 'searchPdvs']);
    Route::get('/dealers', [ComparatorController::class, 'searchDealers']);
});
```

**Accès Frontend :**
- Menu Admin dans Navbar (dropdown "Administration")
- Route : `/comparator`
- Composant : `Comparator.vue`

**Fonctionnalités :**
- Comparaison de 2-4 PDV (CA, dépôts, retraits, évolution)
- Comparaison de dealers (stats agrégées)
- Comparaison de périodes (tendances temporelles)
- Export PDF des résultats

---

### 3. Analytics Transactions - Admin uniquement ⭐

**Endpoints Backend :**
- `GET /api/transaction-analytics` - Données agrégées
- `GET /api/analytics-insights` - Insights AI

**Middleware appliqué :**
```php
Route::middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
    Route::get('/transaction-analytics', [TransactionAnalyticsController::class, 'getAnalytics']);
    Route::get('/analytics-insights', [AnalyticsInsightsController::class, 'getInsights']);
});
```

**Accès Frontend :**
- Menu Admin dans Navbar
- Route : `/analytics`
- Composant : `TransactionAnalytics.vue`

**Optimisations :**
- Cache Laravel : 1 heure (3600s)
- Première requête : ~9s (268k transactions)
- Requêtes suivantes : <1s (cached)

---

### 4. Forecasting & Prédictions (#4) - Admin uniquement ⭐

**À IMPLÉMENTER**

**Permissions prévues :**
- Admin uniquement
- Accès via menu Admin
- Widget sur Dashboard admin

**Fonctionnalités planifiées :**
- Algorithme de régression pour prédiction CA mensuel
- Identification PDV à potentiel
- Prédictions par région/dealer
- Widget : "À ce rythme : X FCFA attendu ce mois"

**Contraintes de sécurité :**
```php
// À implémenter dans ForecastingController
public function getForecast(Request $request)
{
    // Vérifier que l'utilisateur est admin
    if (!$request->user()->isAdmin()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }
    
    // ... logique de prédiction
}
```

---

### 5. Recommandations AI (#19) - Admin uniquement ⭐

**À IMPLÉMENTER**

**Permissions prévues :**
- Admin uniquement
- Extension du système AI Insights existant

**Fonctionnalités planifiées :**
- Actions recommandées par PDV (ex : "Encourager dépôts", "Contacter pour réactivation")
- Actions recommandées par dealer (ex : "Former agents", "Ouvrir nouveau PDV")
- Priorisation par impact potentiel
- Intégration dans AnalyticsInsightsController

---

### 6. Détection de Fraude (#18) - Admin uniquement ⭐

**À IMPLÉMENTER**

**Permissions prévues :**
- Admin uniquement
- Système d'alertes automatiques

**Fonctionnalités planifiées :**
- Détection patterns suspects (ex : transactions à heures anormales, montants inhabituels)
- Scores de risque par PDV/dealer
- Alertes temps réel via notifications
- Dashboard dédié avec métriques fraude

**Règles métier à implémenter :**
- Transactions >500k FCFA hors heures normales
- Ratio dépôts/retraits anormal
- PDV inactif puis pic soudain d'activité
- Géolocalisation incohérente

---

### 7. Géolocalisation Avancée (#16) - Admin uniquement ⭐

**À IMPLÉMENTER**

**Permissions prévues :**
- Admin uniquement
- Visualisation avancée sur carte

**Fonctionnalités planifiées :**
- Heatmap avec intensité basée sur CA
- Clustering automatique des PDV
- Identification zones à potentiel (forte densité, faible couverture)
- Carte interactive avec filtres (région, CA, statut)

**Technologies :**
- Leaflet.js ou Mapbox GL
- Clustering : Leaflet.markercluster
- Heatmap : Leaflet.heat

---

### 8. Mode Hors-ligne (#11) - Tous utilisateurs ✅

**À IMPLÉMENTER**

**Permissions prévues :**
- **Accessible à tous** (Admin, Dealer Owner, Dealer Agent)
- Fonctionnalité critique pour terrain

**Fonctionnalités planifiées :**
- Service Worker pour mise en cache ressources
- IndexedDB pour stockage données (PDV, tâches)
- État de synchronisation visible dans UI
- Queue de modifications hors-ligne
- Sync automatique lors reconnexion

**Données à cacher :**
- Liste PDV (selon permissions utilisateur)
- Tâches assignées
- Formulaires en cours
- Images/documents récents

---

## 📱 Accès Mobile

Toutes les fonctionnalités admin sont optimisées pour desktop/tablette.

**Recommandations :**
- Comparateur : Desktop recommandé (graphiques complexes)
- Analytics : Desktop/Tablette (visualisations larges)
- Recherche globale : Mobile OK (interface responsive)
- Mode hors-ligne : **Mobile prioritaire** (usage terrain)

---

## 🔧 Implémentation Technique

### Vérification des Permissions Backend

```php
// Middleware CheckRole
Route::middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
    // Routes admin uniquement
});

// Dans les contrôleurs
if (!$user->isAdmin()) {
    return response()->json(['error' => 'Unauthorized'], 403);
}
```

### Vérification des Permissions Frontend

```javascript
// Dans les composants Vue
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();

if (!authStore.isAdmin) {
    router.push('/dashboard');
    toast.error('Accès réservé aux administrateurs');
}
```

### Routes Admin (router/index.js)

```javascript
{
    path: '/comparator',
    name: 'Comparator',
    component: () => import('../views/Comparator.vue'),
    meta: { 
        requiresAuth: true,
        requiresAdmin: true  // ⭐ Important
    },
}
```

---

## 📊 Résumé Permissions

| Rôle | Fonctionnalités Accessibles |
|---|---|
| **Admin** | TOUT (8/8 fonctionnalités) |
| **Dealer Owner** | Recherche globale (limité), Mode hors-ligne (2/8) |
| **Dealer Agent** | Recherche globale (limité), Mode hors-ligne (2/8) |

---

## 🚀 Prochaines Étapes

1. ✅ Recherche globale - **TERMINÉE**
2. ⏳ Forecasting (#4) - EN COURS
3. ⏳ Recommandations (#19)
4. ⏳ Détection fraude (#18)
5. ⏳ Géolocalisation (#16)
6. ⏳ Mode hors-ligne (#11)

---

**Note importante :** Toutes les nouvelles fonctionnalités avancées (analytics, comparaison, prédictions, fraude) sont réservées aux admins pour :
- Protéger la confidentialité des données sensibles inter-organisations
- Éviter la surcharge des utilisateurs terrain avec des outils complexes
- Centraliser la prise de décision stratégique
- Simplifier l'interface pour les rôles non-admin
