import api from './api';

export default {
  async getRegions() {
    const response = await api.get('/geography/regions');
    return response.data;
  },

  async getPrefectures(region) {
    const response = await api.get('/geography/prefectures', { params: { region } });
    return response.data;
  },

  async getCommunes(prefecture) {
    const response = await api.get('/geography/communes', { params: { prefecture } });
    return response.data;
  },

  async getCantons(commune) {
    const response = await api.get('/geography/cantons', { params: { commune } });
    return response.data;
  },

  async getHierarchy() {
    const response = await api.get('/geography/hierarchy');
    return response.data;
  },
};
