import { defineStore } from 'pinia';
import NotificationService from '../services/NotificationService';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
    // Pagination
    currentPage: 1,
    totalPages: 1,
    perPage: 20
  }),

  getters: {
    /**
     * Get unread notifications
     */
    unreadNotifications: (state) => {
      return state.notifications.filter(n => !n.is_read);
    },

    /**
     * Get notifications by type
     */
    notificationsByType: (state) => (type) => {
      return state.notifications.filter(n => n.type === type);
    },

    /**
     * Get recent notifications (last 5)
     */
    recentNotifications: (state) => {
      return state.notifications.slice(0, 5);
    },

    /**
     * Check if there are unread notifications
     */
    hasUnread: (state) => {
      return state.unreadCount > 0;
    }
  },

  actions: {
    /**
     * Fetch notifications with optional filters
     */
    async fetchNotifications(params = {}) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await NotificationService.getAll({
          page: this.currentPage,
          per_page: this.perPage,
          ...params
        });
        
        // response est déjà le tableau, pas response.data
        this.notifications = Array.isArray(response) ? response : (response.data || []);
        this.currentPage = response.current_page || 1;
        this.totalPages = response.last_page || 1;
        
        return response;
      } catch (error) {
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },

    /**
     * Fetch unread count
     */
    async fetchUnreadCount() {
      try {
        const count = await NotificationService.getUnreadCount();
        this.unreadCount = count;
        return count;
      } catch (error) {
        console.error('Error fetching unread count:', error);
        this.error = error.message;
      }
    },

    /**
     * Mark notification as read
     */
    async markAsRead(id) {
      try {
        const updated = await NotificationService.markAsRead(id);
        
        // Update in store
        const index = this.notifications.findIndex(n => n.id === id);
        if (index !== -1) {
          this.notifications[index].is_read = true;
          this.notifications[index].read_at = updated.read_at;
        }
        
        // Update count
        if (this.unreadCount > 0) {
          this.unreadCount--;
        }
        
        return updated;
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
      try {
        await NotificationService.markAllAsRead();
        
        // Update all in store
        this.notifications.forEach(n => {
          n.is_read = true;
          n.read_at = new Date().toISOString();
        });
        
        this.unreadCount = 0;
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },

    /**
     * Delete notification
     */
    async deleteNotification(id) {
      try {
        await NotificationService.delete(id);
        
        // Remove from store
        const notification = this.notifications.find(n => n.id === id);
        this.notifications = this.notifications.filter(n => n.id !== id);
        
        // Update count if it was unread
        if (notification && !notification.is_read && this.unreadCount > 0) {
          this.unreadCount--;
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },

    /**
     * Delete all read notifications
     */
    async deleteAllRead() {
      try {
        const response = await NotificationService.deleteAllRead();
        
        // Remove read notifications from store
        this.notifications = this.notifications.filter(n => !n.is_read);
        
        return response;
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },

    /**
     * Add new notification (for real-time updates)
     */
    addNotification(notification) {
      this.notifications.unshift(notification);
      if (!notification.is_read) {
        this.unreadCount++;
      }
    },

    /**
     * Clear error
     */
    clearError() {
      this.error = null;
    },

    /**
     * Set page
     */
    setPage(page) {
      this.currentPage = page;
    }
  }
});
