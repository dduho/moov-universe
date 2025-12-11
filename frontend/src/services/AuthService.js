import api from './api';

export default {
  async login(credentials) {
    const response = await api.post('/login', credentials);
    return response.data;
  },

  async logout() {
    const response = await api.post('/logout');
    return response.data;
  },

  async getMe() {
    const response = await api.get('/me');
    return response.data;
  },

  async register(data) {
    const response = await api.post('/register', data);
    return response.data;
  },

  async changePassword(data) {
    const response = await api.post('/change-password', data);
    return response.data;
  },

  async getPasswordRules() {
    const response = await api.get('/password-rules');
    return response.data;
  },

  async updateProfile(data) {
    const response = await api.put('/profile', data);
    return response.data;
  },

  async updatePassword(data) {
    const response = await api.put('/profile/password', data);
    return response.data;
  },
};
