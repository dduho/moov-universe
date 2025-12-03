import api from './api';

const ActivityLogService = {
  /**
   * Get all activity logs with filters
   * @param {Object} params - Filter parameters
   * @returns {Promise}
   */
  async getAll(params = {}) {
    const response = await api.get('/activity-logs', { params });
    return response.data;
  },

  /**
   * Get activity logs for a specific resource
   * @param {string} resourceType - Type of resource (pdv, user, organization)
   * @param {number} resourceId - ID of the resource
   * @returns {Promise}
   */
  async getByResource(resourceType, resourceId) {
    const response = await api.get(`/activity-logs/resource/${resourceType}/${resourceId}`);
    return response.data;
  },

  /**
   * Get activity logs for a specific user
   * @param {number} userId - ID of the user
   * @returns {Promise}
   */
  async getByUser(userId) {
    const response = await api.get(`/activity-logs/user/${userId}`);
    return response.data;
  },

  /**
   * Create a new activity log entry
   * @param {Object} logData - Log data
   * @returns {Promise}
   */
  async create(logData) {
    const response = await api.post('/activity-logs', logData);
    return response.data;
  },

  /**
   * Get activity log statistics
   * @param {Object} params - Filter parameters
   * @returns {Promise}
   */
  async getStatistics(params = {}) {
    const response = await api.get('/activity-logs/statistics', { params });
    return response.data;
  },

  /**
   * Export activity logs
   * @param {Object} params - Filter parameters
   * @returns {Promise}
   */
  async export(params = {}) {
    const response = await api.get('/activity-logs/export', {
      params,
      responseType: 'blob'
    });
    return response.data;
  },

  /**
   * Get action type configuration
   * @param {string} action - Action type
   * @returns {Object} Configuration object with icon, color, label
   */
  getActionConfig(action) {
    const configs = {
      create: {
        icon: 'plus-circle',
        color: 'green',
        label: 'Création'
      },
      update: {
        icon: 'pencil',
        color: 'blue',
        label: 'Modification'
      },
      delete: {
        icon: 'trash',
        color: 'red',
        label: 'Suppression'
      },
      validate: {
        icon: 'check-circle',
        color: 'green',
        label: 'Validation'
      },
      reject: {
        icon: 'x-circle',
        color: 'red',
        label: 'Rejet'
      },
      login: {
        icon: 'login',
        color: 'purple',
        label: 'Connexion'
      },
      logout: {
        icon: 'logout',
        color: 'gray',
        label: 'Déconnexion'
      },
      export: {
        icon: 'download',
        color: 'blue',
        label: 'Export'
      },
      import: {
        icon: 'upload',
        color: 'blue',
        label: 'Import'
      }
    };

    return configs[action] || {
      icon: 'document',
      color: 'gray',
      label: action
    };
  },

  /**
   * Get resource type configuration
   * @param {string} resourceType - Resource type
   * @returns {Object} Configuration object with icon, color, label
   */
  getResourceConfig(resourceType) {
    const configs = {
      pdv: {
        icon: 'location-marker',
        color: 'orange',
        label: 'Point de Vente'
      },
      user: {
        icon: 'user',
        color: 'purple',
        label: 'Utilisateur'
      },
      organization: {
        icon: 'office-building',
        color: 'blue',
        label: 'Organisation'
      },
      dealer: {
        icon: 'briefcase',
        color: 'green',
        label: 'Dealer'
      }
    };

    return configs[resourceType] || {
      icon: 'document',
      color: 'gray',
      label: resourceType
    };
  }
};

export default ActivityLogService;
