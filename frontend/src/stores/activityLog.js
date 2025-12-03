import { defineStore } from 'pinia';
import ActivityLogService from '../services/ActivityLogService';

export const useActivityLogStore = defineStore('activityLog', {
  state: () => ({
    logs: [],
    loading: false,
    error: null,
    currentPage: 1,
    totalPages: 1,
    perPage: 20,
    total: 0,
    filters: {
      user_id: null,
      action: null,
      resource_type: null,
      start_date: null,
      end_date: null,
      search: ''
    },
    statistics: null
  }),

  getters: {
    logsByAction: (state) => (action) => {
      return state.logs.filter(log => log.action === action);
    },

    logsByResource: (state) => (resourceType) => {
      return state.logs.filter(log => log.resource_type === resourceType);
    },

    logsByUser: (state) => (userId) => {
      return state.logs.filter(log => log.user_id === userId);
    },

    recentLogs: (state) => {
      return state.logs.slice(0, 10);
    },

    hasFilters: (state) => {
      return !!(
        state.filters.user_id ||
        state.filters.action ||
        state.filters.resource_type ||
        state.filters.start_date ||
        state.filters.end_date ||
        state.filters.search
      );
    }
  },

  actions: {
    /**
     * Fetch activity logs with filters
     */
    async fetchLogs(params = {}) {
      this.loading = true;
      this.error = null;

      try {
        const queryParams = {
          page: this.currentPage,
          per_page: this.perPage,
          ...this.filters,
          ...params
        };

        // Remove null/empty filters
        Object.keys(queryParams).forEach(key => {
          if (queryParams[key] === null || queryParams[key] === '') {
            delete queryParams[key];
          }
        });

        const data = await ActivityLogService.getAll(queryParams);
        
        this.logs = data.data || data;
        this.total = data.total || this.logs.length;
        this.currentPage = data.current_page || 1;
        this.totalPages = data.last_page || 1;
        this.perPage = data.per_page || 20;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des logs';
        console.error('Error fetching activity logs:', error);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch logs for a specific resource
     */
    async fetchByResource(resourceType, resourceId) {
      this.loading = true;
      this.error = null;

      try {
        const data = await ActivityLogService.getByResource(resourceType, resourceId);
        this.logs = data.data || data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des logs';
        console.error('Error fetching resource logs:', error);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch logs for a specific user
     */
    async fetchByUser(userId) {
      this.loading = true;
      this.error = null;

      try {
        const data = await ActivityLogService.getByUser(userId);
        this.logs = data.data || data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Erreur lors du chargement des logs';
        console.error('Error fetching user logs:', error);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Create a new activity log entry
     */
    async logAction(logData) {
      try {
        const enrichedData = {
          ...logData,
          ip_address: await this.getClientIP(),
          user_agent: navigator.userAgent,
          timestamp: new Date().toISOString()
        };

        await ActivityLogService.create(enrichedData);
      } catch (error) {
        console.error('Error creating activity log:', error);
        // Don't throw - logging failures shouldn't break user actions
      }
    },

    /**
     * Fetch activity log statistics
     */
    async fetchStatistics(params = {}) {
      try {
        this.statistics = await ActivityLogService.getStatistics(params);
      } catch (error) {
        console.error('Error fetching statistics:', error);
      }
    },

    /**
     * Update filters
     */
    updateFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters };
      this.currentPage = 1; // Reset to first page
    },

    /**
     * Clear all filters
     */
    clearFilters() {
      this.filters = {
        user_id: null,
        action: null,
        resource_type: null,
        start_date: null,
        end_date: null,
        search: ''
      };
      this.currentPage = 1;
    },

    /**
     * Change page
     */
    setPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.fetchLogs();
      }
    },

    /**
     * Get client IP address (helper method)
     */
    async getClientIP() {
      try {
        const response = await fetch('https://api.ipify.org?format=json');
        const data = await response.json();
        return data.ip;
      } catch (error) {
        return 'unknown';
      }
    }
  }
});
