import api from './api';

export default {
  /**
   * Get transaction analytics
   */
  async getAnalytics(params = {}) {
    return api.get('/analytics/transactions', { params });
  },
};
