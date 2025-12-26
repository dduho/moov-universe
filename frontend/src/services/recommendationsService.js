import apiClient from './api';

class RecommendationsService {
  /**
   * Get AI recommendations for PDV and Dealers
   * @param {Object} params - Query parameters
   * @param {string} params.scope - Scope: 'global', 'region', 'dealer', 'pdv'
   * @param {number} params.entity_id - Entity ID for scoped requests
   * @param {number} params.limit - Maximum number of recommendations per category
   * @returns {Promise} API response with recommendations
   */
  async getRecommendations({ scope = 'global', entity_id = null, limit = 10 } = {}) {
    try {
      const params = { scope, limit };
      if (entity_id) {
        params.entity_id = entity_id;
      }

      const response = await apiClient.get('/recommendations', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching recommendations:', error);
      throw error;
    }
  }
}

export default new RecommendationsService();
