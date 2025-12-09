import { defineStore } from 'pinia';
import AuthService from '../services/AuthService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
    mustChangePassword: JSON.parse(localStorage.getItem('mustChangePassword') || 'false'),
  }),

  getters: {
    isAdmin: (state) => state.user?.role?.name === 'admin',
    isDealerOwner: (state) => state.user?.role?.name === 'dealer_owner',
    isDealerAgent: (state) => state.user?.role?.name === 'dealer_agent',
    isDealer: (state) => {
      const roleName = state.user?.role?.name;
      return roleName === 'dealer_owner' || roleName === 'dealer_agent';
    },
    organizationId: (state) => state.user?.organization_id,
    userRole: (state) => state.user?.role?.name,
    requiresPasswordChange: (state) => state.mustChangePassword === true,
  },

  actions: {
    async login(credentials) {
      try {
        const data = await AuthService.login(credentials);
        this.token = data.token;
        this.user = data.user;
        this.isAuthenticated = true;
        this.mustChangePassword = data.must_change_password || false;
        
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        localStorage.setItem('mustChangePassword', JSON.stringify(this.mustChangePassword));
        
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
        this.mustChangePassword = false;
        
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('mustChangePassword');
      }
    },

    async fetchUser() {
      try {
        const data = await AuthService.getMe();
        this.user = data.user;
        this.mustChangePassword = data.must_change_password || false;
        localStorage.setItem('user', JSON.stringify(data.user));
        localStorage.setItem('mustChangePassword', JSON.stringify(this.mustChangePassword));
      } catch (error) {
        this.logout();
        throw error;
      }
    },

    async changePassword(passwordData) {
      try {
        const data = await AuthService.changePassword(passwordData);
        this.user = data.user;
        this.mustChangePassword = false;
        localStorage.setItem('user', JSON.stringify(data.user));
        localStorage.setItem('mustChangePassword', 'false');
        return data;
      } catch (error) {
        throw error;
      }
    },

    clearPasswordChangeRequirement() {
      this.mustChangePassword = false;
      localStorage.setItem('mustChangePassword', 'false');
    },
  },
});
