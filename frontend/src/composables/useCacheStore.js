import { ref } from 'vue';
import { useToast } from './useToast';

/**
 * Composable pour gérer le cache localStorage avec stratégie stale-while-revalidate
 * 
 * Fonctionnement:
 * 1. Au premier chargement: fetch API + stockage localStorage
 * 2. Aux chargements suivants: affichage immédiat du cache + fetch en background
 * 3. Si données mises à jour: synchronisation + toast notification
 */
export function useCacheStore() {
  const { showToast } = useToast();
  const CACHE_VERSION = '1.0';
  const MAX_CACHE_SIZE = 50 * 1024 * 1024; // 50MB
  
  /**
   * Calcule la taille utilisée par le localStorage
   */
  const getLocalStorageSize = () => {
    let total = 0;
    for (let key in localStorage) {
      if (localStorage.hasOwnProperty(key)) {
        total += localStorage[key].length + key.length;
      }
    }
    return total;
  };

  /**
   * Vérifie si le localStorage a de l'espace disponible
   */
  const hasStorageSpace = (dataSize) => {
    const currentSize = getLocalStorageSize();
    return (currentSize + dataSize) < MAX_CACHE_SIZE;
  };

  /**
   * Nettoie le localStorage en supprimant les entrées les plus anciennes
   */
  const cleanOldCache = () => {
    const cacheKeys = [];
    
    // Récupérer toutes les clés de cache avec leur timestamp
    for (let key in localStorage) {
      if (key.startsWith('cache_')) {
        try {
          const data = JSON.parse(localStorage[key]);
          if (data.timestamp) {
            cacheKeys.push({ key, timestamp: data.timestamp });
          }
        } catch (e) {
          // Supprimer les entrées corrompues
          localStorage.removeItem(key);
        }
      }
    }

    // Trier par timestamp (plus ancien en premier)
    cacheKeys.sort((a, b) => a.timestamp - b.timestamp);

    // Supprimer jusqu'à ce qu'on ait assez d'espace (20% du cache)
    const targetSize = MAX_CACHE_SIZE * 0.8;
    while (getLocalStorageSize() > targetSize && cacheKeys.length > 0) {
      const oldest = cacheKeys.shift();
      localStorage.removeItem(oldest.key);
    }
  };

  /**
   * Génère une clé de cache unique basée sur l'endpoint et les paramètres
   */
  const generateCacheKey = (endpoint, params = {}) => {
    const sortedParams = Object.keys(params)
      .sort()
      .reduce((acc, key) => {
        acc[key] = params[key];
        return acc;
      }, {});
    
    const paramString = JSON.stringify(sortedParams);
    return `cache_${endpoint}_${btoa(paramString)}`;
  };

  /**
   * Vérifie si le cache localStorage est expiré
   */
  const isCacheExpired = (cachedEntry, ttlMinutes = 30) => {
    if (!cachedEntry || !cachedEntry.timestamp) return true;
    
    const now = Date.now();
    const expirationTime = cachedEntry.timestamp + (ttlMinutes * 60 * 1000);
    
    return now > expirationTime;
  };

  /**
   * Récupère les données du cache localStorage
   */
  const getCachedData = (cacheKey, ttlMinutes = null) => {
    try {
      const cached = localStorage.getItem(cacheKey);
      if (!cached) return null;

      const data = JSON.parse(cached);
      
      // Vérifier la version du cache
      if (data.version !== CACHE_VERSION) {
        localStorage.removeItem(cacheKey);
        return null;
      }

      // Vérifier l'expiration si TTL fourni
      if (ttlMinutes !== null && isCacheExpired(data, ttlMinutes)) {
        localStorage.removeItem(cacheKey);
        return null;
      }

      return data;
    } catch (e) {
      console.error('Erreur lors de la lecture du cache:', e);
      localStorage.removeItem(cacheKey);
      return null;
    }
  };

  /**
   * Stocke les données dans le cache localStorage
   */
  const setCachedData = (cacheKey, data) => {
    try {
      const cacheEntry = {
        version: CACHE_VERSION,
        timestamp: Date.now(),
        data: data
      };

      const serialized = JSON.stringify(cacheEntry);
      const dataSize = serialized.length + cacheKey.length;

      // Vérifier l'espace disponible
      if (!hasStorageSpace(dataSize)) {
        cleanOldCache();
        
        // Vérifier à nouveau après le nettoyage
        if (!hasStorageSpace(dataSize)) {
          console.warn('Pas assez d\'espace dans le localStorage pour le cache');
          return false;
        }
      }

      localStorage.setItem(cacheKey, serialized);
      return true;
    } catch (e) {
      if (e.name === 'QuotaExceededError') {
        console.warn('QuotaExceededError: Nettoyage du cache...');
        cleanOldCache();
        
        // Réessayer après nettoyage
        try {
          localStorage.setItem(cacheKey, JSON.stringify({
            version: CACHE_VERSION,
            timestamp: Date.now(),
            data: data
          }));
          return true;
        } catch (retryError) {
          console.error('Impossible de stocker dans le cache même après nettoyage');
          return false;
        }
      }
      console.error('Erreur lors de l\'écriture du cache:', e);
      return false;
    }
  };

  /**
   * Compare deux objets de données pour détecter les changements
   */
  const hasDataChanged = (oldData, newData) => {
    // Comparaison simple par stringification
    // Pour des comparaisons plus complexes, utiliser une lib comme lodash
    return JSON.stringify(oldData) !== JSON.stringify(newData);
  };

  /**
   * Fonction principale pour récupérer des données avec cache
   * Implémente la stratégie stale-while-revalidate avec TTL localStorage
   * 
   * @param {string} endpoint - L'endpoint de l'API
   * @param {Function} fetchFunction - Fonction qui retourne une Promise des données
   * @param {Object} params - Paramètres de la requête (pour la clé de cache)
   * @param {Object} options - Options supplémentaires
   * @returns {Object} { data, loading, error }
   */
  const fetchWithCache = async (endpoint, fetchFunction, params = {}, options = {}) => {
    const {
      forceRefresh = false,
      onDataUpdate = null,
      showSyncToast = true,
      ttl = 30 // TTL en minutes pour le localStorage (par défaut 30min)
    } = options;

    const cacheKey = generateCacheKey(endpoint, params);
    const data = ref(null);
    const loading = ref(true);
    const error = ref(null);

    try {
      // 1. Charger les données du cache si disponibles (sauf si forceRefresh)
      if (!forceRefresh) {
        const cachedEntry = getCachedData(cacheKey, ttl);
        if (cachedEntry && cachedEntry.data) {
          data.value = cachedEntry.data;
          loading.value = false;
          
          // Callback optionnel pour notifier qu'on a des données en cache
          if (onDataUpdate) {
            onDataUpdate(cachedEntry.data, true); // true = from cache
          }
          
          // Si le cache localStorage est encore valide (pas expiré),
          // ne pas faire de requête du tout - optimisation maximale
          if (!isCacheExpired(cachedEntry, ttl)) {
            return { data, loading, error };
          }
        }
      }

      // 2. Fetch les données fraîches en background
      // (seulement si forceRefresh OU si pas de cache OU si cache expiré)
      const freshData = await fetchFunction();
      
      // 3. Comparer avec le cache
      if (data.value) {
        // On a des données en cache, comparer
        if (hasDataChanged(data.value, freshData)) {
          // Les données ont changé, mettre à jour
          data.value = freshData;
          setCachedData(cacheKey, freshData);
          
          if (showSyncToast) {
            showToast('Données synchronisées', 'success');
          }
          
          // Callback pour notifier de la mise à jour
          if (onDataUpdate) {
            onDataUpdate(freshData, false); // false = from API
          }
        } else {
          // Les données sont identiques, juste rafraîchir le timestamp
          setCachedData(cacheKey, freshData);
        }
      } else {
        // Pas de cache, c'est le premier chargement
        data.value = freshData;
        setCachedData(cacheKey, freshData);
        
        if (onDataUpdate) {
          onDataUpdate(freshData, false);
        }
      }

      loading.value = false;
      
    } catch (err) {
      error.value = err;
      loading.value = false;
      console.error('Erreur lors du fetch avec cache:', err);
      
      // Si on a des données en cache et que le fetch échoue, garder le cache
      if (!data.value) {
        throw err;
      }
    }

    return { data, loading, error };
  };

  /**
   * Vide tout le cache localStorage
   */
  const clearAllCache = () => {
    const keysToRemove = [];
    
    for (let key in localStorage) {
      if (key.startsWith('cache_')) {
        keysToRemove.push(key);
      }
    }

    keysToRemove.forEach(key => localStorage.removeItem(key));
    
    return keysToRemove.length;
  };

  /**
   * Vide le cache pour un endpoint spécifique
   */
  const clearCacheForEndpoint = (endpoint) => {
    const keysToRemove = [];
    
    for (let key in localStorage) {
      if (key.startsWith(`cache_${endpoint}_`)) {
        keysToRemove.push(key);
      }
    }

    keysToRemove.forEach(key => localStorage.removeItem(key));
    
    return keysToRemove.length;
  };

  /**
   * Obtient des statistiques sur le cache
   */
  const getCacheStats = () => {
    let cacheCount = 0;
    let totalSize = 0;

    for (let key in localStorage) {
      if (key.startsWith('cache_')) {
        cacheCount++;
        totalSize += localStorage[key].length + key.length;
      }
    }

    return {
      count: cacheCount,
      size: totalSize,
      sizeFormatted: `${(totalSize / 1024).toFixed(2)} KB`,
      maxSize: MAX_CACHE_SIZE,
      maxSizeFormatted: `${(MAX_CACHE_SIZE / 1024 / 1024).toFixed(2)} MB`,
      usagePercent: ((totalSize / MAX_CACHE_SIZE) * 100).toFixed(2)
    };
  };

  return {
    fetchWithCache,
    clearAllCache,
    clearCacheForEndpoint,
    getCacheStats,
    generateCacheKey,
    getCachedData,
    setCachedData,
    isCacheExpired
  };
}
