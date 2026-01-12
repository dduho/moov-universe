# Système de Cache Hybride (Redis + localStorage)

## 🎯 Vue d'ensemble

Ce document décrit l'implémentation du système de cache hybride qui combine le cache backend Redis avec un cache frontend localStorage, utilisant la stratégie **stale-while-revalidate avec TTL localStorage intelligent**.

## 🚀 Optimisation majeure : TTL localStorage

**Le problème résolu :**
Dans la version précédente, chaque chargement faisait systématiquement une requête API en background pour vérifier si les données avaient changé, même si elles étaient récentes.

**La solution optimale :**
Ajout d'un TTL (Time To Live) au niveau du localStorage. Si les données ne sont pas expirées localement, aucune requête n'est effectuée.

### Flow optimisé

```
1er chargement:
API → Redis (backend) → localStorage (frontend) → Affichage

Chargements suivants (cache valide):
localStorage → Vérification TTL → Si valide: Affichage SANS requête ✨

Chargements suivants (cache expiré):
localStorage → Affichage instantané
    ↓
API en background → Redis répond si valide
    ↓
Comparaison localStorage vs réponse
    ↓
Si différent: Sync + Toast "Données synchronisées"
Si identique: Rafraîchir timestamp seulement
```

## ⚡ Avantages de cette approche

1. **Zéro requête** si les données sont récentes (< TTL)
2. **Affichage instantané** dans tous les cas
3. **Synchronisation transparente** en background si expiré
4. **Double cache** optimisé : Redis (serveur) + localStorage (client)
5. **Économie de bande passante** significative

## 📊 Architecture

### Backend - Redis Cache
- **TTL configurable** : Défini dans les paramètres système (system_settings)
- **Endpoints cachés** :
  - Analyse de rentabilité (`/rentability/analyze`)
  - Analyse prédictive (`/predictive-analytics/predictions`)
  - Alertes prédictives (`/predictive-analytics/alerts`)
  - Recommandations (`/predictive-analytics/optimization-recommendations`)
  - Analytics transactions (`/analytics/transactions`, `/analytics/insights`, `/analytics/monthly-revenue`)
  - Liste des PDV (`/point-of-sales/list`)
  - Données de géolocalisation
  - Détection de fraude

### Frontend - localStorage Cache
- **Limite de taille** : 3 MB
- **Stratégie** : stale-while-revalidate avec TTL
- **Nettoyage automatique** : Suppression des entrées les plus anciennes si quota dépassé
- **Versioning** : Système de version pour invalider automatiquement les anciens caches
- **TTL par endpoint** : Configurable pour chaque type de données

## 🔧 Paramètres système ajoutés

### Cache Prédictions
```sql
INSERT INTO system_settings (key, value, type, description) VALUES
('cache_predictions_enabled', 'true', 'boolean', 'Activer le cache pour l\'analyse prédictive'),
('cache_predictions_ttl', '30', 'integer', 'Durée du cache pour l\'analyse prédictive en minutes');
```

### Cache Analytics
```sql
INSERT INTO system_settings (key, value, type, description) VALUES
('cache_analytics_enabled', 'true', 'boolean', 'Activer le cache pour les analytics de transactions'),
('cache_analytics_ttl', '15', 'integer', 'Durée du cache pour les analytics en minutes');
```

### Cache PDV
```sql
INSERT INTO system_settings (key, value, type, description) VALUES
('cache_pdv_enabled', 'true', 'boolean', 'Activer le cache pour la liste des PDV'),
('cache_pdv_ttl', '10', 'integer', 'Durée du cache pour la liste des PDV en minutes');
```

Ces paramètres ont été ajoutés dans `SystemSettingSeeder.php` et sont maintenant présents dans la table `system_settings`.

## 📦 Composants migrés vers le cache hybride

### ✅ Complètement migrés

#### 1. **TrendAnalytics.vue** - Analyse prédictive
- `/predictive-analytics/predictions` (TTL: 30min)
- `/predictive-analytics/alerts` (TTL: 30min)
- `/predictive-analytics/optimization-recommendations` (TTL: 30min)

#### 2. **RentabilityWidget.vue** - Analyse de rentabilité
- `/rentability/analyze` (TTL: 240min = 4h)

#### 3. **MapView.vue** - Carte & Heatmap
- `/rentability/analyze-map` (TTL: 30min, pas de toast)

#### 4. **TransactionAnalytics.vue** - Analytics
- `/analytics/transactions` (TTL: 15min)
- `/analytics/monthly-revenue` (TTL: 60min = 1h)
- `/analytics/insights` (TTL: 20min)

#### 5. **PointOfSaleList.vue** - Liste des PDV
- `/point-of-sales/list` (TTL: 10min)

### 📋 TTL recommandés par type de données

| Type de données | TTL | Justification |
|----------------|-----|--------------|
| **Prédictions** | 30 min | Analyses lourdes, changent lentement |
| **Alertes** | 30 min | Détection basée sur seuils, stable |
| **Rentabilité** | 4 heures | Calculs très lourds, peu volatiles |
| **Analytics** | 15 min | Données transactionnelles, mises à jour régulières |
| **Insights AI** | 20 min | Génération coûteuse, recommandations stables |
| **Revenus mensuels** | 1 heure | Agrégations mensuelles, changent peu |
| **Liste PDV** | 10 min | Données fréquemment consultées, changent modérément |
| **Map performance** | 30 min | Heatmap, mise à jour régulière suffisante |

## 🔧 Utilisation du composable

### Exemple basique
```javascript
import { useCacheStore } from '../composables/useCacheStore';

const { fetchWithCache } = useCacheStore();

await fetchWithCache(
  'analytics/transactions', // endpoint
  async () => {
    // Fonction de fetch
    const response = await AnalyticsService.getAnalytics(params);
    return response.data;
  },
  params, // Paramètres pour la clé de cache
  {
    ttl: 15, // TTL en minutes
    showSyncToast: false, // Désactiver le toast
    onDataUpdate: (data, fromCache) => {
      // Callback appelé avec les données
      myData.value = data;
    }
  }
);
```

### Options avancées
```javascript
{
  ttl: 30,              // TTL en minutes (défaut: 30)
  forceRefresh: false,  // Forcer le refresh (défaut: false)
  showSyncToast: true,  // Afficher toast sync (défaut: true)
  onDataUpdate: (data, fromCache) => {
    // fromCache = true si données du cache
    // fromCache = false si données de l'API
  }
}
```

## 🎯 Logique d'optimisation

### Cas 1 : Cache valide (< TTL)
```javascript
localStorage → Vérification TTL → AUCUNE REQUÊTE ✨
```
**Performance** : Instantané, 0 requête HTTP

### Cas 2 : Cache expiré (> TTL)
```javascript
localStorage → Affichage immédiat
    ↓
API (background) → Comparaison → Sync si différent
```
**Performance** : Affichage instantané + sync background

### Cas 3 : Premier chargement
```javascript
API → Affichage + Stockage localStorage
```
**Performance** : 1 requête HTTP normale

### `useCacheStore.js`
Localisation : `frontend/src/composables/useCacheStore.js`

**Fonctions principales :**
- `fetchWithCache(endpoint, fetchFunction, params, options)` : Fonction principale de gestion du cache
- `clearAllCache()` : Vide tout le cache localStorage
- `clearCacheForEndpoint(endpoint)` : Vide le cache d'un endpoint spécifique
- `getCacheStats()` : Statistiques sur l'utilisation du cache
- `getCachedData(cacheKey)` : Récupère les données du cache
- `setCachedData(cacheKey, data)` : Stocke les données dans le cache

**Gestion d'erreurs :**
- Détection de `QuotaExceededError`
- Nettoyage automatique des vieilles entrées
- Gestion des entrées corrompues

## Composants modifiés

### 1. TrendAnalytics.vue
**Endpoints avec cache :**
- `/predictive-analytics/predictions`
- `/predictive-analytics/alerts`
- `/predictive-analytics/optimization-recommendations`

**Modifications :**
```javascript
import { useCacheStore } from '../composables/useCacheStore';

const { fetchWithCache } = useCacheStore();

await fetchWithCache(
  'predictive-analytics/predictions',
  async () => {
    const response = await PredictionService.getPredictions(params);
    return response;
  },
  params,
  {
    onDataUpdate: (data, fromCache) => {
      // Mise à jour des données
    }
  }
);
```

### 2. RentabilityWidget.vue
**Endpoints avec cache :**
- `/rentability/analyze`

**Fonctionnement :**
- Affichage immédiat des données en cache
- Fetch en background pour mise à jour
- Toast uniquement si données changées

### 3. MapView.vue
**Endpoints avec cache :**
- `/rentability/analyze` (pour la heatmap de performance)

**Option spéciale :**
```javascript
showSyncToast: false  // Pas de toast pour éviter la pollution UI
```

### 4. Settings.vue
**Nouvelles fonctionnalités :**

#### Vidage de cache par widget
```javascript
async function clearWidgetCache(widget) {
  // 1. Vider le cache backend (Redis)
  await SettingService.clearCache(widget.key);
  
  // 2. Vider le cache localStorage pour ce widget
  const endpoint = getEndpointFromWidgetKey(widget.key);
  if (endpoint) {
    clearCacheForEndpoint(endpoint);
  }
  
  toast.success('Cache vidé (backend + localStorage)');
}
```

#### Vidage de tous les caches frontend
```javascript
async function clearFrontendCaches() {
  // 1. Vider stores Pinia
  analyticsCacheStore.clearAll();
  
  // 2. Vider IndexedDB
  await offlineDB.clearAll();
  
  // 3. Vider localStorage (cache hybride)
  clearAllCache();
  
  // 4. Vider Service Worker caches
  const cacheNames = await caches.keys();
  await Promise.all(cacheNames.map(cacheName => caches.delete(cacheName)));
  
  // 5. Réinitialiser Service Worker
  // ...
}
```

## Mapping widget → endpoint

```javascript
const mapping = {
  'cache_rentability': 'rentability/analyze',
  'cache_predictions': 'predictive-analytics/predictions',
  'cache_map': 'rentability/analyze-map',
  'cache_fraud_detection': 'fraud-detection',
  'cache_geolocation': 'geolocation'
};
```

## Backend - PredictionController

### Vérification du cache
Le `PredictionController.php` utilise déjà Redis pour le cache :

```php
$cacheKey = "predictions_" . md5(json_encode($request->all()));

$result = Cache::remember($cacheKey, 1800, function () use (...) {
    // Logique de prédiction
});
```

**TTL actuel :** 1800 secondes (30 minutes) - hardcodé
**TODO potentiel :** Récupérer le TTL depuis les system_settings

## Avantages du système

### Performance
- ⚡ **Chargement instantané** : Affichage immédiat depuis localStorage
- 🔄 **Synchronisation transparente** : Mise à jour en background
- 💾 **Double cache** : Redis (backend) + localStorage (frontend)

### Expérience utilisateur
- 📱 **Hors ligne** : Données disponibles même sans connexion
- 🔔 **Notification** : Toast "Données synchronisées" uniquement si changement
- 🎯 **Ciblé** : Cache par endpoint avec paramètres spécifiques

### Maintenance
- 🧹 **Auto-nettoyage** : Suppression automatique si quota dépassé
- 📊 **Statistiques** : Monitoring de l'utilisation du cache
- 🔧 **Configurable** : TTL et activation par widget dans Settings

## Gestion de la limite de 3 MB

### Stratégie de nettoyage
1. Tri des entrées par timestamp (plus ancien → plus récent)
2. Suppression progressive jusqu'à atteindre 80% de la limite
3. Retry automatique après nettoyage

### Détection
```javascript
const MAX_CACHE_SIZE = 3 * 1024 * 1024; // 3MB

const hasStorageSpace = (dataSize) => {
  const currentSize = getLocalStorageSize();
  return (currentSize + dataSize) < MAX_CACHE_SIZE;
};
```

## Debugging

### Console logs
Le système log automatiquement :
- ✅ Succès de stockage
- 🧹 Nettoyage de cache
- 📊 Nombre d'entrées supprimées
- ⚠️ Erreurs de quota

### Statistiques
```javascript
const stats = getCacheStats();
// {
//   count: 12,
//   size: 2458912,
//   sizeFormatted: "2401.28 KB",
//   maxSize: 3145728,
//   maxSizeFormatted: "3.00 MB",
//   usagePercent: "78.16"
// }
```

## Configuration recommandée

### TTL par type de données
- **Prédictions** : 30 minutes (données analytiques lentes à changer)
- **Rentabilité** : 4 heures (calculs lourds)
- **Map** : 30 minutes (données géographiques stables)
- **Détection de fraude** : 3 heures (analyses complexes)

### Activation
Tous les caches sont activés par défaut dans `system_settings` :
- `cache_predictions_enabled = true`
- `cache_rentability_enabled = true`
- `cache_map_enabled = true`
- etc.

## Tests manuels recommandés

1. **Premier chargement** : Vérifier que les données se chargent et se stockent
2. **Rechargement** : Vérifier l'affichage instantané depuis localStorage
3. **Modification backend** : Vérifier le toast "Données synchronisées"
4. **Quota dépassé** : Charger beaucoup de données, vérifier le nettoyage
5. **Vidage cache** : Tester les boutons dans Settings

## Fichiers modifiés

### Backend
- `backend/database/seeders/SystemSettingSeeder.php` : Ajout paramètres cache prédictions

### Frontend
- `frontend/src/composables/useCacheStore.js` : **NOUVEAU** - Composable de cache
- `frontend/src/components/TrendAnalytics.vue` : Intégration cache prédictions
- `frontend/src/components/RentabilityWidget.vue` : Intégration cache rentabilité
- `frontend/src/views/MapView.vue` : Intégration cache map
- `frontend/src/views/Settings.vue` : Vidage localStorage ajouté

## Notes importantes

⚠️ **Versioning** : Le cache utilise un système de version (`CACHE_VERSION = '1.0'`). Si vous modifiez la structure des données, incrémentez cette version pour invalider automatiquement tous les anciens caches.

⚠️ **Comparaison** : La comparaison des données utilise `JSON.stringify()`. Pour des objets complexes avec ordre des clés différent, envisager une lib comme lodash `isEqual()`.

⚠️ **PredictionController** : Le TTL est actuellement hardcodé à 1800 secondes. Pour une cohérence totale, envisager de le récupérer depuis les system_settings.

## Prochaines étapes possibles

1. **Métriques avancées** : Dashboard de monitoring du cache
2. **Compression** : Compresser les données avant stockage (LZ-string)
3. **Préchargement** : Charger les données en anticipé (predictive prefetch)
4. **Invalidation intelligente** : Invalider automatiquement si l'utilisateur modifie des données
5. **Cache partagé** : Partager le cache entre onglets via BroadcastChannel API
