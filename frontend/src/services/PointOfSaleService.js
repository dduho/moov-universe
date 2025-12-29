import api from './api';
import { offlineDB } from '../utils/offlineDB';

export default {
  async getAll(params = {}) {
    try {
      // Si en ligne, fetch depuis l'API
      if (navigator.onLine) {
        const response = await api.get('/point-of-sales', { params });
        const pdvList = response.data.data || response.data;
        
        // Sauvegarder dans IndexedDB pour usage offline
        if (Array.isArray(pdvList)) {
          await offlineDB.savePDVList(pdvList);
          console.log('[PDV Service] Liste PDV sauvegardée pour mode offline');
        }
        
        return response.data;
      }
      
      // Si hors ligne, utiliser les données locales
      console.log('[PDV Service] Mode hors ligne - Chargement depuis IndexedDB');
      const cachedPDV = await offlineDB.getPDVList();
      return { data: cachedPDV };
    } catch (error) {
      console.error('[PDV Service] Erreur, tentative cache...', error);
      
      // En cas d'erreur, essayer le cache local
      const cached = await offlineDB.getPDVList();
      if (cached && cached.length > 0) {
        console.log('[PDV Service] Données récupérées du cache');
        return { data: cached };
      }
      
      throw error;
    }
  },

  async getForMap(params = {}) {
    try {
      if (navigator.onLine) {
        const response = await api.get('/point-of-sales/for-map', { params });
        
        // Mettre en cache les données de la carte
        const mapData = response.data;
        if (mapData) {
          await offlineDB.cacheData('map-pdv-list', mapData, 60);
        }
        
        return response.data;
      }
      
      // Mode offline : récupérer depuis le cache
      const cached = await offlineDB.getCachedData('map-pdv-list');
      if (cached) {
        console.log('[PDV Service] Données carte depuis cache');
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
};
