<template>
  <Teleport to="body">
    <Transition name="search-modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-20 sm:pt-32 px-4"
        @click="closeSearch"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

        <!-- Search Container -->
        <div
          class="relative w-full max-w-3xl bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/40 overflow-hidden"
          @click.stop
        >
          <!-- Search Input -->
          <div class="flex items-center gap-4 px-6 py-5 border-b border-gray-200/60">
            <svg class="w-6 h-6 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher PDV, Dealers, Régions, Villes..."
              class="flex-1 bg-transparent text-lg font-semibold text-gray-900 placeholder-gray-400 outline-none"
              @keydown.escape="closeSearch"
              @keydown.down.prevent="navigateResults(1)"
              @keydown.up.prevent="navigateResults(-1)"
              @keydown.enter.prevent="selectHighlighted"
            />
            <button
              @click="closeSearch"
              class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
              title="Fermer (Esc)"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Search Results / Suggestions -->
          <div class="max-h-[60vh] overflow-y-auto">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-moov-orange"></div>
            </div>

            <!-- No Query State -->
            <div v-else-if="!searchQuery.trim()" class="px-6 py-8">
              <p class="text-center text-sm font-semibold text-gray-400 mb-6">
                Commencez à taper pour rechercher...
              </p>
              <div class="grid grid-cols-2 gap-3">
                <button
                  v-for="quickSearch in quickSearches"
                  :key="quickSearch.label"
                  @click="applyQuickSearch(quickSearch)"
                  class="flex items-center gap-3 p-4 rounded-xl border-2 border-gray-200 hover:border-moov-orange hover:bg-moov-orange/5 transition-all group"
                >
                  <component :is="quickSearch.icon" class="w-5 h-5 text-gray-400 group-hover:text-moov-orange" />
                  <span class="text-sm font-semibold text-gray-700 group-hover:text-moov-orange">{{ quickSearch.label }}</span>
                </button>
              </div>
            </div>

            <!-- Suggestions -->
            <div v-else-if="suggestions.length > 0" class="py-2">
              <div
                v-for="(suggestion, index) in suggestions"
                :key="`${suggestion.type}-${suggestion.id || suggestion.label}`"
                :ref="el => { if (el) resultRefs[index] = el }"
                @click="selectSuggestion(suggestion)"
                @mouseenter="highlightedIndex = index"
                class="flex items-center gap-4 px-6 py-4 cursor-pointer transition-all"
                :class="{
                  'bg-moov-orange/10': highlightedIndex === index,
                  'hover:bg-gray-50': highlightedIndex !== index
                }"
              >
                <!-- Icon based on type -->
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full" :class="getTypeColor(suggestion.type)">
                  <component :is="getTypeIcon(suggestion.type)" class="w-5 h-5" />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold text-gray-900 truncate">{{ suggestion.label }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ suggestion.subtitle }}</p>
                </div>

                <!-- Type Badge -->
                <span class="shrink-0 px-2 py-1 text-xs font-bold rounded-full" :class="getTypeBadge(suggestion.type)">
                  {{ getTypeLabel(suggestion.type) }}
                </span>
              </div>
            </div>

            <!-- No Results -->
            <div v-else-if="!loading && searchQuery.trim()" class="px-6 py-12">
              <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-lg font-bold text-gray-600 mb-2">Aucun résultat</p>
                <p class="text-sm text-gray-500">
                  Essayez avec un autre terme de recherche
                </p>
              </div>
            </div>
          </div>

          <!-- Footer Shortcuts -->
          <div class="px-6 py-4 border-t border-gray-200/60 bg-gray-50/50">
            <div class="flex items-center justify-between text-xs font-semibold text-gray-500">
              <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                  <kbd class="px-2 py-1 bg-white border border-gray-300 rounded text-xs">↑</kbd>
                  <kbd class="px-2 py-1 bg-white border border-gray-300 rounded text-xs">↓</kbd>
                  Naviguer
                </span>
                <span class="flex items-center gap-1.5">
                  <kbd class="px-2 py-1 bg-white border border-gray-300 rounded text-xs">Enter</kbd>
                  Sélectionner
                </span>
              </div>
              <span class="flex items-center gap-1.5">
                <kbd class="px-2 py-1 bg-white border border-gray-300 rounded text-xs">Esc</kbd>
                Fermer
              </span>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted, h } from 'vue';
import { useRouter } from 'vue-router';
import GlobalSearchService from '../services/globalSearchService';
import { useToast } from '../composables/useToast';

const router = useRouter();
const toast = useToast();

const isOpen = ref(false);
const searchQuery = ref('');
const suggestions = ref([]);
const loading = ref(false);
const highlightedIndex = ref(0);
const searchInputRef = ref(null);
const resultRefs = ref([]);

let debounceTimer = null;

// Icon Components (définis avant quickSearches)
const CheckCircleIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const ClockIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const MapPinIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' }),
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 11a3 3 0 11-6 0 3 3 0 016 0z' })
    ]);
  }
};

const BuildingIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
    ]);
  }
};

const ShoppingBagIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z' })
    ]);
  }
};

const GlobeIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

// Quick searches (définis après les icônes)
const quickSearches = [
  { label: 'PDV validés', type: 'pdv', filters: { status: 'validated' }, icon: CheckCircleIcon },
  { label: 'PDV en attente', type: 'pdv', filters: { status: 'pending' }, icon: ClockIcon },
  { label: 'Région Maritime', type: 'region', value: 'MARITIME', icon: MapPinIcon },
  { label: 'Tous les dealers', type: 'dealer', icon: BuildingIcon },
];

// Open/Close handlers
const openSearch = () => {
  isOpen.value = true;
  nextTick(() => {
    searchInputRef.value?.focus();
  });
};

const closeSearch = () => {
  isOpen.value = false;
  searchQuery.value = '';
  suggestions.value = [];
  highlightedIndex.value = 0;
};

// Search with debounce
watch(searchQuery, (newQuery) => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }

  if (!newQuery.trim() || newQuery.trim().length < 2) {
    suggestions.value = [];
    return;
  }

  debounceTimer = setTimeout(async () => {
    await fetchSuggestions(newQuery.trim());
  }, 300);
});

// Fetch suggestions from API
const fetchSuggestions = async (query) => {
  try {
    loading.value = true;
    const response = await GlobalSearchService.getSuggestions(query, 10);
    suggestions.value = response.suggestions || [];
    highlightedIndex.value = 0;
  } catch (error) {
    console.error('Search error:', error);
    toast.error('Erreur lors de la recherche');
  } finally {
    loading.value = false;
  }
};

// Navigation with keyboard
const navigateResults = (direction) => {
  if (suggestions.value.length === 0) return;

  highlightedIndex.value = Math.max(
    0,
    Math.min(suggestions.value.length - 1, highlightedIndex.value + direction)
  );

  // Scroll into view
  nextTick(() => {
    const element = resultRefs.value[highlightedIndex.value];
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  });
};

// Select highlighted result
const selectHighlighted = () => {
  if (suggestions.value.length > 0 && highlightedIndex.value >= 0) {
    selectSuggestion(suggestions.value[highlightedIndex.value]);
  }
};

// Select a suggestion
const selectSuggestion = (suggestion) => {
  closeSearch();
  
  if (suggestion.url) {
    router.push(suggestion.url);
  } else if (suggestion.type === 'region') {
    router.push(`/pdv/list?region=${suggestion.label}`);
  } else {
    toast.info(`Navigation vers ${suggestion.label}`);
  }
};

// Apply quick search
const applyQuickSearch = (quickSearch) => {
  if (quickSearch.type === 'pdv') {
    const params = new URLSearchParams(quickSearch.filters).toString();
    router.push(`/pdv/list?${params}`);
  } else if (quickSearch.type === 'dealer') {
    router.push('/dealers');
  } else if (quickSearch.type === 'region') {
    router.push(`/pdv/list?region=${quickSearch.value}`);
  }
  closeSearch();
};

// Helper functions
const getTypeIcon = (type) => {
  const icons = {
    pdv: ShoppingBagIcon,
    dealer: BuildingIcon,
    region: MapPinIcon,
    prefecture: MapPinIcon,
    commune: MapPinIcon,
    ville: GlobeIcon,
  };
  return icons[type] || ShoppingBagIcon;
};

const getTypeColor = (type) => {
  const colors = {
    pdv: 'bg-blue-100 text-blue-600',
    dealer: 'bg-purple-100 text-purple-600',
    region: 'bg-green-100 text-green-600',
    prefecture: 'bg-yellow-100 text-yellow-600',
    commune: 'bg-orange-100 text-orange-600',
    ville: 'bg-pink-100 text-pink-600',
  };
  return colors[type] || 'bg-gray-100 text-gray-600';
};

const getTypeBadge = (type) => {
  const badges = {
    pdv: 'bg-blue-100 text-blue-700',
    dealer: 'bg-purple-100 text-purple-700',
    region: 'bg-green-100 text-green-700',
    prefecture: 'bg-yellow-100 text-yellow-700',
    commune: 'bg-orange-100 text-orange-700',
    ville: 'bg-pink-100 text-pink-700',
  };
  return badges[type] || 'bg-gray-100 text-gray-700';
};

const getTypeLabel = (type) => {
  const labels = {
    pdv: 'PDV',
    dealer: 'Dealer',
    region: 'Région',
    prefecture: 'Préfecture',
    commune: 'Commune',
    ville: 'Ville',
  };
  return labels[type] || type;
};

// Listen for global open event
onMounted(() => {
  window.addEventListener('open-global-search', openSearch);
});

onUnmounted(() => {
  window.removeEventListener('open-global-search', openSearch);
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
});
</script>

<style scoped>
.search-modal-enter-active,
.search-modal-leave-active {
  transition: opacity 0.2s ease;
}

.search-modal-enter-from,
.search-modal-leave-to {
  opacity: 0;
}

.search-modal-enter-active .relative,
.search-modal-leave-active .relative {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.search-modal-enter-from .relative {
  transform: scale(0.95) translateY(-20px);
  opacity: 0;
}

.search-modal-leave-to .relative {
  transform: scale(0.95) translateY(-20px);
  opacity: 0;
}

kbd {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>


