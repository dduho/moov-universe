import api from './api';

export default {
  /**
   * Get all users with optional filters
   */
  async getAll(params = {}) {
    const response = await api.get('/users', { params });
    return response.data;
  },

  /**
   * Get a specific user by ID
   */
  async getById(id) {
    const response = await api.get(`/users/${id}`);
    return response.data;
  },

  /**
   * Create a new user
   */
  async create(userData) {
    const response = await api.post('/users', userData);
    return response.data;
  },

  /**
   * Update an existing user
   */
  async update(id, userData) {
    const response = await api.put(`/users/${id}`, userData);
    return response.data;
  },

  /**
   * Delete a user
   */
  async delete(id) {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  },

  /**
   * Reset user password
   */
  async resetPassword(id, newPassword) {
    const response = await api.post(`/users/${id}/reset-password`, {
      password: newPassword
    });
    return response.data;
  },

  /**
   * Toggle user active status
   */
  async toggleStatus(id) {
    const response = await api.post(`/users/${id}/toggle-status`);
    return response.data;
  },

  /**
   * Get user activity logs
   */
  async getActivityLogs(id, params = {}) {
    const response = await api.get(`/users/${id}/activity`, { params });
    return response.data;
  }
};
