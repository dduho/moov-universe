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
};
