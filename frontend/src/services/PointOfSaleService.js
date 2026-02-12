import api from './api';
import { offlineDB } from '../utils/offlineDB';
import cacheService from './cacheService';

const buildCacheKey = (params = {}) => {
  const sorted = Object.keys(params)
    .sort()
    .reduce((acc, key) => {
      acc[key] = params[key];
      return acc;
    }, {});
  return `pdv-list:${JSON.stringify(sorted)}`;
};

export default {
  /**
   * Get all PDVs for export (single request, no pagination)
   * Uses dedicated endpoint to avoid rate limiting
   */
  async getAllForExport(params = {}) {
    try {
      console.log('[PDV Service] Fetching all PDVs for export...');
      const response = await api.get('/point-of-sales/export-all', { params });
      console.log(`[PDV Service] Export: ${response.data.total} PDVs retrieved`);
      return response.data;
    } catch (error) {
      console.error('[PDV Service] Error fetching export data:', error);
      
      // Fallback: try to get from IndexedDB
      try {
        const cachedList = await offlineDB.getAllPDVs();
        if (cachedList && cachedList.length > 0) {
          console.log('[PDV Service] Using cached data for export');
          return { data: cachedList, total: cachedList.length };
        }
      } catch (dbError) {
        console.error('[PDV Service] IndexedDB fallback failed:', dbError);
      }
      
      throw error;
    }
  },

  async getAll(params = {}) {
    try {
      const cacheKey = buildCacheKey(params);

      // Try cache first when offline or to shortcut repeat fetches
      if (!navigator.onLine) {
        const cached = await offlineDB.getCachedData(cacheKey);
        if (cached) {
          console.log('[PDV Service] Offline cache hit');
          return cached;
        }
      }

      // Si en ligne, fetch depuis l'API
      if (navigator.onLine) {
        const response = await api.get('/point-of-sales', { params });
        const payload = response.data;
        const pdvList = payload.data || payload;
        
        // Sauvegarder dans IndexedDB pour usage offline
        if (Array.isArray(pdvList)) {
          await offlineDB.savePDVList(pdvList);
          console.log('[PDV Service] Liste PDV sauvegardée pour mode offline');
        }

        // Cache la réponse paginée pour réutilisation rapide (5 min)
        try {
          await offlineDB.cacheData(cacheKey, payload, 5);
        } catch (cacheError) {
          console.warn('[PDV Service] Cache write failed', cacheError);
        }
        
        return payload;
      }
      
      // Si hors ligne, utiliser les données locales
      console.log('[PDV Service] Mode hors ligne - Chargement depuis IndexedDB');
      const cached = await offlineDB.getCachedData(cacheKey);
      if (cached) {
        return cached;
      }

      const cachedPDV = await offlineDB.getPDVList();
      return { data: cachedPDV };
    } catch (error) {
      console.warn('[PDV Service] Erreur, tentative cache...', error.message || error);
      
      // En cas d'erreur, essayer le cache local
      try {
        const cacheKey = buildCacheKey(params);
        const cachedResponse = await offlineDB.getCachedData(cacheKey);
        if (cachedResponse) {
          console.log('[PDV Service] ✅ Données récupérées du cache');
          return cachedResponse;
        }
      } catch (cacheErr) {
        console.warn('[PDV Service] Cache fallback échoué:', cacheErr.message);
      }

      try {
        const cachedList = await offlineDB.getPDVList();
        if (cachedList && cachedList.length > 0) {
          console.log('[PDV Service] ✅ Données récupérées du cache (liste brute)');
          return { data: cachedList };
        }
      } catch (listErr) {
        console.warn('[PDV Service] Liste cache échouée:', listErr.message);
      }
      
      // Si tout échoue, retourner un tableau vide au lieu de throw
      console.error('[PDV Service] ❌ Aucune donnée disponible');
      return { data: [], total: 0, message: 'Données non disponibles' };
    }
  },

  async getForMap(params = {}) {
    try {
      // Créer une clé de cache unique basée sur les paramètres
      const cacheKey = 'map-pdv-' + buildCacheKey(params);
      
      // Vérifier le cache d'abord
      const cachedData = cacheService.get(cacheKey);
      if (cachedData) {
        console.log('[PDV Service] Données carte depuis cache localStorage');
        return cachedData;
      }
      
      // Si pas en cache, récupérer depuis l'API
      if (navigator.onLine) {
        const response = await api.get('/point-of-sales/for-map', { params });
        
        // Mettre en cache les données (30 minutes, max 3MB)
        const mapData = response.data;
        if (mapData) {
          const cached = cacheService.set(cacheKey, mapData, 30, 3);
          if (!cached) {
            console.warn('[PDV Service] Données trop volumineuses pour le cache localStorage');
          }
          // Garder aussi l'ancien cache offlineDB pour la compatibilité
          await offlineDB.cacheData('map-pdv-list', mapData, 60);
        }
        
        return response.data;
      }
      
      // Mode offline : récupérer depuis le cache offlineDB
      const cached = await offlineDB.getCachedData('map-pdv-list');
      if (cached) {
        console.log('[PDV Service] Données carte depuis cache offlineDB');
        return cached;
      }
      
      // Fallback sur la liste complète
      const allPDV = await offlineDB.getPDVList();
      return { data: allPDV.filter(pdv => pdv.latitude && pdv.longitude) };
    } catch (error) {
      // Fallback cache
      const cached = await offlineDB.getCachedData('map-pdv-list');
      if (cached) return cached;
      
      const allPDV = await offlineDB.getPDVList();
      return { data: allPDV.filter(pdv => pdv.latitude && pdv.longitude) };
    }
  },

  async getById(id) {
    const response = await api.get(`/point-of-sales/${id}`);
    return response.data;
  },

  async create(data) {
    const response = await api.post('/point-of-sales', data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(`/point-of-sales/${id}`, data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(`/point-of-sales/${id}`);
    return response.data;
  },

  async validate(id) {
    const response = await api.post(`/point-of-sales/${id}/validate`);
    return response.data;
  },

  async reject(id, reason) {
    const response = await api.post(`/point-of-sales/${id}/reject`, { rejection_reason: reason });
    return response.data;
  },

  async checkProximity(latitude, longitude, excludeId = null) {
    const response = await api.post('/point-of-sales/check-proximity', {
      latitude,
      longitude,
      exclude_id: excludeId,
    });
    return response.data;
  },

  async checkUniqueness(field, value, excludeId = null) {
    const response = await api.post('/point-of-sales/check-uniqueness', {
      field,
      value,
      exclude_id: excludeId,
    });
    return response.data;
  },

  async getGpsStats() {
    const response = await api.get('/point-of-sales/gps-stats');
    return response.data;
  },

  async getProximityAlerts() {
    const response = await api.get('/point-of-sales/proximity-alerts');
    return response.data;
  },

  async clearDuplicateCoordinates() {
    const response = await api.post('/point-of-sales/clear-duplicate-coordinates');
    return response.data;
  },

  async getWithoutCoordinates(params = {}) {
    const response = await api.get('/point-of-sales/without-coordinates', { params });
    return response.data;
  },

  async getWithoutCoordinatesStats() {
    const response = await api.get('/point-of-sales/without-coordinates-stats');
    return response.data;
  },

  async lock(id) {
    const response = await api.post(`/point-of-sales/${id}/lock`);
    return response.data;
  },

  async unlock(id) {
    const response = await api.post(`/point-of-sales/${id}/unlock`);
    return response.data;
  },
};
