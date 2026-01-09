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
          
          // Vérifier les mises à jour toutes les 60 secondes
          setInterval(() => {
            registration.update();
          }, 60000);
          
          // Détecter une nouvelle version en attente
          registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('[App] Nouvelle version du Service Worker détectée');
            
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // Nouvelle version disponible
                console.log('[App] Nouvelle version prête - Rechargement automatique dans 2s');
                
                // Afficher un message à l'utilisateur (optionnel)
                const notification = document.createElement('div');
                notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#FF6600;color:white;padding:15px 20px;border-radius:8px;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,0.2);font-family:sans-serif;';
                notification.textContent = '🔄 Mise à jour disponible - Rechargement...';
                document.body.appendChild(notification);
                
                // Attendre 2 secondes puis recharger
                setTimeout(() => {
                  newWorker.postMessage({ type: 'SKIP_WAITING' });
                  window.location.reload();
                }, 2000);
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

