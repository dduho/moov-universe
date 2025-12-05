import { ref, computed, onMounted, onUnmounted } from 'vue';

/**
 * Composable pour gérer l'installation PWA
 * - Détection du prompt d'installation
 * - État "installé"
 * - Support iOS
 */
export function usePWA() {
  const deferredPrompt = ref(null);
  const canInstall = ref(false);
  const isInstalled = ref(false);
  const isIOS = ref(false);
  const showIOSInstructions = ref(false);
  const isDismissed = ref(false);

  // Détecter si l'app est déjà installée
  const checkIfInstalled = () => {
    // Pour les navigateurs qui supportent getInstalledRelatedApps
    if ('getInstalledRelatedApps' in navigator) {
      navigator.getInstalledRelatedApps().then(apps => {
        isInstalled.value = apps.length > 0;
      }).catch(() => {});
    }
    
    // Vérifier si on est en mode standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
      isInstalled.value = true;
    }
    
    // iOS Safari
    if (window.navigator.standalone === true) {
      isInstalled.value = true;
    }
  };

  // Détecter iOS
  const checkIfIOS = () => {
    const userAgent = window.navigator.userAgent.toLowerCase();
    isIOS.value = /iphone|ipad|ipod/.test(userAgent) && !window.MSStream;
    
    // Sur iOS, on ne peut pas utiliser beforeinstallprompt
    // donc on montre des instructions manuelles
    if (isIOS.value && !isInstalled.value) {
      canInstall.value = true;
    }
  };

  // Handler pour l'événement beforeinstallprompt
  const handleBeforeInstallPrompt = (e) => {
    // Empêcher le prompt automatique
    e.preventDefault();
    // Sauvegarder l'événement pour plus tard
    deferredPrompt.value = e;
    canInstall.value = true;
  };

  // Handler pour l'événement appinstalled
  const handleAppInstalled = () => {
    deferredPrompt.value = null;
    canInstall.value = false;
    isInstalled.value = true;
    showIOSInstructions.value = false;
    
    // Vibration de confirmation
    if ('vibrate' in navigator) {
      navigator.vibrate([100, 50, 100]);
    }
  };

  // Installer l'application (pour Chrome/Android)
  const install = async () => {
    if (!deferredPrompt.value) {
      if (isIOS.value) {
        showIOSInstructions.value = true;
      }
      return false;
    }

    try {
      // Montrer le prompt d'installation
      deferredPrompt.value.prompt();
      
      // Attendre la réponse
      const { outcome } = await deferredPrompt.value.userChoice;
      
      // Nettoyer
      deferredPrompt.value = null;
      
      if (outcome === 'accepted') {
        canInstall.value = false;
        return true;
      }
      return false;
    } catch (error) {
      console.error('Error during PWA install:', error);
      return false;
    }
  };

  // Fermer les instructions iOS
  const closeIOSInstructions = () => {
    showIOSInstructions.value = false;
  };

  // Marquer qu'on a refusé d'installer pour ne plus montrer le bandeau
  const dismissInstallPrompt = () => {
    try {
      localStorage.setItem('pwa_install_dismissed', Date.now().toString());
      isDismissed.value = true; // Mise à jour réactive immédiate
    } catch (e) {
      console.error('Error saving dismiss state:', e);
    }
  };

  // Vérifier si on doit montrer le prompt (computed réactif)
  const shouldShowPrompt = computed(() => {
    if (isInstalled.value || !canInstall.value || isDismissed.value) return false;
    
    try {
      const dismissed = localStorage.getItem('pwa_install_dismissed');
      if (dismissed) {
        // Ne pas re-montrer avant 7 jours
        const dismissedAt = parseInt(dismissed);
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        if (Date.now() - dismissedAt < sevenDays) {
          return false;
        }
      }
    } catch (e) {
      console.error('Error checking dismiss state:', e);
    }
    
    return true;
  });

  // Vérifier l'état initial du dismiss
  const checkDismissState = () => {
    try {
      const dismissed = localStorage.getItem('pwa_install_dismissed');
      if (dismissed) {
        const dismissedAt = parseInt(dismissed);
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        if (Date.now() - dismissedAt < sevenDays) {
          isDismissed.value = true;
        }
      }
    } catch (e) {
      console.error('Error checking dismiss state:', e);
    }
  };

  onMounted(() => {
    checkIfInstalled();
    checkIfIOS();
    checkDismissState();

    // Écouter l'événement beforeinstallprompt
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    
    // Écouter quand l'app est installée
    window.addEventListener('appinstalled', handleAppInstalled);
    
    // Écouter les changements de mode d'affichage
    window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
      if (e.matches) {
        isInstalled.value = true;
      }
    });
  });

  onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.removeEventListener('appinstalled', handleAppInstalled);
  });

  return {
    canInstall,
    isInstalled,
    isIOS,
    showIOSInstructions,
    install,
    closeIOSInstructions,
    dismissInstallPrompt,
    shouldShowPrompt
  };
}
