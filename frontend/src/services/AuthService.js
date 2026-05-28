import api from './api';

export default {
  async login(credentials) {
    const response = await api.post('/login', credentials);
    return response.data;
  },

  async verifyOtp({ otpToken, code }) {
    const response = await api.post('/verify-otp', {
      otp_token: otpToken,
      code,
    });
    return response.data;
  },

  async resendOtp(otpToken) {
    const response = await api.post('/resend-otp', { otp_token: otpToken });
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
