import { precacheAndRoute, cleanupOutdatedCaches, createHandlerBoundToURL } from 'workbox-precaching'
import { registerRoute, NavigationRoute } from 'workbox-routing'
import { StaleWhileRevalidate, NetworkFirst, CacheFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'
import { CacheableResponsePlugin } from 'workbox-cacheable-response'

const APP_SHELL_CACHE = 'moov-app-shell-v2'
const ASSETS_CACHE = 'moov-assets-v2'
const IMAGES_CACHE = 'moov-images-v2'
const API_CACHE = 'moov-api-v2'

const PRECACHE_MANIFEST = self.__WB_MANIFEST || []

// Pré-cache de l'app shell
// Note: offline.html est déjà dans PRECACHE_MANIFEST, ne pas l'ajouter en double
precacheAndRoute(PRECACHE_MANIFEST)

cleanupOutdatedCaches()

// Navigation (SPA) : app shell avec fallback offline
const navigationHandler = createHandlerBoundToURL('/index.html')

registerRoute(
  new NavigationRoute(async (context) => {
    try {
      return await navigationHandler.handle(context)
    } catch (error) {
      const offline = await caches.match('/offline.html')
      if (offline) return offline
      return Response.error()
    }
  }, { denylist: [/^\/api\//] })
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

// API GET : network-first avec cache de secours
registerRoute(
  ({ url, request }) => url.pathname.startsWith('/api/') && request.method === 'GET',
  new NetworkFirst({
    cacheName: API_CACHE,
    networkTimeoutSeconds: 5,
    plugins: [
      new CacheableResponsePlugin({ statuses: [0, 200] }),
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 60 * 60 * 24
      })
    ]
  })
)

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
