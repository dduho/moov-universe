import { defineStore } from 'pinia';
import UserService from '../services/UserService';

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    currentUser: null,
    loading: false,
    error: null
  }),

  getters: {
    activeUsers: (state) => state.users.filter(u => u.is_active),
    usersByRole: (state) => (role) => state.users.filter(u => u.role === role),
    usersByOrganization: (state) => (orgId) => state.users.filter(u => u.organization_id === orgId)
  },

  actions: {
    async fetchUsers(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        this.users = await UserService.getAll(params);
      } catch (error) {
        this.error = error.message || 'Erreur lors du chargement des utilisateurs';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchUser(id) {
      this.loading = true;
      this.error = null;
      try {
        this.currentUser = await UserService.getById(id);
      } catch (error) {
        this.error = error.message || 'Erreur lors du chargement de l\'utilisateur';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createUser(userData) {
      this.loading = true;
      this.error = null;
      try {
        const newUser = await UserService.create(userData);
        this.users.push(newUser);
        return newUser;
      } catch (error) {
        this.error = error.message || 'Erreur lors de la création de l\'utilisateur';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateUser(id, userData) {
      this.loading = true;
      this.error = null;
      try {
        const updatedUser = await UserService.update(id, userData);
        const index = this.users.findIndex(u => u.id === id);
        if (index !== -1) {
          this.users[index] = updatedUser;
        }
        if (this.currentUser?.id === id) {
          this.currentUser = updatedUser;
        }
        return updatedUser;
      } catch (error) {
        this.error = error.message || 'Erreur lors de la mise à jour de l\'utilisateur';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteUser(id) {
      this.loading = true;
      this.error = null;
      try {
        await UserService.delete(id);
        this.users = this.users.filter(u => u.id !== id);
        if (this.currentUser?.id === id) {
          this.currentUser = null;
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors de la suppression de l\'utilisateur';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async resetPassword(id, newPassword) {
      this.loading = true;
      this.error = null;
      try {
        await UserService.resetPassword(id, newPassword);
      } catch (error) {
        this.error = error.message || 'Erreur lors de la réinitialisation du mot de passe';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async toggleStatus(id) {
      this.loading = true;
      this.error = null;
      try {
        const updatedUser = await UserService.toggleStatus(id);
        const index = this.users.findIndex(u => u.id === id);
        if (index !== -1) {
          this.users[index] = updatedUser;
        }
      } catch (error) {
        this.error = error.message || 'Erreur lors du changement de statut';
        throw error;
      } finally {
        this.loading = false;
      }
    }
  }
});
