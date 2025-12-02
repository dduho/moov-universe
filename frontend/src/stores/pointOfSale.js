import { defineStore } from 'pinia';
import PointOfSaleService from '../services/PointOfSaleService';

export const usePointOfSaleStore = defineStore('pointOfSale', {
  state: () => ({
    pointOfSales: [],
    currentPdv: null,
    loading: false,
    error: null,
    pagination: {
      current_page: 1,
      per_page: 15,
      total: 0,
    },
  }),

  actions: {
    async fetchPointOfSales(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.getAll(params);
        this.pointOfSales = data.data;
        this.pagination = {
          current_page: data.current_page,
          per_page: data.per_page,
          total: data.total,
        };
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch PDVs';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async fetchPdvById(id) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.getById(id);
        this.currentPdv = data;
        return data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch PDV';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async createPdv(pdvData) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.create(pdvData);
        return data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create PDV';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async updatePdv(id, pdvData) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.update(id, pdvData);
        return data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update PDV';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async validatePdv(id) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.validate(id);
        return data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to validate PDV';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async rejectPdv(id, reason) {
      this.loading = true;
      this.error = null;
      try {
        const data = await PointOfSaleService.reject(id, reason);
        return data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to reject PDV';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    async checkProximity(latitude, longitude, excludeId = null) {
      try {
        const data = await PointOfSaleService.checkProximity(latitude, longitude, excludeId);
        return data;
      } catch (error) {
        console.error('Proximity check error:', error);
        return { has_nearby: false, nearby_pdvs: [] };
      }
    },
  },
});
