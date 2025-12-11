<template>
  <!-- Notification Badge -->
  <button
    @click="toggleNotifications"
    class="relative w-12 h-12 rounded-xl bg-white/50 hover:bg-white flex items-center justify-center transition-all duration-200"
    :class="{ 'ring-2 ring-moov-orange': showNotifications }"
  >
    <!-- Bell Icon -->
    <component :is="BellIcon" class="w-6 h-6 text-gray-700" />
    
    <!-- Unread Badge -->
    <span
      v-if="unreadCount > 0"
      class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center ring-2 ring-white"
    >
      {{ unreadCount > 99 ? '99+' : unreadCount }}
    </span>
  </button>

  <!-- Notification Dropdown -->
  <Teleport to="body">
    <div
      v-if="showNotifications"
      class="fixed inset-0 z-50"
      @click.self="showNotifications = false"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/20 backdrop-blur-sm"></div>
      
      <!-- Notification Panel -->
      <div
        class="absolute top-20 right-4 w-96 max-h-[600px] bg-white/15 backdrop-blur-xl border border-white/30 rounded-2xl shadow-2xl overflow-hidden"
        @click.stop
      >
        <!-- Header -->
        <div class="p-4 border-b border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold text-gray-900">Notifications</h3>
            <button
              @click="showNotifications = false"
              class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center transition-colors"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <button
              v-if="unreadCount > 0"
              @click="handleMarkAllAsRead"
              class="text-xs px-3 py-1.5 rounded-lg bg-moov-orange/10 text-moov-orange hover:bg-moov-orange/20 font-semibold transition-colors"
            >
              Tout marquer comme lu
            </button>
            <button
              @click="handleDeleteAllRead"
              class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 font-semibold transition-colors"
            >
              Supprimer tout (lu)
            </button>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto max-h-[500px] custom-scrollbar">
          <!-- Loading -->
          <div v-if="loading" class="p-8 flex flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-moov-orange mb-4"></div>
            <p class="text-gray-500 text-sm">Chargement...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="notifications.length === 0" class="p-8 flex flex-col items-center justify-center">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
              </svg>
            </div>
            <p class="text-gray-600 font-semibold">Aucune notification</p>
            <p class="text-gray-400 text-sm mt-1">Vous êtes à jour !</p>
          </div>

          <!-- Notifications -->
          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="p-4 hover:bg-gray-50 transition-colors cursor-pointer"
              :class="{ 'bg-blue-50/50': !notification.is_read }"
              @click="handleNotificationClick(notification)"
            >
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div
                  class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="`bg-${getTypeConfig(notification.type).color}-100`"
                >
                  <component
                    :is="getIconComponent(notification.type)"
                    class="w-5 h-5"
                    :class="`text-${getTypeConfig(notification.type).color}-600`"
                  />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 line-clamp-2">
                      {{ notification.title }}
                    </p>
                    <button
                      @click.stop="handleDelete(notification.id)"
                      class="w-6 h-6 rounded hover:bg-gray-200 flex items-center justify-center flex-shrink-0 transition-colors"
                    >
                      <svg class="w-4 h-4 text-gray-400 hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </button>
                  </div>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
                  <div class="flex items-center gap-2 mt-2">
                    <span
                      class="text-xs px-2 py-0.5 rounded-full font-semibold"
                      :class="`bg-${getTypeConfig(notification.type).color}-100 text-${getTypeConfig(notification.type).color}-700`"
                    >
                      {{ getTypeConfig(notification.type).label }}
                    </span>
                    <span class="text-xs text-blue-600">{{ formatTime(notification.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="p-3 border-t border-gray-200 bg-gray-50">
          <button
            @click="goToNotifications"
            class="w-full text-sm text-moov-orange hover:text-moov-orange-dark font-semibold transition-colors"
          >
            Voir toutes les notifications →
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from '../stores/notification';
import { useConfirm } from '../composables/useConfirm';
import NotificationService from '../services/NotificationService';

const router = useRouter();
const notificationStore = useNotificationStore();
const { confirm } = useConfirm();

const showNotifications = ref(false);

// Computed
const notifications = computed(() => notificationStore.notifications);
const unreadCount = computed(() => notificationStore.unreadCount);
const loading = computed(() => notificationStore.loading);

// Icon components using h() render function
const BellIcon = h('svg', {
  fill: 'none',
  stroke: 'currentColor',
  viewBox: '0 0 24 24'
}, [
  h('path', {
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    'stroke-width': '2',
    d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
  })
]);

const getIconComponent = (type) => {
  const iconPaths = {
    pdv_created: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
    pdv_validated: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    pdv_rejected: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    user_created: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    proximity_alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    task_assigned: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    task_completed: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
    task_validated: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    task_revision_requested: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    system: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
  };

  const path = iconPaths[type] || iconPaths.system;
  
  return h('svg', {
    fill: 'none',
    stroke: 'currentColor',
    viewBox: '0 0 24 24'
  }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '2',
      d: path
    })
  ]);
};

// Methods
const toggleNotifications = async () => {
  showNotifications.value = !showNotifications.value;
  
  if (showNotifications.value) {
    await notificationStore.fetchNotifications();
  }
};

const getTypeConfig = (type) => {
  return NotificationService.getTypeConfig(type);
};

const formatTime = (timestamp) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'À l\'instant';
  if (diffMins < 60) return `Il y a ${diffMins}m`;
  if (diffHours < 24) return `Il y a ${diffHours}h`;
  if (diffDays < 7) return `Il y a ${diffDays}j`;
  
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

const handleNotificationClick = async (notification) => {
  // Mark as read if unread
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }

  // Navigate to related resource
  if (notification.data?.url) {
    router.push(notification.data.url);
    showNotifications.value = false;
  }
};

const handleMarkAllAsRead = async () => {
  try {
    await notificationStore.markAllAsRead();
  } catch (error) {
    console.error('Error marking all as read:', error);
  }
};

const handleDeleteAllRead = async () => {
  const confirmed = await confirm({
    title: 'Supprimer les notifications',
    message: 'Supprimer toutes les notifications lues ?',
    confirmText: 'Supprimer',
    type: 'danger'
  });
  if (!confirmed) return;
  
  try {
    await notificationStore.deleteAllRead();
  } catch (error) {
    console.error('Error deleting read notifications:', error);
  }
};

const handleDelete = async (id) => {
  try {
    await notificationStore.deleteNotification(id);
  } catch (error) {
    console.error('Error deleting notification:', error);
  }
};

const goToNotifications = () => {
  router.push('/notifications');
  showNotifications.value = false;
};

// Fetch unread count on mount and periodically
onMounted(() => {
  notificationStore.fetchUnreadCount();
  
  // Poll for new notifications every 30 seconds
  setInterval(() => {
    notificationStore.fetchUnreadCount();
  }, 30000);
});
</script>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 107, 0, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(255, 107, 0, 0.3);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(255, 107, 0, 0.5);
}
</style>


