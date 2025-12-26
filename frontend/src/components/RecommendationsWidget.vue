<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Recommandations</h3>
          <p class="text-sm text-gray-600">Actions suggérées pour améliorer les performances</p>
        </div>
      </div>

      <!-- Summary Badges -->
      <div v-if="!loading && recommendations" class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
          {{ recommendations.summary.high_priority_count }} Priorité haute
        </span>
        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
          {{ recommendations.summary.total_pdv_actions + recommendations.summary.total_dealer_actions }} Total
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-purple-500 mx-auto mb-4"></div>
      <p class="text-sm text-gray-600 font-semibold">Génération des recommandations AI...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <svg class="w-12 h-12 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-red-600 font-semibold">{{ error }}</p>
      <button @click="loadRecommendations" class="mt-4 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else-if="recommendations" class="space-y-6">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-gray-200">
        <button
          @click="activeTab = 'pdv'"
          :class="[
            'px-4 py-2 font-semibold text-sm transition-all relative',
            activeTab === 'pdv'
              ? 'text-purple-600 border-b-2 border-purple-600'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          PDV ({{ recommendations.pdv_recommendations.length }})
        </button>
        <button
          @click="activeTab = 'dealer'"
          :class="[
            'px-4 py-2 font-semibold text-sm transition-all relative',
            activeTab === 'dealer'
              ? 'text-purple-600 border-b-2 border-purple-600'
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          Dealers ({{ recommendations.dealer_recommendations.length }})
        </button>
      </div>

      <!-- PDV Recommendations -->
      <div v-if="activeTab === 'pdv'" class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
        <div v-if="recommendations.pdv_recommendations.length === 0" class="text-center py-8">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-600 font-semibold">Aucune recommandation pour le moment</p>
          <p class="text-sm text-gray-500 mt-1">Tous les PDV sont performants !</p>
        </div>

        <div
          v-for="rec in recommendations.pdv_recommendations"
          :key="rec.pdv_id"
          class="group rounded-xl border-2 p-5 transition-all hover:shadow-lg"
          :class="{
            'border-red-200 bg-red-50/50': rec.priority === 'high',
            'border-yellow-200 bg-yellow-50/50': rec.priority === 'medium',
            'border-blue-200 bg-blue-50/50': rec.priority === 'low'
          }"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="text-lg font-bold text-gray-900">{{ rec.pdv_name }}</h4>
                <span class="text-xs font-mono text-gray-500">{{ rec.pdv_numero }}</span>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-bold"
                  :class="{
                    'bg-red-100 text-red-700': rec.priority === 'high',
                    'bg-yellow-100 text-yellow-700': rec.priority === 'medium',
                    'bg-blue-100 text-blue-700': rec.priority === 'low'
                  }"
                >
                  {{ rec.priority === 'high' ? 'Haute' : rec.priority === 'medium' ? 'Moyenne' : 'Basse' }}
                </span>
              </div>
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  </svg>
                  {{ rec.region }}
                </span>
                <span class="flex items-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  {{ rec.dealer_name }}
                </span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div class="px-3 py-1 rounded-lg bg-purple-100">
                <span class="text-xs text-purple-600 font-semibold">Impact</span>
                <span class="text-lg font-bold text-purple-700 ml-1">{{ rec.impact_score }}</span>
              </div>
              <span
                class="px-2 py-1 rounded text-xs font-semibold"
                :class="{
                  'bg-orange-100 text-orange-700': rec.action_type === 'reactivation',
                  'bg-blue-100 text-blue-700': rec.action_type === 'growth_opportunity',
                  'bg-red-100 text-red-700': rec.action_type === 'performance_improvement',
                  'bg-teal-100 text-teal-700': rec.action_type === 'balance_optimization',
                  'bg-green-100 text-green-700': rec.action_type === 'best_practice'
                }"
              >
                {{ getActionTypeLabel(rec.action_type) }}
              </span>
            </div>
          </div>

          <!-- Title & Description -->
          <div class="mb-4">
            <h5 class="text-base font-bold text-gray-900 mb-2">{{ rec.title }}</h5>
            <p class="text-sm text-gray-700 leading-relaxed">{{ rec.description }}</p>
          </div>

          <!-- Recommended Actions -->
          <div class="mb-4">
            <p class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Actions recommandées</p>
            <ul class="space-y-2">
              <li
                v-for="(action, idx) in rec.recommended_actions"
                :key="idx"
                class="flex items-start gap-2 text-sm text-gray-700"
              >
                <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ action }}</span>
              </li>
            </ul>
          </div>

          <!-- Expected Outcome -->
          <div class="pt-4 border-t border-gray-200">
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold text-gray-700">Résultat attendu :</span>
              <span class="text-gray-600">{{ rec.expected_outcome }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Dealer Recommendations -->
      <div v-if="activeTab === 'dealer'" class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
        <div v-if="recommendations.dealer_recommendations.length === 0" class="text-center py-8">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-gray-600 font-semibold">Aucune recommandation pour le moment</p>
          <p class="text-sm text-gray-500 mt-1">Tous les dealers sont performants !</p>
        </div>

        <div
          v-for="rec in recommendations.dealer_recommendations"
          :key="rec.dealer_id"
          class="group rounded-xl border-2 p-5 transition-all hover:shadow-lg"
          :class="{
            'border-red-200 bg-red-50/50': rec.priority === 'high',
            'border-yellow-200 bg-yellow-50/50': rec.priority === 'medium',
            'border-blue-200 bg-blue-50/50': rec.priority === 'low'
          }"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="text-lg font-bold text-gray-900">{{ rec.dealer_name }}</h4>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-bold"
                  :class="{
                    'bg-red-100 text-red-700': rec.priority === 'high',
                    'bg-yellow-100 text-yellow-700': rec.priority === 'medium',
                    'bg-blue-100 text-blue-700': rec.priority === 'low'
                  }"
                >
                  {{ rec.priority === 'high' ? 'Haute' : rec.priority === 'medium' ? 'Moyenne' : 'Basse' }}
                </span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div class="px-3 py-1 rounded-lg bg-purple-100">
                <span class="text-xs text-purple-600 font-semibold">Impact</span>
                <span class="text-lg font-bold text-purple-700 ml-1">{{ rec.impact_score }}</span>
              </div>
              <span
                class="px-2 py-1 rounded text-xs font-semibold"
                :class="{
                  'bg-orange-100 text-orange-700': rec.action_type === 'activation',
                  'bg-blue-100 text-blue-700': rec.action_type === 'expansion',
                  'bg-teal-100 text-teal-700': rec.action_type === 'diversification',
                  'bg-green-100 text-green-700': rec.action_type === 'recognition'
                }"
              >
                {{ getDealerActionTypeLabel(rec.action_type) }}
              </span>
            </div>
          </div>

          <!-- Title & Description -->
          <div class="mb-4">
            <h5 class="text-base font-bold text-gray-900 mb-2">{{ rec.title }}</h5>
            <p class="text-sm text-gray-700 leading-relaxed">{{ rec.description }}</p>
          </div>

          <!-- Recommended Actions -->
          <div class="mb-4">
            <p class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2">Actions recommandées</p>
            <ul class="space-y-2">
              <li
                v-for="(action, idx) in rec.recommended_actions"
                :key="idx"
                class="flex items-start gap-2 text-sm text-gray-700"
              >
                <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ action }}</span>
              </li>
            </ul>
          </div>

          <!-- Expected Outcome -->
          <div class="pt-4 border-t border-gray-200">
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-semibold text-gray-700">Résultat attendu :</span>
              <span class="text-gray-600">{{ rec.expected_outcome }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Refresh Info -->
      <div class="text-center pt-4 border-t border-gray-200">
        <p class="text-xs text-gray-500">
          Généré le {{ formatDateTime(recommendations.generated_at) }}
          <button @click="loadRecommendations" class="ml-2 text-purple-600 hover:text-purple-700 font-semibold">
            Actualiser
          </button>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import RecommendationsService from '../services/recommendationsService';
import { useToast } from '../composables/useToast';

const props = defineProps({
  scope: {
    type: String,
    default: 'global',
    validator: (value) => ['global', 'region', 'dealer', 'pdv'].includes(value)
  },
  entityId: {
    type: Number,
    default: null
  },
  limit: {
    type: Number,
    default: 10
  },
  autoRefresh: {
    type: Boolean,
    default: false
  },
  refreshInterval: {
    type: Number,
    default: 600000 // 10 minutes
  }
});

const { showToast } = useToast();
const loading = ref(false);
const error = ref(null);
const recommendations = ref(null);
const activeTab = ref('pdv');
let refreshTimer = null;

const loadRecommendations = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await RecommendationsService.getRecommendations({
      scope: props.scope,
      entity_id: props.entityId,
      limit: props.limit
    });

    if (response.success) {
      recommendations.value = response.data;
    } else {
      throw new Error('Erreur lors du chargement des recommandations');
    }
  } catch (err) {
    console.error('Error loading recommendations:', err);
    error.value = err.response?.data?.message || 'Impossible de charger les recommandations';
    if (showToast) {
      showToast('Erreur de chargement des recommandations', 'error');
    }
  } finally {
    loading.value = false;
  }
};

const getActionTypeLabel = (type) => {
  const labels = {
    reactivation: 'Réactivation',
    performance_improvement: 'Amélioration',
    growth_opportunity: 'Croissance',
    balance_optimization: 'Optimisation',
    best_practice: 'Bonne pratique'
  };
  return labels[type] || type;
};

const getDealerActionTypeLabel = (type) => {
  const labels = {
    activation: 'Activation',
    expansion: 'Expansion',
    diversification: 'Diversification',
    recognition: 'Reconnaissance'
  };
  return labels[type] || type;
};

const formatDateTime = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  loadRecommendations();

  if (props.autoRefresh && props.refreshInterval > 0) {
    refreshTimer = setInterval(loadRecommendations, props.refreshInterval);
  }
});

// Cleanup
if (refreshTimer) {
  clearInterval(refreshTimer);
}
</script>
