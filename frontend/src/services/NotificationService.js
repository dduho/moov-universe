import api from './api';

const NotificationService = {
  /**
   * Get all notifications for current user
   * @param {Object} params - Query parameters (page, per_page, unread_only, type)
   * @returns {Promise<Object>} Notifications with pagination
   */
  getAll(params = {}) {
    return api.get('/notifications', { params })
      .then(response => response.data);
  },

  /**
   * Get unread notifications count
   * @returns {Promise<number>} Count of unread notifications
   */
  getUnreadCount() {
    return api.get('/notifications/unread-count')
      .then(response => response.data.count);
  },

  /**
   * Get a single notification by ID
   * @param {number} id - Notification ID
   * @returns {Promise<Object>} Notification details
   */
  getById(id) {
    return api.get(`/notifications/${id}`)
      .then(response => response.data);
  },

  /**
   * Mark notification as read
   * @param {number} id - Notification ID
   * @returns {Promise<Object>} Updated notification
   */
  markAsRead(id) {
    return api.post(`/notifications/${id}/read`)
      .then(response => response.data);
  },

  /**
   * Mark all notifications as read
   * @returns {Promise<Object>} Success message
   */
  markAllAsRead() {
    return api.post('/notifications/mark-all-read')
      .then(response => response.data);
  },

  /**
   * Delete a notification
   * @param {number} id - Notification ID
   * @returns {Promise<void>}
   */
  delete(id) {
    return api.delete(`/notifications/${id}`)
      .then(response => response.data);
  },

  /**
   * Delete all read notifications
   * @returns {Promise<Object>} Success message with count
   */
  deleteAllRead() {
    return api.delete('/notifications/delete-all-read')
      .then(response => response.data);
  },

  /**
   * Get notification types for filtering
   * @returns {Array<string>} List of notification types
   */
  getTypes() {
    return [
      'pdv_created',
      'pdv_validated',
      'pdv_rejected',
      'user_created',
      'proximity_alert',
      'system'
    ];
  },

  /**
   * Get notification icon and color by type
   * @param {string} type - Notification type
   * @returns {Object} Icon component and color class
   */
  getTypeConfig(type) {
    const configs = {
      pdv_created: {
        icon: 'store',
        color: 'blue',
        label: 'Nouveau PDV'
      },
      pdv_validated: {
        icon: 'check-circle',
        color: 'green',
        label: 'PDV validé'
      },
      pdv_rejected: {
        icon: 'x-circle',
        color: 'red',
        label: 'PDV rejeté'
      },
      user_created: {
        icon: 'user',
        color: 'purple',
        label: 'Nouvel utilisateur'
      },
      proximity_alert: {
        icon: 'alert',
        color: 'yellow',
        label: 'Alerte proximité'
      },
      task_assigned: {
        icon: 'task',
        color: 'blue',
        label: 'Tâche assignée'
      },
      task_completed: {
        icon: 'check',
        color: 'yellow',
        label: 'Tâche complétée'
      },
      task_validated: {
        icon: 'check-circle',
        color: 'green',
        label: 'Tâche validée'
      },
      task_revision_requested: {
        icon: 'alert',
        color: 'orange',
        label: 'Révision demandée'
      },
      system: {
        icon: 'bell',
        color: 'gray',
        label: 'Système'
      }
    };
    return configs[type] || configs.system;
  }
};

export default NotificationService;
