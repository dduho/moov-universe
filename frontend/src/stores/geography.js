import { defineStore } from 'pinia';
import GeographyService from '../services/GeographyService';

export const useGeographyStore = defineStore('geography', {
  state: () => ({
    regions: [],
    prefectures: [],
    communes: [],
    cantons: [],
    hierarchy: null,
    loading: false,
  }),

  actions: {
    async fetchRegions() {
      this.loading = true;
      try {
        this.regions = await GeographyService.getRegions();
      } catch (error) {
        console.error('Failed to fetch regions:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchPrefectures(region) {
      this.loading = true;
      try {
        this.prefectures = await GeographyService.getPrefectures(region);
      } catch (error) {
        console.error('Failed to fetch prefectures:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCommunes(prefecture) {
      this.loading = true;
      try {
        this.communes = await GeographyService.getCommunes(prefecture);
      } catch (error) {
        console.error('Failed to fetch communes:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchCantons(commune) {
      this.loading = true;
      try {
        this.cantons = await GeographyService.getCantons(commune);
      } catch (error) {
        console.error('Failed to fetch cantons:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchHierarchy() {
      this.loading = true;
      try {
        this.hierarchy = await GeographyService.getHierarchy();
      } catch (error) {
        console.error('Failed to fetch hierarchy:', error);
      } finally {
        this.loading = false;
      }
    },
  },
});
