import { defineStore } from 'pinia';
import OrganizationService from '../services/OrganizationService';

export const useOrganizationStore = defineStore('organization', {
  state: () => ({
    organizations: [],
    currentOrganization: null,
    loading: false,
    error: null,
  }),

  getters: {
    activeOrganizations: (state) => state.organizations.filter(org => org.is_active),
    
    getOrganizationById: (state) => (id) => {
      return state.organizations.find(org => org.id === id);
    },
  },

  actions: {
    async fetchOrganizations(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        this.organizations = await OrganizationService.getAll(params);
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des dealers';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchOrganization(id) {
      this.loading = true;
      this.error = null;
      try {
        this.currentOrganization = await OrganizationService.getById(id);
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement du dealer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createOrganization(data) {
      this.loading = true;
      this.error = null;
      try {
        const newOrg = await OrganizationService.create(data);
        this.organizations.push(newOrg);
        return newOrg;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la création du dealer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updateOrganization(id, data) {
      this.loading = true;
      this.error = null;
      try {
        const updated = await OrganizationService.update(id, data);
        const index = this.organizations.findIndex(org => org.id === id);
        if (index !== -1) {
          this.organizations[index] = updated;
        }
        if (this.currentOrganization?.id === id) {
          this.currentOrganization = updated;
        }
        return updated;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du dealer';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async deleteOrganization(id) {
      this.loading = true;
      this.error = null;
      try {
        await OrganizationService.delete(id);
        this.organizations = this.organizations.filter(org => org.id !== id);
        if (this.currentOrganization?.id === id) {
          this.currentOrganization = null;
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors de la suppression du dealer';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});
