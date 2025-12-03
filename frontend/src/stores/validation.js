import { defineStore } from 'pinia';
import ValidationService from '../services/ValidationService';

export const useValidationStore = defineStore('validation', {
  state: () => ({
    pendingPOS: [],
    stats: {
      validatedToday: 0,
      rejectedToday: 0,
      averageTime: '0h'
    },
    loading: false,
    error: null
  }),

  getters: {
    pendingCount: (state) => state.pendingPOS.length,
    
    posWithProximityAlert: (state) => 
      state.pendingPOS.filter(pos => pos.proximity_alert),
  },

  actions: {
    async fetchPendingPOS() {
      this.loading = true;
      this.error = null;
      try {
        const data = await ValidationService.getPending();
        this.pendingPOS = data;
      } catch (error) {
        this.error = error.message;
        console.error('Error fetching pending POS:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchStats() {
      try {
        const data = await ValidationService.getStats();
        this.stats = data;
      } catch (error) {
        console.error('Error fetching validation stats:', error);
      }
    },

    async validatePOS(posId) {
      this.loading = true;
      this.error = null;
      try {
        await ValidationService.validate(posId);
        
        // Remove from pending list
        this.pendingPOS = this.pendingPOS.filter(pos => pos.id !== posId);
        
        // Update stats
        this.stats.validatedToday++;
        
        return { success: true };
      } catch (error) {
        this.error = error.message;
        console.error('Error validating POS:', error);
        return { success: false, error: error.message };
      } finally {
        this.loading = false;
      }
    },

    async rejectPOS(posId, reason) {
      this.loading = true;
      this.error = null;
      try {
        await ValidationService.reject(posId, reason);
        
        // Remove from pending list
        this.pendingPOS = this.pendingPOS.filter(pos => pos.id !== posId);
        
        // Update stats
        this.stats.rejectedToday++;
        
        return { success: true };
      } catch (error) {
        this.error = error.message;
        console.error('Error rejecting POS:', error);
        return { success: false, error: error.message };
      } finally {
        this.loading = false;
      }
    },

    async checkProximity(latitude, longitude) {
      try {
        const data = await ValidationService.checkProximity(latitude, longitude);
        return data;
      } catch (error) {
        console.error('Error checking proximity:', error);
        return { hasNearby: false, nearby: [] };
      }
    }
  }
});
