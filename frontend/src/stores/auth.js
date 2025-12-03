import { defineStore } from 'pinia';
import AuthService from '../services/AuthService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),

  getters: {
    isAdmin: (state) => state.user?.role?.name === 'admin',
    isDealer: (state) => state.user?.role?.name === 'dealer',
    isCommercial: (state) => state.user?.role?.name === 'commercial',
    organizationId: (state) => state.user?.organization_id,
  },

  actions: {
    async login(credentials) {
      try {
        const data = await AuthService.login(credentials);
        this.token = data.token;
        this.user = data.user;
        this.isAuthenticated = true;
        
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        
        return data;
      } catch (error) {
        throw error;
      }
    },

    async logout() {
      try {
        await AuthService.logout();
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        this.token = null;
        this.user = null;
        this.isAuthenticated = false;
        
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      }
    },

    async fetchUser() {
      try {
        const data = await AuthService.getMe();
        this.user = data.user;
        localStorage.setItem('user', JSON.stringify(data.user));
      } catch (error) {
        this.logout();
        throw error;
      }
    },
  },
});
