import { ref, computed } from 'vue';

// Cache de la dernière position connue (24h)
const CACHE_KEY = 'last_known_position';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 heures

/**
 * Composable pour une gestion améliorée de la géolocalisation
 * - Timeout de 30s pour GPS froid
 * - Retry automatique avec backoff exponentiel
 * - Cache de dernière position (24h)
 * - Mode position approximative en fallback
 * - Indicateur visuel de précision GPS
 */
export function useGeolocation() {
  const loading = ref(false);
  const error = ref(null);
  const position = ref(null);
  const accuracy = ref(null);
  const isApproximate = ref(false);
  const retryCount = ref(0);
  const maxRetries = 3;

  // Indicateur de qualité de précision
  const precisionQuality = computed(() => {
    if (!accuracy.value) return null;
    if (accuracy.value < 10) return 'excellent'; // < 10m
    if (accuracy.value < 30) return 'good';       // 10-30m
    if (accuracy.value < 50) return 'fair';       // 30-50m
    return 'poor';                                 // > 50m
  });

  const precisionLabel = computed(() => {
    if (isApproximate.value) return 'Position approximative';
    switch (precisionQuality.value) {
      case 'excellent': return 'Précision excellente';
      case 'good': return 'Bonne précision';
      case 'fair': return 'Précision moyenne';
      case 'poor': return 'Précision faible';
      default: return '';
    }
  });

  const precisionColor = computed(() => {
    if (isApproximate.value) return 'text-yellow-600 bg-yellow-100';
    switch (precisionQuality.value) {
      case 'excellent': return 'text-green-600 bg-green-100';
      case 'good': return 'text-green-500 bg-green-50';
      case 'fair': return 'text-yellow-600 bg-yellow-100';
      case 'poor': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  });

  // Récupérer la position en cache
  const getCachedPosition = () => {
    try {
      const cached = localStorage.getItem(CACHE_KEY);
      if (cached) {
        const { data, timestamp } = JSON.parse(cached);
        if (Date.now() - timestamp < CACHE_DURATION) {
          return data;
        }
        localStorage.removeItem(CACHE_KEY);
      }
    } catch (e) {
      console.error('Error reading cached position:', e);
    }
    return null;
  };

  // Sauvegarder la position en cache
  const cachePosition = (pos) => {
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify({
        data: pos,
        timestamp: Date.now()
      }));
    } catch (e) {
      console.error('Error caching position:', e);
    }
  };

  // Vibration/son au succès
  const notifySuccess = () => {
    if ('vibrate' in navigator) {
      navigator.vibrate(100);
    }
  };

  // Calculer le délai de backoff exponentiel
  const getBackoffDelay = (attempt) => {
    return Math.min(1000 * Math.pow(2, attempt), 10000);
  };

  // Obtenir la position GPS avec options améliorées
  const getHighAccuracyPosition = () => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('La géolocalisation n\'est pas supportée'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => resolve(pos),
        (err) => reject(err),
        {
          enableHighAccuracy: true,
          timeout: 30000, // 30s pour GPS froid
          maximumAge: 0
        }
      );
    });
  };

  // Fallback: Network-based location (moins précise mais plus rapide)
  const getNetworkBasedPosition = () => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('La géolocalisation n\'est pas supportée'));
        return;
      }

      navigator.geolocation.getCurrentPosition(
        (pos) => resolve(pos),
        (err) => reject(err),
        {
          enableHighAccuracy: false,
          timeout: 15000,
          maximumAge: 60000 // Accepter une position de moins d'1 minute
        }
      );
    });
  };

  // Fonction principale avec retry et fallbacks
  const getCurrentPosition = async () => {
    loading.value = true;
    error.value = null;
    isApproximate.value = false;
    retryCount.value = 0;

    // Essayer d'abord le GPS haute précision avec retry
    while (retryCount.value < maxRetries) {
      try {
        const pos = await getHighAccuracyPosition();
        position.value = {
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude
        };
        accuracy.value = pos.coords.accuracy;
        cachePosition(position.value);
        notifySuccess();
        loading.value = false;
        return position.value;
      } catch (err) {
        retryCount.value++;
        if (retryCount.value < maxRetries) {
          await new Promise(r => setTimeout(r, getBackoffDelay(retryCount.value)));
        }
      }
    }

    // Fallback 1: Network-based location
    try {
      const pos = await getNetworkBasedPosition();
      position.value = {
        latitude: pos.coords.latitude,
        longitude: pos.coords.longitude
      };
      accuracy.value = pos.coords.accuracy;
      isApproximate.value = true;
      cachePosition(position.value);
      notifySuccess();
      loading.value = false;
      return position.value;
    } catch (err) {
      // Fallback 2: Position en cache
      const cached = getCachedPosition();
      if (cached) {
        position.value = cached;
        accuracy.value = null;
        isApproximate.value = true;
        loading.value = false;
        return position.value;
      }

      // Échec total
      error.value = 'Impossible de récupérer votre position. Veuillez vérifier vos paramètres de localisation.';
      loading.value = false;
      throw new Error(error.value);
    }
  };

  // Watcher de position en temps réel
  let watchId = null;
  
  const watchPosition = (callback) => {
    if (!navigator.geolocation) {
      error.value = 'La géolocalisation n\'est pas supportée';
      return;
    }

    watchId = navigator.geolocation.watchPosition(
      (pos) => {
        position.value = {
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude
        };
        accuracy.value = pos.coords.accuracy;
        cachePosition(position.value);
        if (callback) callback(position.value);
      },
      (err) => {
        error.value = err.message;
      },
      {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 5000
      }
    );
  };

  const stopWatching = () => {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }
  };

  return {
    loading,
    error,
    position,
    accuracy,
    isApproximate,
    precisionQuality,
    precisionLabel,
    precisionColor,
    retryCount,
    getCurrentPosition,
    watchPosition,
    stopWatching,
    getCachedPosition
  };
}
