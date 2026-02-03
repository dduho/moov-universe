import { createApp } from 'vue'
import './style.css'
import 'leaflet/dist/leaflet.css'
import App from './App.vue'
import router from './router'
import { offlineDB } from './utils/offlineDB'
import pinia from './plugins/pinia'
import { setActivePinia } from 'pinia'

const app = createApp(App)

// Activer Pinia avant toute utilisation de stores (garde de route incluse)
setActivePinia(pinia)

app.use(pinia)
app.use(router)

// Initialiser IndexedDB au démarrage et attendre qu'elle soit prête
;(async () => {
  try {
    await offlineDB.init();
    if (offlineDB.isAvailable) {
      console.log('[App] ✅ IndexedDB prête');
    } else {
      console.warn('[App] ⚠️ IndexedDB indisponible - Mode dégradé activé');
    }
  } catch (error) {
    console.warn('[App] ⚠️ IndexedDB non disponible:', error.message);
    // Continue anyway - l'app fonctionne sans IndexedDB
  }

  // Enregistrer le Service Worker uniquement en prod pour éviter d'interférer avec HMR en dev
  if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js')
        .then(registration => {
          console.log('[App] Service Worker enregistré:', registration.scope);
          
          // Vérifier les mises à jour toutes les 60 secondes
          setInterval(() => {
            registration.update();
          }, 60000);
          
          // Détecter une nouvelle version en attente
          let refreshing = false;
          navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) return;
            refreshing = true;
            window.location.reload();
          });

          registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('[App] Nouvelle version du Service Worker détectée');
            
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // Nouvelle version disponible
                console.log('[App] Nouvelle version prête - activation');
                newWorker.postMessage({ type: 'SKIP_WAITING' });
              }
            });
          });
        })
        .catch(error => {
          console.error('[App] Erreur d\'enregistrement du Service Worker:', error);
        });
    });
  }

  app.mount('#app');
})()

