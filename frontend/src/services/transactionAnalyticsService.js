import api from './api';

export default {
  /**
   * Get transaction analytics
   */
  async getAnalytics(params = {}) {
    return api.get('/analytics/transactions', { params });
  },

  /**
   * Get AI insights and recommendations
   */
  async getInsights(params = {}) {
    return api.get('/analytics/insights', { params });
  },

  /**
   * Export PDV list for a specific insight type (inactivity or anomaly)
   */
  async exportInsightPdv(type) {
    return api.get('/analytics/insights/export', { params: { type } });
  },
};
