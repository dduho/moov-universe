import apiClient from './api';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export default {
  /**
   * Detect fraud patterns
   * @param {Object} params - { scope, entity_id, start_date, end_date }
   */
  async detectFraud(params = {}) {
    const response = await apiClient.get('/fraud-detection', { params });
    return response.data;
  }
};
