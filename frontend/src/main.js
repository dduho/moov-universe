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
    console.log('[App] IndexedDB prête');
  } catch (error) {
    console.error('[App] Erreur d\'initialisation IndexedDB:', error);
    // Continue anyway - l'app peut fonctionner sans IndexedDB
  }

  // Enregistrer le Service Worker uniquement en prod pour éviter d'interférer avec HMR en dev
  if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/service-worker.js')
        .then(registration => {
          console.log('[App] Service Worker enregistré:', registration.scope);
        })
        .catch(error => {
          console.error('[App] Erreur d\'enregistrement du Service Worker:', error);
        });
    });
  }

  app.mount('#app');
})()

