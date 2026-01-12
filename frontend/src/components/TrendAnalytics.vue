<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Header avec contrôles -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 sm:p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="text-lg sm:text-xl font-bold text-white">Tendances Prédictives</h3>
          <p class="text-blue-100 text-sm mt-1">Analyse des performances futures et alertes proactives</p>
        </div>
        <div class="flex gap-2 mt-3 sm:mt-0">
          <select 
            v-model="selectedModel" 
            @change="fetchPredictions"
            class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          >
            <option value="linear">Linéaire</option>
            <option value="seasonal">Saisonnier</option>
            <option value="advanced">Avancé</option>
          </select>
          <button 
            @click="refreshData" 
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 text-sm bg-white text-blue-600 rounded-lg hover:bg-blue-50 disabled:opacity-50 transition-colors"
          >
            <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Actualiser
          </button>
        </div>
      </div>

      <!-- Métriques clés -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-6">
        <div class="bg-white bg-opacity-35 backdrop-blur-lg rounded-lg p-4 border-2 border-white border-opacity-60 shadow-xl">
          <div class="text-gray-700 text-xs font-semibold uppercase tracking-wide">Précision Modèle</div>
          <div class="text-3xl font-bold mt-2 bg-gradient-to-r from-blue-600 to-orange-500 bg-clip-text text-transparent">{{ modelAccuracy }}%</div>
        </div>
        <div class="bg-white bg-opacity-35 backdrop-blur-lg rounded-lg p-4 border-2 border-white border-opacity-60 shadow-xl">
          <div class="text-gray-700 text-xs font-semibold uppercase tracking-wide">Prédiction ROI</div>
          <div class="text-3xl font-bold mt-2 bg-gradient-to-r from-blue-600 to-orange-500 bg-clip-text text-transparent">{{ predictedROI }}%</div>
          <div class="text-gray-700 text-xs mt-1 font-medium">
            {{ roiTrendText }}
          </div>
        </div>
        <div class="bg-white bg-opacity-35 backdrop-blur-lg rounded-lg p-4 border-2 border-white border-opacity-60 shadow-xl">
          <div class="text-gray-700 text-xs font-semibold uppercase tracking-wide">Alertes Actives</div>
          <div class="text-3xl font-bold mt-2 bg-gradient-to-r from-blue-600 to-orange-500 bg-clip-text text-transparent">{{ alertsPagination.total_count }}</div>
        </div>
        <div class="bg-white bg-opacity-35 backdrop-blur-lg rounded-lg p-4 border-2 border-white border-opacity-60 shadow-xl">
          <div class="text-gray-700 text-xs font-semibold uppercase tracking-wide">Confiance</div>
          <div class="text-3xl font-bold mt-2 bg-gradient-to-r from-blue-600 to-orange-500 bg-clip-text text-transparent">{{ confidenceLevel }}%</div>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="p-4 sm:p-6">
      <!-- Onglets -->
      <div class="flex border-b border-gray-200 mb-6">
        <button 
          v-for="tab in tabs" 
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.key 
              ? 'border-blue-500 text-blue-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Contenu des onglets -->
      <!-- Onglet Prédictions -->
      <div v-if="activeTab === 'predictions'" class="space-y-6">
        <!-- Loader -->
        <div v-if="loadingPredictions" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-2 text-gray-600">Génération des prédictions...</span>
        </div>
        
        <template v-else>
          <!-- État vide -->
          <div v-if="predictions.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune prédiction disponible</h3>
            <p class="mt-1 text-sm text-gray-500">Les données historiques sont insuffisantes pour générer des prédictions.</p>
          </div>
          
          <!-- Graphique des prédictions -->
          <div v-else class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-lg font-semibold mb-4">Évolution Prédite du ROI</h4>
            <canvas ref="predictionChart" class="w-full" style="max-height: 400px;"></canvas>
          </div>

          <!-- Insights automatiques -->
          <div v-if="insights.length > 0" class="bg-blue-50 rounded-lg p-4">
            <h5 class="text-md font-medium text-blue-900 mb-3">🔍 Insights Automatiques</h5>
            <ul class="space-y-2">
              <li v-for="(insight, index) in insights" :key="index" class="text-sm text-blue-800">
                • {{ insight }}
              </li>
            </ul>
          </div>

          <!-- Contrôles de prédiction -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Période de prédiction</label>
              <select v-model="forecastDays" @change="fetchPredictions" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="7">7 jours</option>
                <option value="14">14 jours</option>
                <option value="30">30 jours</option>
                <option value="90">90 jours</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Niveau de confiance</label>
              <select v-model="confidenceLevel" @change="fetchPredictions" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="85">85%</option>
                <option value="90">90%</option>
                <option value="95">95%</option>
                <option value="99">99%</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Région (optionnel)</label>
              <select v-model="selectedRegion" @change="fetchPredictions" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">Toutes les régions</option>
                <option v-for="region in availableRegions" :key="region" :value="region">{{ region }}</option>
              </select>
            </div>
          </div>
        </template>
      </div>

      <!-- Onglet Tendances -->
      <div v-else-if="activeTab === 'trends'" class="space-y-6">
        <!-- Loader -->
        <div v-if="loadingTrends" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-2 text-gray-600">Analyse des tendances...</span>
        </div>
        
        <!-- Graphique des tendances -->
        <div v-else class="bg-gray-50 rounded-lg p-4">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-semibold">Analyse des Tendances</h4>
            <select v-model="trendPeriod" @change="fetchTrends" class="px-3 py-2 text-sm border border-gray-300 rounded-lg">
              <option value="weekly">Hebdomadaire</option>
              <option value="monthly">Mensuelle</option>
              <option value="quarterly">Trimestrielle</option>
              <option value="yearly">Annuelle</option>
            </select>
          </div>
          <canvas ref="trendsChart" class="w-full" style="max-height: 400px;"></canvas>
        </div>

        <!-- Analyse de saisonnalité -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="text-md font-medium mb-3">Patterns Saisonniers</h5>
            <div v-if="seasonalPatterns.length > 0" class="space-y-3">
              <div v-for="pattern in seasonalPatterns" :key="pattern.metric" class="flex justify-between">
                <span class="text-sm text-gray-600">{{ pattern.metric.toUpperCase() }}</span>
                <div class="flex items-center gap-2">
                  <div :class="getTrendIndicator(pattern.trend)" class="w-2 h-2 rounded-full"></div>
                  <span class="text-sm font-medium">{{ pattern.trend }}</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Aucun pattern saisonnier détecté
            </div>
          </div>

          <div class="bg-gray-50 rounded-lg p-4">
            <h5 class="text-md font-medium mb-3">Volatilité</h5>
            <div v-if="volatilityData && Object.keys(volatilityData).length > 0" class="space-y-3">\n              <div v-for="(value, metric) in volatilityData" :key="metric" class="flex justify-between">
                <span class="text-sm text-gray-600">{{ metric.toUpperCase() }}</span>
                <div class="flex items-center gap-2">
                  <div :class="getVolatilityColor(value)" class="w-2 h-2 rounded-full"></div>
                  <span class="text-sm font-medium">{{ value.toFixed(1) }}%</span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-500 text-sm">
              Aucune donnée de volatilité disponible
            </div>
          </div>
        </div>
      </div>

      <!-- Onglet Alertes -->
      <div v-else-if="activeTab === 'alerts'" class="space-y-6">
        <!-- Loader -->
        <div v-if="loadingAlerts" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-2 text-gray-600">Génération des alertes...</span>
        </div>
        
        <!-- Configuration des alertes -->
        <div v-else class="bg-gray-50 rounded-lg p-4">
          <h4 class="text-lg font-semibold mb-4">Configuration des Alertes</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Seuil ROI minimal (%)</label>
              <input 
                v-model.number="alertThresholds.roi" 
                @change="fetchAlerts"
                type="number" 
                min="0" 
                max="100" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Seuil de baisse (%)</label>
              <input 
                v-model.number="alertThresholds.decline" 
                @change="fetchAlerts"
                type="number" 
                min="0" 
                max="100" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg"
              >
            </div>
          </div>
        </div>

        <!-- Liste des alertes -->
        <div v-if="activeAlerts.length > 0" class="space-y-3">
          <div class="flex justify-between items-center mb-4">
            <h5 class="text-md font-medium">Alertes Actives ({{ alertsPagination.total_count }})</h5>
            <span class="text-sm text-gray-500">Page {{ alertsPagination.current_page }} sur {{ alertsPagination.total_pages }}</span>
          </div>
          
          <div 
            v-for="alert in activeAlerts" 
            :key="alert.id"
            :class="getAlertClass(alert.severity)"
            class="p-4 rounded-lg border-l-4"
          >
            <div class="flex justify-between items-start">
              <div>
                <h6 class="font-medium">{{ alert.title }}</h6>
                <p class="text-sm mt-1">{{ alert.description }}</p>
                <div class="flex items-center gap-4 mt-2 text-xs">
                  <span class="px-2 py-1 rounded-full text-white" :class="getSeverityBadge(alert.severity)">
                    {{ alert.severity.toUpperCase() }}
                  </span>
                  <span class="text-gray-500">{{ alert.pdv_name || 'Général' }}</span>
                  <span class="text-gray-500">{{ formatDate(alert.created_at) }}</span>
                </div>
              </div>
              <button 
                @click="dismissAlert(alert.id)"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Pagination controls -->
          <div v-if="alertsPagination.total_pages > 1" class="flex justify-center items-center gap-2 mt-6">
            <button 
              @click="prevPage"
              :disabled="!alertsPagination.has_prev"
              class="px-3 py-2 rounded-lg border transition-colors"
              :class="alertsPagination.has_prev 
                ? 'border-blue-500 text-blue-600 hover:bg-blue-50' 
                : 'border-gray-300 text-gray-400 cursor-not-allowed'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <!-- Page numbers -->
            <button 
              v-for="page in getVisiblePages()" 
              :key="page"
              @click="goToPage(page)"
              class="px-4 py-2 rounded-lg border transition-colors"
              :class="page === alertsPagination.current_page 
                ? 'bg-blue-500 border-blue-500 text-white' 
                : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
            >
              {{ page }}
            </button>

            <button 
              @click="nextPage"
              :disabled="!alertsPagination.has_next"
              class="px-3 py-2 rounded-lg border transition-colors"
              :class="alertsPagination.has_next 
                ? 'border-blue-500 text-blue-600 hover:bg-blue-50' 
                : 'border-gray-300 text-gray-400 cursor-not-allowed'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p>Aucune alerte active pour le moment</p>
          <p class="text-sm mt-1">Toutes les performances sont dans les normes</p>
        </div>
      </div>

      <!-- Onglet Recommandations -->
      <div v-else-if="activeTab === 'recommendations'" class="space-y-6">
        <!-- Loader -->
        <div v-if="loadingRecommendations" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
          <span class="ml-2 text-gray-600">Génération des recommandations...</span>
        </div>
        
        <!-- État vide -->
        <div v-else-if="recommendations.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
          <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune recommandation disponible</h3>
          <p class="mt-1 text-sm text-gray-500">Les performances actuelles semblent optimales, ou les données sont insuffisantes.</p>
        </div>
        
        <div v-else-if="recommendations.length > 0" class="space-y-4">
          <h4 class="text-lg font-semibold">Recommandations d'Optimisation</h4>
          
          <div 
            v-for="recommendation in recommendations" 
            :key="recommendation.id"
            class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-4"
          >
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0 mt-1">
                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
              </div>
              <div class="flex-1">
                <h6 class="font-medium text-green-900">{{ recommendation.title }}</h6>
                <p class="text-sm text-green-800 mt-1">{{ recommendation.description }}</p>
                
                <!-- Impact estimé -->
                <div v-if="recommendation.impact" class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-3">
                  <div class="text-center bg-white bg-opacity-50 rounded p-2">
                    <div class="text-xs text-green-600">ROI Impact</div>
                    <div class="text-sm font-medium text-green-900">+{{ recommendation.impact.roi }}%</div>
                  </div>
                  <div class="text-center bg-white bg-opacity-50 rounded p-2">
                    <div class="text-xs text-green-600">Revenue Impact</div>
                    <div class="text-sm font-medium text-green-900">+{{ recommendation.impact.revenue }}%</div>
                  </div>
                  <div class="text-center bg-white bg-opacity-50 rounded p-2">
                    <div class="text-xs text-green-600">Difficulté</div>
                    <div class="text-sm font-medium text-green-900">{{ recommendation.difficulty }}</div>
                  </div>
                </div>

                <!-- Actions suggérées -->
                <div v-if="recommendation.actions && recommendation.actions.length > 0" class="mt-3">
                  <div class="text-xs text-green-600 mb-2">Actions suggérées:</div>
                  <ul class="text-sm text-green-800 space-y-1">
                    <li v-for="(action, index) in recommendation.actions" :key="index" class="flex items-start gap-2">
                      <span class="text-green-500">•</span>
                      <span>{{ action }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
          </svg>
          <p>Aucune recommandation disponible</p>
          <p class="text-sm mt-1">Les performances actuelles sont optimales</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';
import PredictionService from '../services/predictionService';
import api from '../services/api';
import { useCacheStore } from '../composables/useCacheStore';

export default {
  name: 'TrendAnalytics',
  setup() {
    const { fetchWithCache } = useCacheStore();
    // Reactive data
    const loading = ref(false);
    const loadingPredictions = ref(false);
    const loadingAlerts = ref(false);
    const loadingRecommendations = ref(false);
    const activeTab = ref('predictions');
    const predictionChart = ref(null);
    
    // Configuration
    const selectedModel = ref('seasonal');
    const forecastDays = ref(30);
    const confidenceLevel = ref(85);
    const selectedRegion = ref('');
    
    // Data
    const predictions = ref([]);
    const activeAlerts = ref([]);
    const recommendations = ref([]);
    const insights = ref([]);
    const availableRegions = ref([]);
    
    // Computed metrics
    const modelAccuracy = ref(0);
    const predictedROI = ref(0);
    const roiTrendText = ref('');
    const roiTrendClass = ref('');
    
    // Alert configuration
    const alertThresholds = ref({
      roi: 50,
      decline: 20
    });

    // Pagination pour les alertes
    const alertsPagination = ref({
      current_page: 1,
      per_page: 15,
      total_pages: 1,
      total_count: 0,
      has_next: false,
      has_prev: false
    });

    // Chart instances
    let predictionChartInstance = null;

    const tabs = [
      { key: 'predictions', label: 'Prédictions' },
      { key: 'alerts', label: 'Alertes' },
      { key: 'recommendations', label: 'Recommandations' }
    ];

    // Methods
    const fetchPredictions = async () => {
      loadingPredictions.value = true;
      try {
        const params = {
          forecast_days: forecastDays.value,
          model_type: selectedModel.value,
          confidence_level: confidenceLevel.value / 100,
          region: selectedRegion.value || undefined
        };

        await fetchWithCache(
          'predictive-analytics/predictions',
          async () => {
            const response = await PredictionService.getPredictions(params);
            return response;
          },
          params,
          {
            onDataUpdate: async (data, fromCache) => {
              if (data && data.success) {
                predictions.value = data.data.forecasts || [];
                insights.value = data.data.insights || [];
                modelAccuracy.value = Math.round((data.data.accuracy || 0) * 100);
                updatePredictiveMetrics();
                // Attendre que le DOM soit mis à jour avant de créer le graphique
                await nextTick();
                await nextTick();
                updatePredictionChart();
              }
            }
          }
        );
      } catch (error) {
        console.error('Erreur lors de la récupération des prédictions:', error);
      } finally {
        loadingPredictions.value = false;
      }
    };

    const fetchAlerts = async (page = 1) => {
      loadingAlerts.value = true;
      try {
        const params = {
          threshold_roi: alertThresholds.value.roi,
          threshold_decline: alertThresholds.value.decline,
          forecast_days: 14,
          page: page,
          per_page: 15
        };

        await fetchWithCache(
          'predictive-analytics/alerts',
          async () => {
            const response = await PredictionService.getPredictiveAlerts(params);
            return response;
          },
          params,
          {
            onDataUpdate: (data, fromCache) => {
              if (data && data.success) {
                activeAlerts.value = data.alerts || [];
                
                // Mise à jour des infos de pagination
                if (data.data) {
                  alertsPagination.value = {
                    current_page: data.data.current_page || 1,
                    per_page: data.data.per_page || 15,
                    total_pages: data.data.total_pages || 1,
                    total_count: data.data.alert_count || 0,
                    has_next: data.data.has_next || false,
                    has_prev: data.data.has_prev || false
                  };
                }
              }
            }
          }
        );
      } catch (error) {
        console.error('Erreur lors de la récupération des alertes:', error);
      } finally {
        loadingAlerts.value = false;
      }
    };

    const goToPage = (page) => {
      if (page >= 1 && page <= alertsPagination.value.total_pages) {
        fetchAlerts(page);
      }
    };

    const nextPage = () => {
      if (alertsPagination.value.has_next) {
        fetchAlerts(alertsPagination.value.current_page + 1);
      }
    };

    const prevPage = () => {
      if (alertsPagination.value.has_prev) {
        fetchAlerts(alertsPagination.value.current_page - 1);
      }
    };

    const fetchRecommendations = async () => {
      loadingRecommendations.value = true;
      try {
        const params = {
          optimization_type: 'roi'
        };

        await fetchWithCache(
          'predictive-analytics/recommendations',
          async () => {
            const response = await PredictionService.getOptimizationRecommendations(params);
            return response;
          },
          params,
          {
            onDataUpdate: (data, fromCache) => {
              if (data && data.success) {
                recommendations.value = data.recommendations || [];
              }
            }
          }
        );
      } catch (error) {
        console.error('Erreur lors de la récupération des recommandations:', error);
      } finally {
        loadingRecommendations.value = false;
      }
    };

    const updatePredictiveMetrics = () => {
      if (predictions.value.length > 0) {
        const lastPrediction = predictions.value[predictions.value.length - 1];
        const firstPrediction = predictions.value[0];
        
        predictedROI.value = Math.round(lastPrediction.roi);
        
        const roiChange = lastPrediction.roi - firstPrediction.roi;
        if (roiChange > 0) {
          roiTrendText.value = `+${roiChange.toFixed(1)}% prévu`;
          roiTrendClass.value = 'text-green-200';
        } else {
          roiTrendText.value = `${roiChange.toFixed(1)}% prévu`;
          roiTrendClass.value = 'text-red-200';
        }
      }
    };

    const updatePredictionChart = () => {
      if (!predictionChart.value || predictions.value.length === 0) return;

      const ctx = predictionChart.value.getContext('2d');
      
      if (predictionChartInstance) {
        predictionChartInstance.destroy();
      }

      const labels = predictions.value.map(p => {
        const date = new Date(p.date);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
      });
      
      const roiData = predictions.value.map(p => p.roi);

      predictionChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'ROI Prédit (%)',
            data: roiData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: 'rgb(59, 130, 246)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top'
            },
            tooltip: {
              mode: 'index',
              intersect: false
            }
          },
          scales: {
            x: {
              title: {
                display: true,
                text: 'Date'
              }
            },
            y: {
              title: {
                display: true,
                text: 'ROI (%)'
              },
              beginAtZero: false
            }
          }
        }
      });
    };

    const updateTrendsChart = () => {
      if (!trendsChart.value || trends.value.length === 0) return;

      const ctx = trendsChart.value.getContext('2d');
      
      if (trendsChartInstance) {
        trendsChartInstance.destroy();
      }

      const labels = trends.value.map(t => {
        try {
          // Essayer de parser la période comme une date
          const date = new Date(t.period);
          if (!isNaN(date.getTime())) {
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
          }
          // Si ce n'est pas une date valide, retourner tel quel
          return t.period;
        } catch {
          return t.period;
        }
      });
      const roiData = trends.value.map(t => t.roi || 0);
      const caData = trends.value.map(t => t.ca || 0);

      trendsChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [
            {
              label: 'ROI (%)',
              data: roiData,
              borderColor: 'rgb(59, 130, 246)',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              yAxisID: 'y'
            },
            {
              label: 'CA (FCFA)',
              data: caData,
              borderColor: 'rgb(16, 185, 129)',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false
          },
          plugins: {
            legend: {
              position: 'top'
            }
          },
          scales: {
            x: {
              title: {
                display: true,
                text: 'Période'
              }
            },
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              title: {
                display: true,
                text: 'ROI (%)'
              }
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              title: {
                display: true,
                text: 'CA (FCFA)'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          }
        }
      });
    };

    const fetchRegions = async () => {
      try {
        const response = await api.get('/geography/regions');
        if (response.data && Array.isArray(response.data)) {
          availableRegions.value = response.data;
          console.log('[Regions] Régions chargées:', availableRegions.value);
        }
      } catch (error) {
        console.error('Erreur lors du chargement des régions:', error);
        // Valeurs par défaut en cas d'erreur
        availableRegions.value = [];
      }
    };

    const refreshData = async () => {
      await Promise.all([
        fetchPredictions(),
        fetchAlerts(),
        fetchRecommendations()
      ]);
    };

    // Helper methods
    const getTrendIndicator = (trend) => {
      if (trend === 'up' || trend === 'positive') return 'bg-green-400';
      if (trend === 'down' || trend === 'negative') return 'bg-red-400';
      return 'bg-yellow-400';
    };

    const getVolatilityColor = (value) => {
      if (value < 10) return 'bg-green-400';
      if (value < 20) return 'bg-yellow-400';
      return 'bg-red-400';
    };

    const getAlertClass = (severity) => {
      if (severity === 'critical') return 'bg-red-50 border-red-500';
      if (severity === 'warning') return 'bg-yellow-50 border-yellow-500';
      return 'bg-blue-50 border-blue-500';
    };

    const getSeverityBadge = (severity) => {
      if (severity === 'critical') return 'bg-red-500';
      if (severity === 'warning') return 'bg-yellow-500';
      return 'bg-blue-500';
    };

    const dismissAlert = (alertId) => {
      activeAlerts.value = activeAlerts.value.filter(alert => alert.id !== alertId);
    };

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('fr-FR');
    };

    const getVisiblePages = () => {
      const current = alertsPagination.value.current_page;
      const total = alertsPagination.value.total_pages;
      const maxVisible = 5;
      
      if (total <= maxVisible) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }
      
      const pages = [];
      const halfVisible = Math.floor(maxVisible / 2);
      
      let start = Math.max(1, current - halfVisible);
      let end = Math.min(total, start + maxVisible - 1);
      
      if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    };

    // Watchers
    watch(activeTab, (newTab) => {
      if (newTab === 'alerts') {
        fetchAlerts();
      } else if (newTab === 'recommendations') {
        fetchRecommendations();
      }
    });

    // Watch predictions to update chart when data changes AND DOM is ready
    watch(predictions, async (newPredictions) => {
      if (newPredictions.length > 0 && !loadingPredictions.value) {
        await nextTick();
        await nextTick(); // Double nextTick pour être sûr que le DOM est monté
        updatePredictionChart();
      }
    });

    // Lifecycle
    onMounted(() => {
      fetchRegions();
      refreshData();
    });

    return {
      // Refs
      loading,
      loadingPredictions,
      loadingAlerts,
      loadingRecommendations,
      activeTab,
      predictionChart,
      
      // Configuration
      selectedModel,
      forecastDays,
      confidenceLevel,
      selectedRegion,
      alertThresholds,
      alertsPagination,
      
      // Data
      predictions,
      activeAlerts,
      recommendations,
      insights,
      availableRegions,
      
      // Computed
      modelAccuracy,
      predictedROI,
      roiTrendText,
      roiTrendClass,
      
      // Constants
      tabs,
      
      // Methods
      fetchPredictions,
      fetchAlerts,
      fetchRecommendations,
      fetchRegions,
      refreshData,
      getAlertClass,
      getSeverityBadge,
      dismissAlert,
      formatDate,
      getVisiblePages,
      goToPage,
      nextPage,
      prevPage
    };
  }
};
</script>

<style scoped>
/* Styles spécifiques au composant si nécessaire */
</style>