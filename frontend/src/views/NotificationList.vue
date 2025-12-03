<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
          <p class="text-gray-600 mt-1">Gérez vos notifications et alertes</p>
        </div>
        
        <div class="flex items-center gap-3">
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllAsRead"
            class="px-4 py-2 rounded-xl bg-moov-orange/10 text-moov-orange hover:bg-moov-orange/20 font-bold transition-all"
          >
            Tout marquer comme lu
          </button>
          <button
            @click="handleDeleteAllRead"
            class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 font-bold transition-all"
          >
            Supprimer tout (lu)
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card p-6 mb-6">
        <div class="grid grid-cols-3 gap-4">
          <FormSelect
            v-model="selectedType"
            label="Filtrer par type"
            :options="[
              { label: 'Tous les types', value: '' },
              { label: 'Nouveau PDV', value: 'pdv_created' },
              { label: 'PDV validé', value: 'pdv_validated' },
              { label: 'PDV rejeté', value: 'pdv_rejected' },
              { label: 'Nouvel utilisateur', value: 'user_created' },
              { label: 'Alerte proximité', value: 'proximity_alert' },
              { label: 'Système', value: 'system' }
            ]"
            option-label="label"
            option-value="value"
            @change="handleFilterChange"
          />
          
          <FormSelect
            v-model="selectedStatus"
            label="Statut"
            :options="[
              { label: 'Toutes', value: '' },
              { label: 'Non lues', value: 'unread' },
              { label: 'Lues', value: 'read' }
            ]"
            option-label="label"
            option-value="value"
            @change="handleFilterChange"
          />
          
          <div class="flex items-end">
            <button
              @click="handleRefresh"
              :disabled="loading"
              class="w-full px-4 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <svg
                class="w-5 h-5"
                :class="{ 'animate-spin': loading }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              Actualiser
            </button>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="glass-card">
        <!-- Loading -->
        <div v-if="loading" class="p-12 flex flex-col items-center justify-center">
          <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-moov-orange mb-4"></div>
          <p class="text-gray-500 text-sm">Chargement des notifications...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="notifications.length === 0" class="p-12 flex flex-col items-center justify-center">
          <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Aucune notification</h3>
          <p class="text-gray-600">Vous n'avez aucune notification pour le moment</p>
        </div>

        <!-- Notifications -->
        <div v-else class="divide-y divide-gray-100">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="p-6 hover:bg-gray-50 transition-colors cursor-pointer group"
            :class="{ 'bg-blue-50/30': !notification.is_read }"
            @click="handleNotificationClick(notification)"
          >
            <div class="flex items-start gap-4">
              <!-- Icon -->
              <div
                class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110"
                :class="`bg-${getTypeConfig(notification.type).color}-100`"
              >
                <component
                  :is="getIconComponent(notification.type)"
                  class="w-7 h-7"
                  :class="`text-${getTypeConfig(notification.type).color}-600`"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-4 mb-2">
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <h3 class="text-lg font-bold text-gray-900">{{ notification.title }}</h3>
                      <span
                        v-if="!notification.is_read"
                        class="w-2 h-2 rounded-full bg-moov-orange flex-shrink-0"
                      ></span>
                    </div>
                    <p class="text-gray-600">{{ notification.message }}</p>
                  </div>
                  
                  <button
                    @click.stop="handleDelete(notification.id)"
                    class="w-10 h-10 rounded-xl hover:bg-red-50 flex items-center justify-center flex-shrink-0 transition-all opacity-0 group-hover:opacity-100"
                  >
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>

                <div class="flex items-center gap-3 mt-3">
                  <span
                    class="text-xs px-3 py-1.5 rounded-full font-bold"
                    :class="`bg-${getTypeConfig(notification.type).color}-100 text-${getTypeConfig(notification.type).color}-700`"
                  >
                    {{ getTypeConfig(notification.type).label }}
                  </span>
                  <span class="text-sm text-gray-500 font-semibold">{{ formatDate(notification.created_at) }}</span>
                  <span
                    v-if="notification.is_read"
                    class="text-xs text-gray-400"
                  >
                    • Lu {{ formatDate(notification.read_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
          <button
            @click="previousPage"
            :disabled="currentPage === 1"
            class="px-4 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ← Précédent
          </button>
          
          <span class="text-sm text-gray-600 font-semibold">
            Page {{ currentPage }} sur {{ totalPages }}
          </span>
          
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Suivant →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import FormSelect from '../components/FormSelect.vue';
import { useNotificationStore } from '../stores/notification';
import NotificationService from '../services/NotificationService';

const router = useRouter();
const notificationStore = useNotificationStore();

// Filters
const selectedType = ref('');
const selectedStatus = ref('');

// Computed
const notifications = computed(() => notificationStore.notifications);
const unreadCount = computed(() => notificationStore.unreadCount);
const loading = computed(() => notificationStore.loading);
const currentPage = computed(() => notificationStore.currentPage);
const totalPages = computed(() => notificationStore.totalPages);

// Icon components using h() render function
const getIconComponent = (type) => {
  const iconPaths = {
    pdv_created: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z',
    pdv_validated: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    pdv_rejected: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    user_created: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    proximity_alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
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
const getTypeConfig = (type) => {
  return NotificationService.getTypeConfig(type);
};

const formatDate = (timestamp) => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'à l\'instant';
  if (diffMins < 60) return `il y a ${diffMins} minute${diffMins > 1 ? 's' : ''}`;
  if (diffHours < 24) return `il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
  if (diffDays < 7) return `il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
  
  return date.toLocaleDateString('fr-FR', { 
    day: 'numeric', 
    month: 'long', 
    year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined 
  });
};

const handleFilterChange = async () => {
  const params = {};
  
  if (selectedType.value) {
    params.type = selectedType.value;
  }
  
  if (selectedStatus.value === 'unread') {
    params.unread_only = true;
  } else if (selectedStatus.value === 'read') {
    params.read_only = true;
  }
  
  await notificationStore.fetchNotifications(params);
};

const handleRefresh = async () => {
  await handleFilterChange();
};

const handleNotificationClick = async (notification) => {
  // Mark as read if unread
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }

  // Navigate to related resource if URL provided
  if (notification.data?.url) {
    router.push(notification.data.url);
  }
};

const handleMarkAllAsRead = async () => {
  try {
    await notificationStore.markAllAsRead();
  } catch (error) {
    console.error('Error marking all as read:', error);
    alert('Erreur lors du marquage des notifications');
  }
};

const handleDeleteAllRead = async () => {
  if (confirm('Êtes-vous sûr de vouloir supprimer toutes les notifications lues ?')) {
    try {
      await notificationStore.deleteAllRead();
    } catch (error) {
      console.error('Error deleting read notifications:', error);
      alert('Erreur lors de la suppression des notifications');
    }
  }
};

const handleDelete = async (id) => {
  if (confirm('Supprimer cette notification ?')) {
    try {
      await notificationStore.deleteNotification(id);
    } catch (error) {
      console.error('Error deleting notification:', error);
      alert('Erreur lors de la suppression');
    }
  }
};

const previousPage = async () => {
  if (currentPage.value > 1) {
    notificationStore.setPage(currentPage.value - 1);
    await handleFilterChange();
  }
};

const nextPage = async () => {
  if (currentPage.value < totalPages.value) {
    notificationStore.setPage(currentPage.value + 1);
    await handleFilterChange();
  }
};

// Lifecycle
onMounted(async () => {
  await notificationStore.fetchNotifications();
  await notificationStore.fetchUnreadCount();
});
</script>
