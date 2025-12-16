import api from './api';

export default {
  async getAll() {
    const response = await api.get('/settings');
    return response.data;
  },

  async getSetting(key) {
    const response = await api.get(`/settings/${key}`);
    return response.data;
  },

  async updateSetting(key, value) {
    const response = await api.put(`/settings/${key}`, { value });
    return response.data;
  },

  async getProximityThreshold() {
    const response = await this.getSetting('pdv_proximity_threshold');
    return response.value;
  },

  async testSmtpConnection() {
    const response = await api.get('/mail/test-smtp');
    return response.data;
  }
};
