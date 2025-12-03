import api from './api';

export default {
  async getDashboard() {
    const response = await api.get('/statistics/dashboard');
    return response.data;
  },

  async getByRegion() {
    const response = await api.get('/statistics/by-region');
    return response.data;
  },

  async getByOrganization() {
    const response = await api.get('/statistics/by-organization');
    return response.data;
  },

  async getTimeline(days = 30) {
    const response = await api.get('/statistics/timeline', { params: { days } });
    return response.data;
  },
};
