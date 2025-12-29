<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Actions en attente</h3>
          <p class="text-sm text-gray-600">Synchronisation avec le serveur</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <span v-if="!isOnline" class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-semibold">
          Hors ligne
        </span>
        <span v-else class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
          En ligne
        </span>
        <button
          v-if="isOnline && pendingActions.length > 0"
          @click="syncNow"
          :disabled="syncInProgress"
          class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-semibold transition-colors flex items-center gap-2"
        >
          <svg class="w-4 h-4" :class="{ 'animate-spin': syncInProgress }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ syncInProgress ? 'Synchronisation...' : 'Synchroniser' }}
        </button>
      </div>
    </div>

    <!-- Liste des actions -->
    <div v-if="pendingActions.length > 0" class="space-y-3">
      <div
        v-for="action in pendingActions"
        :key="action.id"
        class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200"
      >
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg flex items-center justify-center"
            :class="{
              'bg-blue-100 text-blue-600': action.method === 'GET',
              'bg-green-100 text-green-600': action.method === 'POST',
              'bg-yellow-100 text-yellow-600': action.method === 'PUT',
              'bg-red-100 text-red-600': action.method === 'DELETE'
            }"
          >
            <svg v-if="action.method === 'POST'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <svg v-else-if="action.method === 'PUT'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <svg v-else-if="action.method === 'DELETE'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-900">
              {{ action.method }} {{ getEndpointName(action.url) }}
            </p>
            <p class="text-xs text-gray-500">
              {{ formatTimestamp(action.timestamp) }}
            </p>
          </div>
        </div>

        <button
          @click="deleteAction(action.id)"
          class="text-gray-400 hover:text-red-500 transition-colors"
          title="Annuler"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- État vide -->
    <div v-else class="text-center py-12">
      <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-gray-600 font-semibold">Aucune action en attente</p>
      <p class="text-sm text-gray-500 mt-1">Toutes vos actions sont synchronisées</p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useOffline } from '../composables/useOffline';
import { offlineDB } from '../utils/offlineDB';

export default {
  name: 'SyncManager',
  setup() {
    const { isOnline, syncInProgress, syncPendingActions, updatePendingCount } = useOffline();
    const pendingActions = ref([]);

    const loadPendingActions = async () => {
      try {
        pendingActions.value = await offlineDB.getPendingActions();
      } catch (error) {
        console.error('Erreur chargement actions:', error);
      }
    };

    const syncNow = async () => {
      await syncPendingActions();
      await loadPendingActions();
      await updatePendingCount();
    };

    const deleteAction = async (id) => {
      if (confirm('Voulez-vous annuler cette action ?')) {
        try {
          await offlineDB.deletePendingAction(id);
          await loadPendingActions();
          await updatePendingCount();
        } catch (error) {
          console.error('Erreur suppression:', error);
        }
      }
    };

    const getEndpointName = (url) => {
      try {
        const urlObj = new URL(url);
        const path = urlObj.pathname;
        const segments = path.split('/').filter(Boolean);
        return segments.slice(-2).join('/');
      } catch {
        return url;
      }
    };

    const formatTimestamp = (timestamp) => {
      const date = new Date(timestamp);
      const now = new Date();
      const diff = now - date;
      
      if (diff < 60000) return 'Il y a quelques secondes';
      if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
      if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)}h`;
      
      return date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: 'short', 
        hour: '2-digit', 
        minute: '2-digit' 
      });
    };

    onMounted(() => {
      loadPendingActions();
      
      // Rafraîchir toutes les 30 secondes
      const interval = setInterval(loadPendingActions, 30000);
      
      return () => clearInterval(interval);
    });

    return {
      isOnline,
      syncInProgress,
      pendingActions,
      syncNow,
      deleteAction,
      getEndpointName,
      formatTimestamp
    };
  }
};
</script>
