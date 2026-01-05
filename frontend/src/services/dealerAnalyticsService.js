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
   * Récupérer uniquement les KPI
   * @param {Object} params - Paramètres de période
   * @returns {Promise} KPI avec comparaisons
   */
  async getKpi(params = {}) {
    const response = await api.get('/dealer-analytics/kpi', { params });
    return response.data;
  }

  /**
   * Récupérer uniquement l'évolution
   * @param {Object} params - Paramètres de période
   * @returns {Promise} Données d'évolution
   */
  async getEvolution(params = {}) {
    const response = await api.get('/dealer-analytics/evolution', { params });
    return response.data;
  }

  /**
   * Récupérer uniquement les top PDV
   * @param {Object} params - Paramètres de période
   * @returns {Promise} Top PDV par commissions
   */
  async getTopPdv(params = {}) {
    const response = await api.get('/dealer-analytics/top-pdv', { params });
    return response.data;
  }

  /**
   * Récupérer uniquement les stats GIVE
   * @param {Object} params - Paramètres de période
   * @returns {Promise} Statistiques GIVE network
   */
  async getGiveStats(params = {}) {
    const response = await api.get('/dealer-analytics/give-stats', { params });
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
