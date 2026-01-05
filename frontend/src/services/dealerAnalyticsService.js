import api from './api';

class DealerAnalyticsService {
  /**
   * Récupérer les analytics pour le dealer connecté
   * @param {Object} params - Paramètres de la requête (period: 'day'|'week'|'month'|'quarter')
   * @returns {Promise} Données analytics du dealer
   */
  async getAnalytics(params = {}) {
    const response = await api.get('/dealer-analytics/analytics', { params });
    return response.data;
  }

  /**
   * Récupérer les revenus mensuels pour l'année sélectionnée
   * @param {number} year - Année (ex: 2025)
   * @returns {Promise} Données mensuelles (commissions, retenues)
   */
  async getMonthlyRevenue(year) {
    const response = await api.get('/dealer-analytics/monthly-revenue', {
      params: { year }
    });
    return response.data;
  }
}

export default new DealerAnalyticsService();
