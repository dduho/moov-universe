<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-20 px-4"
        @click="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Modal -->
        <div
          class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden"
          @click.stop
        >
          <!-- Search Input -->
          <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-200">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher un PDV, dealer, utilisateur..."
              class="flex-1 bg-transparent border-none outline-none text-gray-900 placeholder-gray-400 text-lg"
              @keydown.esc="close"
            />
            <kbd class="hidden sm:inline-block px-2 py-1 rounded bg-gray-100 text-xs font-mono text-gray-600">ESC</kbd>
          </div>

          <!-- Results -->
          <div class="max-h-[60vh] overflow-y-auto">
            <!-- Loading -->
            <div v-if="loading" class="py-12 text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-moov-orange mx-auto"></div>
              <p class="mt-4 text-sm text-gray-600">Recherche en cours...</p>
            </div>

            <!-- No results -->
            <div v-else-if="searchQuery && !hasResults" class="py-12 text-center">
              <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="mt-4 text-lg font-semibold text-gray-700">Aucun résultat trouvé</p>
              <p class="mt-1 text-sm text-gray-500">Essayez avec d'autres mots-clés</p>
            </div>

            <!-- Results sections -->
            <div v-else-if="hasResults" class="py-2">
              <!-- PDV Results -->
              <div v-if="results.pdvs && results.pdvs.length > 0" class="mb-2">
                <h3 class="px-6 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Points de vente ({{ results.pdvs.length }})</h3>
                <router-link
                  v-for="pdv in results.pdvs"
                  :key="`pdv-${pdv.id}`"
                  :to="`/pdv/${pdv.id}`"
                  @click="close"
                  class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ pdv.nom_point }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ pdv.numero_flooz }} • {{ pdv.ville }}</p>
                  </div>
                  <span
                    class="px-2 py-1 rounded text-xs font-semibold"
                    :class="{
                      'bg-green-100 text-green-800': pdv.status === 'validated',
                      'bg-yellow-100 text-yellow-800': pdv.status === 'pending',
                      'bg-red-100 text-red-800': pdv.status === 'rejected'
                    }"
                  >
                    {{ getStatusLabel(pdv.status) }}
                  </span>
                </router-link>
              </div>

              <!-- Dealers Results -->
              <div v-if="results.dealers && results.dealers.length > 0" class="mb-2">
                <h3 class="px-6 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Dealers ({{ results.dealers.length }})</h3>
                <router-link
                  v-for="dealer in results.dealers"
                  :key="`dealer-${dealer.id}`"
                  :to="`/dealers/${dealer.id}`"
                  @click="close"
                  class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <span class="text-white font-bold text-sm">{{ dealer.code?.substring(0, 2) || 'XX' }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ dealer.name }}</p>
                    <p class="text-sm text-gray-500">{{ dealer.code }}</p>
                  </div>
                  <span class="text-sm text-gray-500">{{ dealer.point_of_sales_count || 0 }} PDV</span>
                </router-link>
              </div>

              <!-- Users Results (Admin only) -->
              <div v-if="results.users && results.users.length > 0 && authStore.isAdmin" class="mb-2">
                <h3 class="px-6 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Utilisateurs ({{ results.users.length }})</h3>
                <router-link
                  v-for="user in results.users"
                  :key="`user-${user.id}`"
                  :to="`/users/${user.id}`"
                  @click="close"
                  class="flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                >
                  <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-900 truncate">{{ user.name }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
                  </div>
                  <span class="text-sm text-gray-500">{{ user.role?.display_name }}</span>
                </router-link>
              </div>
            </div>

            <!-- Quick tips when empty -->
            <div v-else class="py-8 px-6">
              <p class="text-sm text-gray-500 mb-4">Recherche rapide :</p>
              <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <kbd class="px-2 py-1 rounded bg-gray-100 font-mono text-xs">Ctrl+K</kbd>
                  <span>Ouvrir la recherche</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <kbd class="px-2 py-1 rounded bg-gray-100 font-mono text-xs">ESC</kbd>
                  <span>Fermer la recherche</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import PointOfSaleService from '../services/PointOfSaleService';
import OrganizationService from '../services/OrganizationService';
import UserService from '../services/UserService';

const authStore = useAuthStore();

const isOpen = ref(false);
const searchQuery = ref('');
const loading = ref(false);
const searchInput = ref(null);
const results = ref({
  pdvs: [],
  dealers: [],
  users: []
});

const hasResults = computed(() => {
  return results.value.pdvs.length > 0 ||
         results.value.dealers.length > 0 ||
         results.value.users.length > 0;
});

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    validated: 'Validé',
    rejected: 'Rejeté'
  };
  return labels[status] || status;
};

let searchTimeout;

watch(searchQuery, async (newQuery) => {
  if (!newQuery || newQuery.trim().length < 2) {
    results.value = { pdvs: [], dealers: [], users: [] };
    return;
  }

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    await performSearch(newQuery);
  }, 300); // Debounce 300ms
});

const performSearch = async (query) => {
  loading.value = true;
  try {
    const searchPromises = [
      // Search PDVs
      PointOfSaleService.getAll({ search: query, per_page: 5 })
        .then(response => response.data || []),
      
      // Search Dealers
      authStore.isAdmin 
        ? OrganizationService.getAll({ search: query, per_page: 5 })
            .then(response => response.data || [])
        : Promise.resolve([]),
      
      // Search Users (Admin only)
      authStore.isAdmin
        ? UserService.getAll({ search: query, per_page: 5 })
            .then(response => response.data || [])
        : Promise.resolve([])
    ];

    const [pdvs, dealers, users] = await Promise.all(searchPromises);

    results.value = {
      pdvs,
      dealers,
      users
    };
  } catch (error) {
    console.error('Search error:', error);
  } finally {
    loading.value = false;
  }
};

const open = () => {
  isOpen.value = true;
  nextTick(() => {
    searchInput.value?.focus();
  });
};

const close = () => {
  isOpen.value = false;
  searchQuery.value = '';
  results.value = { pdvs: [], dealers: [], users: [] };
};

const handleGlobalSearchEvent = () => {
  if (isOpen.value) {
    close();
  } else {
    open();
  }
};

onMounted(() => {
  window.addEventListener('open-global-search', handleGlobalSearchEvent);
});

onUnmounted(() => {
  window.removeEventListener('open-global-search', handleGlobalSearchEvent);
});
</script>

<script>
import { computed } from 'vue';
export default {
  name: 'GlobalSearch'
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
  transition: transform 0.2s ease;
}

.modal-enter-from > div:last-child,
.modal-leave-to > div:last-child {
  transform: scale(0.95);
}
</style>


