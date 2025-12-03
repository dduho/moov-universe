import api from './api';

class OrganizationService {
  async getAll(params = {}) {
    const response = await api.get('/organizations', { params });
    return response.data;
  }

  async getById(id) {
    const response = await api.get(`/organizations/${id}`);
    return response.data;
  }

  async create(organizationData) {
    const response = await api.post('/organizations', organizationData);
    return response.data;
  }

  async update(id, organizationData) {
    const response = await api.put(`/organizations/${id}`, organizationData);
    return response.data;
  }

  async delete(id) {
    const response = await api.delete(`/organizations/${id}`);
    return response.data;
  }

  async getStats(id) {
    const response = await api.get(`/organizations/${id}/stats`);
    return response.data;
  }
}

export default new OrganizationService();
