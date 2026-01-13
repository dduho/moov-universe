import { ref, computed, onMounted, onUnmounted } from 'vue';
import { offlineDB } from '../utils/offlineDB';

const isOnline = ref(navigator.onLine);
const syncInProgress = ref(false);
const pendingActionsCount = ref(0);

export function useOffline() {
  let registration = null;
  let updatePrompted = false;
  let refreshing = false;

  // Recharger une seule fois quand le nouveau SW prend le contrôle
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (refreshing) return;
      refreshing = true;
      window.location.reload();
    });
  }

  // Mettre à jour le statut de connexion
  const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    console.log('[Offline] Statut connexion:', isOnline.value ? 'En ligne' : 'Hors ligne');
    
    if (isOnline.value) {
      // Déclencher la synchronisation quand on revient en ligne
      triggerSync();
    }
  };

  // Enregistrer le Service Worker
  const registerServiceWorker = async () => {
    // Ne pas enregistrer de service worker en développement pour éviter de bloquer HMR/WebSocket
    if (!import.meta.env.PROD) {
      return null;
    }

    if ('serviceWorker' in navigator) {
      try {
        // Réutiliser l'enregistrement existant si présent pour éviter les doubles listeners
        registration = (await navigator.serviceWorker.getRegistration())
          || await navigator.serviceWorker.register('/service-worker.js', { scope: '/' });
        
        console.log('[Offline] Service Worker enregistré:', registration.scope);

        // Écouter les mises à jour
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          console.log('[Offline] Nouvelle version détectée');
          
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller && !updatePrompted) {
              updatePrompted = true;
              console.log('[Offline] Nouvelle version disponible - activation en cours');
              const shouldReload = confirm('Une nouvelle version est disponible. Recharger ?');
              if (shouldReload) {
                // Activer immédiatement puis laisser controllerchange recharger
                newWorker.postMessage({ type: 'SKIP_WAITING' });
              } else {
                // Ne pas boucler si l'utilisateur refuse
                updatePrompted = false;
              }
            }
          });
        });

        return registration;
      } catch (error) {
        console.error('[Offline] Erreur d\'enregistrement du Service Worker:', error);
        return null;
      }
    }
    return null;
  };

  // Déclencher la synchronisation en arrière-plan
  const triggerSync = async () => {
    if (!registration || !registration.sync) {
      console.log('[Offline] Background Sync non supporté, sync manuelle...');
      await syncPendingActions();
      return;
    }

    try {
      await registration.sync.register('sync-pending-actions');
      console.log('[Offline] Synchronisation programmée');
    } catch (error) {
      console.error('[Offline] Erreur de programmation sync:', error);
      await syncPendingActions();
    }
  };

  // Synchroniser les actions en attente
  const syncPendingActions = async () => {
    if (syncInProgress.value || !isOnline.value) {
      return;
    }

    syncInProgress.value = true;

    try {
      const actions = await offlineDB.getPendingActions();
      console.log(`[Offline] ${actions.length} action(s) à synchroniser`);

      for (const action of actions) {
        try {
          const response = await fetch(action.url, {
            method: action.method,
            headers: action.headers,
            body: action.body,
            credentials: 'include'
          });

          if (response.ok) {
            await offlineDB.deletePendingAction(action.id);
            console.log('[Offline] Action synchronisée:', action.id);
          } else {
            console.warn('[Offline] Échec de synchronisation:', response.status);
          }
        } catch (error) {
          console.error('[Offline] Erreur lors de la sync d\'une action:', error);
        }
      }

      await updatePendingCount();
    } catch (error) {
      console.error('[Offline] Erreur générale de synchronisation:', error);
    } finally {
      syncInProgress.value = false;
    }
  };

  // Mettre à jour le compteur d'actions en attente
  const updatePendingCount = async () => {
    try {
      const actions = await offlineDB.getPendingActions();
      pendingActionsCount.value = actions.length;
    } catch (error) {
      console.error('[Offline] Erreur de comptage:', error);
    }
  };

  // Sauvegarder une action pour synchronisation ultérieure
  const saveForLater = async (url, method, headers, body) => {
    try {
      await offlineDB.addPendingAction({
        url,
        method,
        headers,
        body: body ? JSON.stringify(body) : null
      });
      
      await updatePendingCount();
      console.log('[Offline] Action sauvegardée pour synchronisation');
      return true;
    } catch (error) {
      console.error('[Offline] Erreur de sauvegarde:', error);
      return false;
    }
  };

  // Effectuer une requête avec gestion offline
  const offlineFetch = async (url, options = {}) => {
    // Essayer la requête réseau d'abord
    if (isOnline.value) {
      try {
        const response = await fetch(url, options);
        
        // Si succès, mettre en cache si GET
        if (response.ok && options.method === 'GET') {
          const data = await response.clone().json();
          await offlineDB.cacheData(url, data, 30); // Cache 30 min
        }
        
        return response;
      } catch (error) {
        console.warn('[Offline] Requête réseau échouée, tentative cache...', error);
      }
    }

    // Si hors ligne ou erreur, essayer le cache
    if (options.method === 'GET' || !options.method) {
      const cached = await offlineDB.getCachedData(url);
      if (cached) {
        console.log('[Offline] Données récupérées du cache');
        return new Response(JSON.stringify(cached), {
          status: 200,
          headers: { 'Content-Type': 'application/json' }
        });
      }
    }

    // Si POST/PUT/DELETE et hors ligne, sauvegarder pour plus tard
    if (!isOnline.value && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method)) {
      await saveForLater(url, options.method, options.headers, options.body);
      
      return new Response(
        JSON.stringify({ 
          success: true, 
          offline: true,
          message: 'Action enregistrée. Elle sera synchronisée quand vous serez en ligne.' 
        }),
        {
          status: 202,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }

    // Aucune solution disponible
    throw new Error('Données non disponibles hors ligne');
  };

  // Lifecycle hooks
  onMounted(async () => {
    // Enregistrer le Service Worker (prod uniquement) ou nettoyer en dev
    if (!import.meta.env.PROD && 'serviceWorker' in navigator) {
      const registrations = await navigator.serviceWorker.getRegistrations();
      await Promise.all(registrations.map((reg) => reg.unregister()));
      console.log('[Offline] Service Workers désenregistrés en développement');
    } else {
      await registerServiceWorker();
    }
    
    // Initialiser IndexedDB
    try {
      await offlineDB.init();
      console.log('[Offline] IndexedDB initialisée');
    } catch (error) {
      console.error('[Offline] Erreur d\'initialisation IndexedDB:', error);
    }

    // Mettre à jour le compteur
    await updatePendingCount();

    // Écouter les changements de connexion
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
  });

  onUnmounted(() => {
    window.removeEventListener('online', updateOnlineStatus);
    window.removeEventListener('offline', updateOnlineStatus);
  });

  return {
    isOnline: computed(() => isOnline.value),
    syncInProgress: computed(() => syncInProgress.value),
    pendingActionsCount: computed(() => pendingActionsCount.value),
    syncPendingActions,
    saveForLater,
    offlineFetch,
    updatePendingCount,
    registerServiceWorker
  };
}
