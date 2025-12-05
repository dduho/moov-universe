import api from './api';

export default {
  async getAll(params = {}) {
    const response = await api.get('/point-of-sales', { params });
    return response.data;
  },

  async getForMap(params = {}) {
    const response = await api.get('/point-of-sales/for-map', { params });
    return response.data;
  },

  async getById(id) {
    const response = await api.get(`/point-of-sales/${id}`);
    return response.data;
  },

  async create(data) {
    const response = await api.post('/point-of-sales', data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(`/point-of-sales/${id}`, data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(`/point-of-sales/${id}`);
    return response.data;
  },

  async validate(id) {
    const response = await api.post(`/point-of-sales/${id}/validate`);
    return response.data;
  },

  async reject(id, reason) {
    const response = await api.post(`/point-of-sales/${id}/reject`, { rejection_reason: reason });
    return response.data;
  },

  async checkProximity(latitude, longitude, excludeId = null) {
    const response = await api.post('/point-of-sales/check-proximity', {
      latitude,
      longitude,
      exclude_id: excludeId,
    });
    return response.data;
  },

  async checkUniqueness(field, value) {
    const response = await api.post('/point-of-sales/check-uniqueness', {
      field,
      value,
    });
    return response.data;
  },

  async getGpsStats() {
    const response = await api.get('/point-of-sales/gps-stats');
    return response.data;
  },

  async clearDuplicateCoordinates() {
    const response = await api.post('/point-of-sales/clear-duplicate-coordinates');
    return response.data;
  },

  async getWithoutCoordinates(params = {}) {
    const response = await api.get('/point-of-sales/without-coordinates', { params });
    return response.data;
  },

  async getWithoutCoordinatesStats() {
    const response = await api.get('/point-of-sales/without-coordinates-stats');
    return response.data;
  },
};
