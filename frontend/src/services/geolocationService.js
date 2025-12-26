import apiClient from './api';

export default {
  /**
   * Get PDV geolocation data with CA stats
   * @param {Object} params - { start_date, end_date, region, status, min_ca, max_ca }
   */
  async getPdvGeoData(params = {}) {
    const response = await apiClient.get('/geolocation/pdv', { params });
    return response.data;
  },

  /**
   * Get potential zones (high density, low CA)
   * @param {Object} params - { start_date, end_date }
   */
  async getPotentialZones(params = {}) {
    const response = await apiClient.get('/geolocation/potential-zones', { params });
    return response.data;
  }
};
