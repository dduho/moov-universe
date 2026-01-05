<template>
  <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 border-2 border-orange-200 shadow-xl rounded-2xl p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Prévisions CA</h3>
          <p class="text-xs text-gray-600">{{ forecast?.period?.month || 'Mois en cours' }}</p>
        </div>
      </div>
      <button
        @click="refreshForecast"
        :disabled="loading"
        class="p-2 rounded-lg hover:bg-orange-200 transition-colors"
        title="Actualiser"
      >
        <svg 
          class="w-5 h-5 text-orange-600 transition-transform"
          :class="{ 'animate-spin': loading }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !forecast" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-orange-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <svg class="w-12 h-12 mx-auto text-orange-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-gray-600">{{ error }}</p>
      <button 
        @click="loadForecast" 
        class="mt-3 text-sm font-semibold text-orange-600 hover:text-orange-700"
      >
        Réessayer
      </button>
    </div>

    <!-- Forecast Content -->
    <div v-else-if="forecast && forecast.period && forecast.current && forecast.projected">
      <!-- Progress Bar -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-700">Progression du mois</span>
          <span class="text-sm font-bold text-orange-600">{{ forecast.period?.progress_percentage || 0 }}%</span>
        </div>
        <div class="w-full bg-orange-200/50 rounded-full h-3 overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-orange-500 to-orange-600 rounded-full transition-all duration-500 ease-out"
            :style="{ width: `${forecast.period?.progress_percentage || 0}%` }"
          ></div>
        </div>
        <div class="flex items-center justify-between mt-1">
          <span class="text-xs text-gray-500">{{ forecast.period?.days_passed || 0 }} jours écoulés</span>
          <span class="text-xs text-gray-500">{{ forecast.period?.days_remaining || 0 }} jours restants</span>
        </div>
      </div>

      <!-- Current vs Projected -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <!-- Current CA -->
        <div class="bg-white/80 rounded-xl p-4 border border-orange-200">
          <p class="text-xs font-semibold text-gray-500 uppercase mb-1">CA Actuel</p>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(forecast.current?.ca || 0) }}</p>
          <p class="text-xs text-gray-600 mt-1">{{ formatCurrency(forecast.current?.daily_average || 0) }}/jour</p>
        </div>

        <!-- Projected CA -->
        <div class="bg-white/80 rounded-xl p-4 border-2 border-orange-400">
          <p class="text-xs font-semibold text-orange-600 uppercase mb-1">CA Projeté</p>
          <p class="text-2xl font-bold text-orange-600">{{ formatCurrency(forecast.projected?.ca || 0) }}</p>
          <div class="flex items-center gap-1 mt-1">
            <svg 
              v-if="forecast.projected?.trend === 'increasing'" 
              class="w-3 h-3 text-green-600" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg 
              v-else-if="forecast.projected?.trend === 'decreasing'" 
              class="w-3 h-3 text-red-600" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <span class="text-xs" :class="getTrendColor(forecast.projected?.trend || 'stable')">
              {{ getTrendLabel(forecast.projected?.trend || 'stable') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Comparison with Last Month -->
      <div class="bg-white/80 rounded-xl p-4 border border-orange-200 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">vs Mois Dernier</p>
            <p class="text-lg font-bold text-gray-900">{{ formatCurrency(forecast.comparison?.last_month_ca || 0) }}</p>
          </div>
          <div class="text-right">
            <div 
              class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-bold"
              :class="getGrowthBadgeClass(forecast.comparison?.growth_rate || 0)"
            >
              <span v-if="(forecast.comparison?.growth_rate || 0) > 0">+</span>
              <span>{{ (forecast.comparison?.growth_rate || 0).toFixed(1) }}%</span>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ getGrowthStatusLabel(forecast.comparison?.growth_status || 'stable') }}</p>
          </div>
        </div>
      </div>

      <!-- Confidence Level -->
      <div class="bg-white/80 rounded-xl p-4 border border-orange-200 mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold text-gray-700">Niveau de confiance</span>
          <span class="text-sm font-bold" :class="getConfidenceColor(forecast.projected?.confidence_level || 0)">
            {{ (forecast.projected?.confidence_level || 0).toFixed(0) }}%
          </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-500"
            :class="getConfidenceBarClass(forecast.projected?.confidence_level || 0)"
            :style="{ width: `${forecast.projected?.confidence_level || 0}%` }"
          ></div>
        </div>
      </div>

      <!-- Message -->
      <div class="bg-white/90 rounded-xl p-4 border-l-4 border-orange-500">
        <p class="text-sm text-gray-700 leading-relaxed">
          {{ forecast.message || 'Aucun message disponible.' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ForecastingService from '../services/forecastingService';
import { useToast } from '../composables/useToast';

const props = defineProps({
  scope: {
    type: String,
    default: 'global',
    validator: (val) => ['global', 'region', 'dealer', 'pdv'].includes(val)
  },
  entityId: {
    type: String,
    default: null
  },
  autoRefresh: {
    type: Boolean,
    default: false
  },
  refreshInterval: {
    type: Number,
    default: 300000 // 5 minutes
  }
});

const toast = useToast();
const forecast = ref(null);
const loading = ref(false);
const error = ref(null);

let refreshTimer = null;

const loadForecast = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ForecastingService.getForecast({
      scope: props.scope,
      entity_id: props.entityId
    });

    forecast.value = response.forecast;
  } catch (err) {
    console.error('Error loading forecast:', err);
    error.value = 'Impossible de charger les prévisions';
    toast.error('Erreur lors du chargement des prévisions');
  } finally {
    loading.value = false;
  }
};

const refreshForecast = async () => {
  if (!loading.value) {
    await loadForecast();
  }
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return '0 FCFA';
  return new Intl.NumberFormat('fr-FR', {
    style: 'decimal',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value) + ' FCFA';
};

const getTrendColor = (trend) => {
  return {
    'text-green-600': trend === 'increasing',
    'text-red-600': trend === 'decreasing',
    'text-gray-600': trend === 'stable'
  };
};

const getTrendLabel = (trend) => {
  const labels = {
    increasing: 'En hausse',
    decreasing: 'En baisse',
    stable: 'Stable'
  };
  return labels[trend] || trend;
};

const getGrowthBadgeClass = (growthRate) => {
  if (growthRate > 5) return 'bg-green-100 text-green-700';
  if (growthRate > 0) return 'bg-blue-100 text-blue-700';
  return 'bg-red-100 text-red-700';
};

const getGrowthStatusLabel = (status) => {
  const labels = {
    strong: 'Forte croissance',
    moderate: 'Croissance modérée',
    negative: 'Décroissance'
  };
  return labels[status] || status;
};

const getConfidenceColor = (confidence) => {
  if (confidence > 80) return 'text-green-600';
  if (confidence > 60) return 'text-yellow-600';
  return 'text-orange-600';
};

const getConfidenceBarClass = (confidence) => {
  if (confidence > 80) return 'bg-green-500';
  if (confidence > 60) return 'bg-yellow-500';
  return 'bg-orange-500';
};

onMounted(() => {
  loadForecast();

  if (props.autoRefresh) {
    refreshTimer = setInterval(loadForecast, props.refreshInterval);
  }
});

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
});
</script>
