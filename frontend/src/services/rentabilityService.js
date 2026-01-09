import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class RentabilityService {
  /**
   * Analyse de rentabilité avec cache
   * @param {Object} filters - Filtres de recherche
   * @param {string} filters.period - Période (7d, 30d, 90d, custom)
   * @param {string} filters.groupBy - Groupement (pdv, dealer, region)
   * @param {string} filters.sortBy - Tri (roi, margin, revenue, ca)
   * @param {number} filters.limit - Limite de résultats (0 = tous)
   * @param {string} filters.startDate - Date de début (format YYYY-MM-DD) pour period=custom
   * @param {string} filters.endDate - Date de fin (format YYYY-MM-DD) pour period=custom
   * @returns {Promise<Object>} Données de rentabilité
   */
  async analyze(filters = {}) {
    try {
      const token = localStorage.getItem('token');
      
      if (!token) {
        throw new Error('Non authentifié');
      }

      // Définir la période réelle des données disponibles
      const DATA_START = '2025-12-11';
      const DATA_END = '2026-01-06';
      
      // Calculer les dates selon la période
      let startDate, endDate;
      
      if (filters.period === 'custom' && filters.startDate && filters.endDate) {
        startDate = filters.startDate;
        endDate = filters.endDate;
      } else if (filters.period) {
        // Pour les périodes prédéfinies, calculer à partir de la fin des données
        const dataEndDate = new Date(DATA_END);
        
        switch (filters.period) {
          case '7d':
            const start7d = new Date(dataEndDate);
            start7d.setDate(start7d.getDate() - 7);
            startDate = start7d.toISOString().split('T')[0];
            endDate = DATA_END;
            break;
          case '30d':
            const start30d = new Date(dataEndDate);
            start30d.setDate(start30d.getDate() - 30);
            startDate = start30d.toISOString().split('T')[0];
            endDate = DATA_END;
            break;
          case '90d':
            const start90d = new Date(dataEndDate);
            start90d.setDate(start90d.getDate() - 90);
            startDate = start90d.toISOString().split('T')[0];
            endDate = DATA_END;
            break;
          case 'all':
          default:
            // Utiliser toute la période des données
            startDate = DATA_START;
            endDate = DATA_END;
            break;
        }
      } else {
        // Par défaut, toute la période
        startDate = DATA_START;
        endDate = DATA_END;
      }

      const params = {
        scope: 'global',
        start_date: startDate,
        end_date: endDate,
        group_by: filters.groupBy || 'pdv',
        sort_by: filters.sortBy || 'roi',
        limit: filters.limit || 20
      };

      const response = await axios.get(`${API_URL}/rentability/analyze`, {
        params,
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.data.success) {
        return {
          success: true,
          data: response.data, // Retourner toute la structure de l'API
          cached: response.data.parameters?.cached || false
        };
      } else {
        return {
          success: false,
          error: response.data.message || 'Erreur lors de l\'analyse'
        };
      }
    } catch (error) {
      console.error('Erreur RentabilityService.analyze:', error);
      
      if (error.response?.status === 401) {
        return {
          success: false,
          error: 'Session expirée, veuillez vous reconnecter'
        };
      }
      
      if (error.response?.status === 403) {
        return {
          success: false,
          error: 'Accès non autorisé'
        };
      }
      
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Erreur de connexion au serveur'
      };
    }
  }

  /**
   * Vider le cache de rentabilité (admin uniquement)
   * @returns {Promise<Object>} Résultat de l'opération
   */
  async clearCache() {
    try {
      const token = localStorage.getItem('token');
      
      if (!token) {
        throw new Error('Non authentifié');
      }

      const response = await axios.post(`${API_URL}/rentability/clear-cache`, {}, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      return {
        success: true,
        message: response.data.message || 'Cache vidé avec succès'
      };
    } catch (error) {
      console.error('Erreur RentabilityService.clearCache:', error);
      
      return {
        success: false,
        error: error.response?.data?.message || error.message || 'Erreur lors du vidage du cache'
      };
    }
  }

  /**
   * Exporter les données de rentabilité en Excel
   * @param {Object} filters - Mêmes filtres que analyze()
   * @returns {Promise<Blob>} Fichier Excel
   */
  async export(filters = {}) {
    try {
      const token = localStorage.getItem('token');
      
      if (!token) {
        throw new Error('Non authentifié');
      }

      // Utiliser les mêmes paramètres que analyze()
      let startDate, endDate;
      endDate = new Date().toISOString().split('T')[0];
      
      if (filters.period === 'custom') {
        startDate = filters.startDate;
        endDate = filters.endDate;
      } else {
        const days = parseInt(filters.period) || 30;
        const start = new Date();
        start.setDate(start.getDate() - days);
        startDate = start.toISOString().split('T')[0];
      }

      const params = {
        scope: 'global',
        start_date: startDate,
        end_date: endDate,
        group_by: filters.groupBy || 'pdv',
        sort_by: filters.sortBy || 'roi',
        limit: 0, // Exporter tous les résultats
        export: 'excel'
      };

      const response = await axios.get(`${API_URL}/rentability/analyze`, {
        params,
        headers: {
          'Authorization': `Bearer ${token}`,
        },
        responseType: 'blob'
      });

      return response.data;
    } catch (error) {
      console.error('Erreur RentabilityService.export:', error);
      throw error;
    }
  }
}

export default new RentabilityService();
