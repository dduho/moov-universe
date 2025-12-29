// Service d'exemple montrant comment utiliser le mode offline
import api from './api';
import { offlineDB } from '../utils/offlineDB';

export default {
  /**
   * Récupérer la liste des PDV avec support offline
   */
  async getPDVList(useCache = true) {
    try {
      // Si en ligne, fetch depuis l'API
      if (navigator.onLine) {
        const response = await api.get('/pdv');
        const pdvList = response.data.data || response.data;
        
        // Sauvegarder dans IndexedDB pour usage offline
        await offlineDB.savePDVList(pdvList);
        
        return pdvList;
      }
      
      // Si hors ligne, utiliser les données locales
      console.log('[PDV Service] Mode hors ligne - Chargement depuis IndexedDB');
      return await offlineDB.getPDVList();
    } catch (error) {
      console.error('[PDV Service] Erreur, tentative cache...', error);
      
      // En cas d'erreur, essayer le cache local
      if (useCache) {
        const cached = await offlineDB.getPDVList();
        if (cached && cached.length > 0) {
          console.log('[PDV Service] Données récupérées du cache');
          return cached;
        }
      }
      
      throw error;
    }
  },

  /**
   * Rechercher des PDV avec support offline
   */
  async searchPDV(query) {
    try {
      if (navigator.onLine) {
        const response = await api.get('/pdv/search', { params: { q: query } });
        return response.data;
      }
      
      // Recherche offline dans IndexedDB
      return await offlineDB.searchPDV(query);
    } catch (error) {
      // Fallback sur recherche locale
      return await offlineDB.searchPDV(query);
    }
  },

  /**
   * Créer un PDV avec gestion offline
   */
  async createPDV(pdvData) {
    try {
      if (!navigator.onLine) {
        // Sauvegarder pour synchronisation ultérieure
        await offlineDB.addPendingAction({
          url: `${import.meta.env.VITE_API_BASE_URL}/api/pdv`,
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify(pdvData)
        });
        
        return {
          success: true,
          offline: true,
          message: 'PDV enregistré. Il sera créé lors de la prochaine synchronisation.'
        };
      }
      
      const response = await api.post('/pdv', pdvData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Mettre à jour un PDV avec gestion offline
   */
  async updatePDV(id, pdvData) {
    try {
      if (!navigator.onLine) {
        await offlineDB.addPendingAction({
          url: `${import.meta.env.VITE_API_BASE_URL}/api/pdv/${id}`,
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          },
          body: JSON.stringify(pdvData)
        });
        
        return {
          success: true,
          offline: true,
          message: 'Modification enregistrée. Elle sera synchronisée au retour de la connexion.'
        };
      }
      
      const response = await api.put(`/pdv/${id}`, pdvData);
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  /**
   * Obtenir les statistiques avec cache intelligent
   */
  async getStatistics() {
    const cacheKey = 'pdv-statistics';
    
    try {
      if (navigator.onLine) {
        const response = await api.get('/pdv/statistics');
        const stats = response.data;
        
        // Mettre en cache pour 30 minutes
        await offlineDB.cacheData(cacheKey, stats, 30);
        
        return stats;
      }
      
      // Utiliser les données en cache
      const cached = await offlineDB.getCachedData(cacheKey);
      if (cached) {
        console.log('[PDV Service] Stats depuis le cache');
        return { ...cached, fromCache: true };
      }
      
      throw new Error('Statistiques non disponibles hors ligne');
    } catch (error) {
      // Essayer le cache même si erreur
      const cached = await offlineDB.getCachedData(cacheKey);
      if (cached) {
        return { ...cached, fromCache: true };
      }
      throw error;
    }
  }
};
