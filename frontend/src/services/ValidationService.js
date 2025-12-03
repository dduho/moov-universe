import api from './api';

const ValidationService = {
  /**
   * Get all pending point of sales awaiting validation
   */
  async getPending() {
    try {
      const response = await api.get('/point-of-sales', {
        params: { status: 'pending' }
      });
      return response.data.data || response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Erreur lors du chargement des PDV en attente');
    }
  },

  /**
   * Get validation statistics
   */
  async getStats() {
    try {
      const response = await api.get('/statistics/validation');
      return response.data;
    } catch (error) {
      console.error('Error fetching validation stats:', error);
      // Return default stats if endpoint doesn't exist yet
      return {
        validatedToday: 0,
        rejectedToday: 0,
        averageTime: '0h'
      };
    }
  },

  /**
   * Validate a point of sale
   */
  async validate(posId) {
    try {
      const response = await api.post(`/point-of-sales/${posId}/validate`);
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Erreur lors de la validation du PDV');
    }
  },

  /**
   * Reject a point of sale with a reason
   */
  async reject(posId, reason) {
    try {
      const response = await api.post(`/point-of-sales/${posId}/reject`, {
        rejection_reason: reason
      });
      return response.data;
    } catch (error) {
      throw new Error(error.response?.data?.message || 'Erreur lors du rejet du PDV');
    }
  },

  /**
   * Check proximity to existing point of sales
   */
  async checkProximity(latitude, longitude) {
    try {
      const response = await api.post('/point-of-sales/check-proximity', {
        latitude,
        longitude
      });
      return response.data;
    } catch (error) {
      console.error('Error checking proximity:', error);
      return { hasNearby: false, nearby: [] };
    }
  }
};

export default ValidationService;
