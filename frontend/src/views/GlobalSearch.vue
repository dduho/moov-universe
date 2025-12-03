<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Global Search Modal -->
      <Teleport to="body">
        <div
          v-if="isSearchOpen"
          class="fixed inset-0 z-50 flex items-start justify-center pt-20 bg-black/40 backdrop-blur-sm"
          @click.self="closeSearch"
        >
          <div class="w-full max-w-3xl mx-4">
            <!-- Search Input -->
            <div class="glass-strong rounded-2xl shadow-2xl overflow-hidden">
              <div class="p-6 border-b border-gray-200">
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </div>
                  
                  <input
                    ref="searchInput"
                    v-model="searchQuery"
                    @input="handleSearch"
                    @keydown.esc="closeSearch"
                    @keydown.down="navigateResults('down')"
                    @keydown.up="navigateResults('up')"
                    @keydown.enter="selectHighlighted"
                    type="text"
                    placeholder="Rechercher des PDV, utilisateurs, dealers..."
                    class="w-full pl-14 pr-14 py-4 bg-transparent text-lg text-gray-900 placeholder-gray-400 outline-none"
                  />

                  <button
                    v-if="searchQuery"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center"
                  >
                    <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>

                <!-- Search Stats -->
                <div v-if="searchQuery && !loading" class="mt-3 flex items-center gap-4 text-sm text-gray-600">
                  <span v-if="totalResults > 0">{{ totalResults }} résultat{{ totalResults > 1 ? 's' : '' }} trouvé{{ totalResults > 1 ? 's' : '' }}</span>
                  <span v-else>Aucun résultat</span>
                </div>
              </div>

              <!-- Loading -->
              <div v-if="loading" class="p-12 flex justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-moov-orange"></div>
              </div>

              <!-- Results -->
              <div v-else-if="searchQuery" class="max-h-[500px] overflow-y-auto">
                <!-- Points de Vente -->
                <div v-if="results.pdv.length > 0" class="border-b border-gray-200">
                  <div class="px-6 py-3 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-700">Points de vente ({{ results.pdv.length }})</h3>
                  </div>
                  <button
                    v-for="(item, index) in results.pdv"
                    :key="'pdv-' + item.id"
                    @click="goTo('pdv', item.id)"
                    class="w-full px-6 py-4 text-left hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                    :class="{ 'bg-moov-orange/5': highlightedIndex === `pdv-${index}` }"
                  >
                    <div class="flex items-start gap-4">
                      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900" v-html="highlight(item.point_name)"></p>
                        <p class="text-sm text-gray-600 mt-0.5">{{ item.flooz_number }} • {{ item.city }}</p>
                        <div class="flex items-center gap-2 mt-2">
                          <StatusBadge :status="item.status" type="pdv" />
                          <span v-if="authStore.isAdmin" class="text-xs text-gray-500">{{ item.organization?.name }}</span>
                        </div>
                      </div>
                    </div>
                  </button>
                </div>

                <!-- Utilisateurs -->
                <div v-if="results.users.length > 0 && authStore.isAdmin" class="border-b border-gray-200">
                  <div class="px-6 py-3 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-700">Utilisateurs ({{ results.users.length }})</h3>
                  </div>
                  <button
                    v-for="(item, index) in results.users"
                    :key="'user-' + item.id"
                    @click="goTo('user', item.id)"
                    class="w-full px-6 py-4 text-left hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                    :class="{ 'bg-moov-orange/5': highlightedIndex === `user-${index}` }"
                  >
                    <div class="flex items-start gap-4">
                      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ getInitials(item.name) }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900" v-html="highlight(item.name)"></p>
                        <p class="text-sm text-gray-600 mt-0.5">{{ item.email }}</p>
                        <div class="flex items-center gap-2 mt-2">
                          <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">
                            {{ item.role?.display_name }}
                          </span>
                          <StatusBadge :status="item.is_active ? 'active' : 'inactive'" type="user" />
                        </div>
                      </div>
                    </div>
                  </button>
                </div>

                <!-- Dealers -->
                <div v-if="results.dealers.length > 0 && authStore.isAdmin" class="border-b border-gray-200">
                  <div class="px-6 py-3 bg-gray-50">
                    <h3 class="text-sm font-bold text-gray-700">Dealers ({{ results.dealers.length }})</h3>
                  </div>
                  <button
                    v-for="(item, index) in results.dealers"
                    :key="'dealer-' + item.id"
                    @click="goTo('dealer', item.id)"
                    class="w-full px-6 py-4 text-left hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-b-0"
                    :class="{ 'bg-moov-orange/5': highlightedIndex === `dealer-${index}` }"
                  >
                    <div class="flex items-start gap-4">
                      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900" v-html="highlight(item.name)"></p>
                        <p class="text-sm text-gray-600 mt-0.5">{{ item.contact_email }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ item.pdv_count || 0 }} PDV</p>
                      </div>
                    </div>
                  </button>
                </div>

                <!-- No Results -->
                <div v-if="totalResults === 0" class="p-12 text-center">
                  <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </div>
                  <p class="text-gray-600 font-semibold">Aucun résultat trouvé</p>
                  <p class="text-gray-500 text-sm mt-1">Essayez avec d'autres mots-clés</p>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else class="p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-moov-orange/10 flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </div>
                <p class="text-gray-600 font-semibold mb-2">Recherche globale</p>
                <p class="text-gray-500 text-sm">Tapez pour rechercher des PDV, utilisateurs, ou dealers</p>
                <p class="text-gray-400 text-xs mt-4">
                  Raccourci: <kbd class="px-2 py-1 rounded bg-gray-100 font-mono">Ctrl+K</kbd>
                </p>
              </div>

              <!-- Footer -->
              <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-4 text-xs text-gray-500">
                  <span class="flex items-center gap-1">
                    <kbd class="px-2 py-1 rounded bg-white border border-gray-300">↑</kbd>
                    <kbd class="px-2 py-1 rounded bg-white border border-gray-300">↓</kbd>
                    Naviguer
                  </span>
                  <span class="flex items-center gap-1">
                    <kbd class="px-2 py-1 rounded bg-white border border-gray-300">↵</kbd>
                    Sélectionner
                  </span>
                  <span class="flex items-center gap-1">
                    <kbd class="px-2 py-1 rounded bg-white border border-gray-300">Esc</kbd>
                    Fermer
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import StatusBadge from '../components/StatusBadge.vue';
import { useAuthStore } from '../stores/auth';
import PointOfSaleService from '../services/PointOfSaleService';
import UserService from '../services/UserService';
import OrganizationService from '../services/OrganizationService';

const router = useRouter();
const authStore = useAuthStore();

const isSearchOpen = ref(false);
const searchQuery = ref('');
const searchInput = ref(null);
const loading = ref(false);
const highlightedIndex = ref(null);

const results = ref({
  pdv: [],
  users: [],
  dealers: []
});

let searchTimeout = null;

const totalResults = computed(() => {
  return results.value.pdv.length + results.value.users.length + results.value.dealers.length;
});

// Methods
const openSearch = () => {
  isSearchOpen.value = true;
  nextTick(() => {
    searchInput.value?.focus();
  });
};

const closeSearch = () => {
  isSearchOpen.value = false;
  searchQuery.value = '';
  results.value = { pdv: [], users: [], dealers: [] };
  highlightedIndex.value = null;
};

const clearSearch = () => {
  searchQuery.value = '';
  results.value = { pdv: [], users: [], dealers: [] };
  highlightedIndex.value = null;
};

const handleSearch = async () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  if (!searchQuery.value.trim()) {
    results.value = { pdv: [], users: [], dealers: [] };
    return;
  }

  searchTimeout = setTimeout(async () => {
    loading.value = true;
    
    try {
      const query = searchQuery.value.toLowerCase();
      
      // Search PDV
      const pdvData = await PointOfSaleService.getAll();
      results.value.pdv = pdvData.filter(p =>
        p.point_name?.toLowerCase().includes(query) ||
        p.flooz_number?.toLowerCase().includes(query) ||
        p.city?.toLowerCase().includes(query) ||
        p.region?.toLowerCase().includes(query)
      ).slice(0, 5);

      // Search Users (admin only)
      if (authStore.isAdmin) {
        const userData = await UserService.getAll();
        results.value.users = userData.filter(u =>
          u.name?.toLowerCase().includes(query) ||
          u.email?.toLowerCase().includes(query)
        ).slice(0, 5);

        // Search Dealers
        const dealerData = await OrganizationService.getAll();
        results.value.dealers = dealerData.filter(d =>
          d.name?.toLowerCase().includes(query) ||
          d.contact_email?.toLowerCase().includes(query)
        ).slice(0, 5);
      }
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      loading.value = false;
    }
  }, 300);
};

const highlight = (text) => {
  if (!searchQuery.value) return text;
  const regex = new RegExp(`(${searchQuery.value})`, 'gi');
  return text.replace(regex, '<mark class="bg-yellow-200 text-gray-900">$1</mark>');
};

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const goTo = (type, id) => {
  const routes = {
    pdv: `/pdv/${id}`,
    user: `/users`,
    dealer: `/dealers/${id}`
  };
  router.push(routes[type]);
  closeSearch();
};

const navigateResults = (direction) => {
  // TODO: Implement keyboard navigation
  console.log('Navigate:', direction);
};

const selectHighlighted = () => {
  // TODO: Select highlighted item
  console.log('Select highlighted');
};

// Keyboard shortcut (Ctrl+K)
const handleKeydown = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
    event.preventDefault();
    openSearch();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  // Auto-open search on mount
  openSearch();
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
kbd {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
}
</style>
