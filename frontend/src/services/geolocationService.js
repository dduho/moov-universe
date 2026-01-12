import apiClient from './api';
import cacheService from './cacheService';

const buildCacheKey = (params = {}) => {
  const sorted = Object.keys(params)
    .sort()
    .reduce((acc, key) => {
      acc[key] = params[key];
      return acc;
    }, {});
  return JSON.stringify(sorted);
};

export default {
  /**
   * Get PDV geolocation data with CA stats
   * @param {Object} params - { start_date, end_date, region, status, min_ca, max_ca }
   */
  async getPdvGeoData(params = {}) {
    // Créer une clé de cache unique basée sur les paramètres
    const cacheKey = 'geo-pdv-' + buildCacheKey(params);
    
    // Vérifier le cache d'abord
    const cachedData = cacheService.get(cacheKey);
    if (cachedData) {
      console.log('[Geo Service] Données géolocalisation depuis cache localStorage');
      return cachedData;
    }
    
    // Si pas en cache, récupérer depuis l'API
    const response = await apiClient.get('/geolocation/pdv', { params });
    
    // Mettre en cache les données (60 minutes, max 3MB)
    if (response.data) {
      const cached = cacheService.set(cacheKey, response.data, 60, 3);
      if (!cached) {
        console.warn('[Geo Service] Données trop volumineuses pour le cache localStorage');
      }
    }
    
    return response.data;
  },

  /**
   * Get potential zones (high density, low CA)
   * @param {Object} params - { start_date, end_date }
   */
  async getPotentialZones(params = {}) {
    const response = await apiClient.get('/geolocation/potential-zones', { params });
    return response.data;
  }
};
