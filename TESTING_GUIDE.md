# Guide de Test - Corrections Production

## 🎯 Objectifs des tests

Valider que l'application fonctionne correctement même quand IndexedDB échoue ou n'est pas disponible.

## 🧪 Scénarios de test

### Test 1: Mode Navigation Privée ✅

**But:** Vérifier que l'app fonctionne en mode incognito (IndexedDB désactivé)

1. Ouvrir le navigateur en mode navigation privée
2. Accéder à l'application: `https://universe.moov-africa.tg`
3. Vérifier dans la console (F12):
   ```
   [App] ⚠️ IndexedDB indisponible - Mode dégradé activé
   ```
4. ✅ **Succès si:** L'application charge et affiche les PDV normalement

### Test 2: Quota de Stockage Dépassé ⚠️

**But:** Simuler un espace disque plein

1. Ouvrir DevTools (F12) → Application → Storage
2. Remplir le quota avec du dummy data
3. Recharger l'application
4. ✅ **Succès si:** L'app charge et utilise le fallback localStorage

### Test 3: Page de Diagnostic 🔍

**But:** Vérifier l'état du système

1. Accéder à: `https://universe.moov-africa.tg/diagnostic.html`
2. Lancer le diagnostic complet
3. Vérifier tous les indicateurs
4. ✅ **Succès si:** Tous les status sont verts ou jaune (warning acceptable)

### Test 4: Liste des PDV sans Cache 📋

**But:** Charger les PDV quand pas de cache disponible

1. Vider tout le cache:
   ```javascript
   localStorage.clear();
   indexedDB.deleteDatabase('moov-offline-db');
   ```
2. Aller sur /point-of-sales
3. ✅ **Succès si:** La liste charge depuis l'API sans crash

### Test 5: Réseau Coupé avec Cache ⚡

**But:** Mode offline avec fallback

1. Charger l'application normalement (avec cache)
2. DevTools → Network → Offline
3. Recharger la page
4. ✅ **Succès si:** Les données du cache s'affichent

### Test 6: Réseau Coupé SANS Cache ❌

**But:** Vérifier qu'il n'y a pas de crash

1. Vider le cache complet
2. DevTools → Network → Offline
3. Recharger la page
4. ✅ **Succès si:** 
   - Pas de crash
   - Message "Données non disponibles"
   - UI reste fonctionnelle

## 📊 Checklist de Validation

### Console (F12)

- [ ] Pas d'erreur rouge critique
- [ ] Seulement des warnings jaunes acceptables
- [ ] Messages avec emoji ✅ ⚠️ ❌ visibles
- [ ] Aucun "Uncaught Error" ou "UnknownError"

### Interface Utilisateur

- [ ] Page de login charge
- [ ] Dashboard s'affiche
- [ ] Liste PDV accessible
- [ ] Formulaire PDV fonctionne
- [ ] Map s'affiche
- [ ] Settings page responsive

### Fonctionnalités

- [ ] Création de PDV fonctionne
- [ ] Modification de PDV fonctionne
- [ ] Recherche PDV fonctionne
- [ ] Export fonctionne
- [ ] Analytics affichées

## 🔧 Commandes de Test Rapide

### 1. Vérifier l'état d'IndexedDB
```javascript
// Dans la console du navigateur
if (window.indexedDB) {
  console.log('✅ IndexedDB disponible');
  indexedDB.open('moov-offline-db', 1).onsuccess = (e) => {
    const db = e.target.result;
    console.log('Stores:', Array.from(db.objectStoreNames));
    db.close();
  };
} else {
  console.log('❌ IndexedDB non disponible');
}
```

### 2. Forcer le mode dégradé
```javascript
// Simuler IndexedDB non disponible
Object.defineProperty(window, 'indexedDB', {
  get: () => undefined
});
location.reload();
```

### 3. Nettoyer complètement
```javascript
// Tout vider et recharger
localStorage.clear();
indexedDB.deleteDatabase('moov-offline-db');
caches.keys().then(keys => Promise.all(keys.map(k => caches.delete(k))))
  .then(() => location.reload());
```

### 4. Vérifier le stockage
```javascript
navigator.storage.estimate().then(est => {
  console.log('Utilisé:', (est.usage / 1024 / 1024).toFixed(2), 'MB');
  console.log('Quota:', (est.quota / 1024 / 1024).toFixed(2), 'MB');
  console.log('Libre:', ((est.quota - est.usage) / 1024 / 1024).toFixed(2), 'MB');
});
```

## 🚀 Déploiement

### Pré-déploiement

1. **Build production:**
   ```bash
   cd frontend
   npm run build
   ```

2. **Vérifier la compilation:**
   - Pas d'erreurs TypeScript
   - Pas de warnings critiques
   - Service Worker généré dans dist/

3. **Tester le build local:**
   ```bash
   npm run preview
   ```

### Déploiement

1. **Déployer sur le serveur:**
   ```bash
   ./deploy.sh
   ```

2. **Vérifier immédiatement:**
   - Ouvrir https://universe.moov-africa.tg
   - Ouvrir la console (F12)
   - Vérifier les logs d'initialisation
   - Tester la page de diagnostic

3. **Tests post-déploiement:**
   - [ ] Test navigation privée
   - [ ] Test différents navigateurs (Chrome, Firefox, Safari)
   - [ ] Test mobile (Android, iOS)
   - [ ] Test réseau lent (throttling)

## 📈 Monitoring

### Logs à surveiller (Console)

**✅ Bon:**
```
[App] ✅ IndexedDB prête
[offlineDB] IndexedDB initialisée avec succès
[SW] ✅ Installation - nouvelle version détectée
[PDV Service] Liste PDV sauvegardée pour mode offline
```

**⚠️ Acceptable (Mode Dégradé):**
```
[App] ⚠️ IndexedDB indisponible - Mode dégradé activé
[offlineDB] Fonctionnement en mode dégradé (pas d'IndexedDB)
[PDV Service] ✅ Données récupérées du cache
```

**❌ Problématique:**
```
[PDV Service] ❌ Aucune donnée disponible
Uncaught Error: ...
UnknownError: Internal error (sauf sur IndexedDB)
```

## 🐛 Troubleshooting

### Problème: L'app ne charge pas du tout

**Solution:**
1. Vider le cache du navigateur (Ctrl+Shift+Del)
2. Désactiver les extensions du navigateur
3. Tester en navigation privée
4. Vérifier les erreurs console

### Problème: "Données non disponibles"

**Solution:**
1. Vérifier la connexion réseau
2. Vérifier que l'API backend répond
3. Vérifier les CORS
4. Vider le cache et recharger

### Problème: Service Worker ne s'active pas

**Solution:**
1. DevTools → Application → Service Workers
2. Cliquer "Unregister" sur les anciens SW
3. Recharger la page (Ctrl+F5)
4. Vérifier que le nouveau SW s'installe

### Problème: Cache ne se vide pas

**Solution:**
1. Aller sur /diagnostic.html
2. Cliquer "Tout nettoyer"
3. OU manuellement dans DevTools → Application → Clear storage

## 📝 Rapport de Test

Après chaque test, noter:

| Test | Navigateur | Résultat | Notes |
|------|-----------|----------|-------|
| Navigation privée | Chrome | ✅ | Mode dégradé OK |
| Quota dépassé | Firefox | ✅ | Fallback localStorage |
| Offline avec cache | Safari | ⚠️ | Lenteur constatée |
| Offline sans cache | Edge | ✅ | Message correct |

## 🔗 Liens Utiles

- Page de diagnostic: `/diagnostic.html`
- API Status: `/api/health`
- Documentation: `PRODUCTION_ERRORS_FIX.md`
- Service Worker cache: DevTools → Application → Cache Storage

## ✅ Validation Finale

**L'application est prête pour la production si:**

- ✅ Tous les tests passent
- ✅ Aucun crash en mode navigation privée
- ✅ Mode dégradé fonctionne correctement
- ✅ Les données s'affichent même sans IndexedDB
- ✅ Les erreurs sont loggées proprement
- ✅ L'UI reste responsive même en cas d'erreur
- ✅ Le diagnostic est au vert

**Date de validation:** _____________

**Validé par:** _____________

**Signature:** _____________
