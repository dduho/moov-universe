import { precacheAndRoute, cleanupOutdatedCaches, createHandlerBoundToURL } from 'workbox-precaching'
import { registerRoute, NavigationRoute } from 'workbox-routing'
import { StaleWhileRevalidate, NetworkFirst, CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'

const APP_SHELL_CACHE = 'moov-app-shell-v4' // Incrémenter à chaque déploiement
const ASSETS_CACHE = 'moov-assets-v4'
const IMAGES_CACHE = 'moov-images-v4'
const API_CACHE = 'moov-api-v4'

const PRECACHE_MANIFEST = self.__WB_MANIFEST || []

// Pré-cache de l'app shell
// Note: offline.html est déjà dans PRECACHE_MANIFEST, ne pas l'ajouter en double
precacheAndRoute(PRECACHE_MANIFEST)

cleanupOutdatedCaches()

// Forcer la prise de contrôle immédiate lors de l'installation
self.addEventListener('install', (event) => {
  console.log('[SW] Installation - nouvelle version détectée')
  // Skip waiting pour activer immédiatement le nouveau SW
  self.skipWaiting()
})

// Prendre le contrôle de tous les clients immédiatement
self.addEventListener('activate', (event) => {
  console.log('[SW] Activation - prise de contrôle des clients')
  event.waitUntil(
    Promise.all([
      // Supprimer les anciens caches
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName.startsWith('moov-') && 
                ![APP_SHELL_CACHE, ASSETS_CACHE, IMAGES_CACHE, API_CACHE].includes(cacheName)) {
              console.log('[SW] Suppression ancien cache:', cacheName)
              return caches.delete(cacheName)
            }
          })
        )
      }),
      // Prendre le contrôle de tous les clients
      self.clients.claim()
    ])
  )
})

// Notifier les clients qu'une mise à jour est disponible
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting()
  }
})

// Navigation (SPA) : app shell avec fallback offline
const navigationHandler = createHandlerBoundToURL('/index.html')

registerRoute(
  new NavigationRoute(async (context) => {
    try {
      // Essayer de récupérer la page depuis le réseau
      return await navigationHandler.handle(context)
    } catch (error) {
      console.log('[SW] Navigation failed, trying offline page:', error)
      // Si le réseau échoue, essayer la page offline
      const offline = await caches.match('/offline.html')
      if (offline) return offline
      // En dernier recours, laisser la requête passer (ne pas bloquer)
      return fetch(context.request).catch(() => new Response('Offline', { status: 503, statusText: 'Service Unavailable' }))
    }
  }, { 
    denylist: [
      /^\/api\//,           // Exclure les appels API
      /\.(json|xml|txt)$/,  // Exclure les fichiers de données
    ] 
  })
)

// JS/CSS/workers : stale-while-revalidate
registerRoute(
  ({ request }) => ['script', 'style', 'worker'].includes(request.destination),
  new StaleWhileRevalidate({
    cacheName: ASSETS_CACHE,
    plugins: [
      new ExpirationPlugin({
        maxEntries: 120,
        maxAgeSeconds: 60 * 60 * 24 * 14
      })
    ]
  })
)

// Images : cache-first avec expiration
registerRoute(
  ({ request }) => request.destination === 'image',
  new CacheFirst({
    cacheName: IMAGES_CACHE,
    plugins: [
      new CacheableResponsePlugin({ statuses: [0, 200] }),
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 60 * 60 * 24 * 30
      })
    ]
  })
)

// IMPORTANT: Les appels API ne sont PAS mis en cache par le Service Worker
// pour permettre au cache Redis backend de fonctionner efficacement.
// Toutes les requêtes API passent directement au serveur qui gère le cache avec Redis.
// Le Service Worker gère uniquement le mode offline avec des messages d'erreur clairs.

// API non-GET : réponse claire en mode hors ligne
self.addEventListener('fetch', (event) => {
  const { request } = event
  const url = new URL(request.url)

  if (url.pathname.startsWith('/api/') && request.method !== 'GET') {
    event.respondWith(
      fetch(request).catch(() => new Response(
        JSON.stringify({
          error: 'offline',
          message: 'Impossible d\'envoyer des données en mode hors ligne.'
        }),
        { status: 503, headers: { 'Content-Type': 'application/json' } }
      ))
    )
  }
})

// Gestion de la synchronisation en arrière-plan
self.addEventListener('sync', (event) => {
  console.log('[SW] Sync event:', event.tag)
  
  if (event.tag === 'sync-pending-actions') {
    event.waitUntil(syncPendingActions())
  }
})

async function syncPendingActions() {
  try {
    // Récupérer les actions en attente depuis IndexedDB
    const db = await openDB();
    const actions = await getAllPendingActions(db);
    
    console.log(`[SW] ${actions.length} action(s) à synchroniser`);
    
    for (const action of actions) {
      try {
        const response = await fetch(action.url, {
          method: action.method,
          headers: action.headers,
          body: action.body
        });
        
        if (response.ok) {
          // Supprimer l'action synchronisée
          await deletePendingAction(db, action.id);
          console.log('[SW] Action synchronisée:', action.id);
        }
      } catch (error) {
        console.error('[SW] Erreur lors de la sync:', error);
      }
    }
  } catch (error) {
    console.error('[SW] Erreur générale de synchronisation:', error);
    throw error; // Retry la sync
  }
}

// Helper pour ouvrir IndexedDB
function openDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('moov-offline-db', 1);
    
    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);
    
    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('pending-actions')) {
        db.createObjectStore('pending-actions', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

function getAllPendingActions(db) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pending-actions'], 'readonly');
    const store = transaction.objectStore('pending-actions');
    const request = store.getAll();
    
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

function deletePendingAction(db, id) {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(['pending-actions'], 'readwrite');
    const store = transaction.objectStore('pending-actions');
    const request = store.delete(id);
    
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}
