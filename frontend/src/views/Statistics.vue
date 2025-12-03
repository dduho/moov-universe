<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Statistiques & Rapports</h1>
        
        <!-- Period Selector -->
        <div class="flex items-center gap-3">
          <FormSelect
            v-model="selectedPeriod"
            :options="[
              { label: '7 derniers jours', value: '7' },
              { label: '30 derniers jours', value: '30' },
              { label: '90 derniers jours', value: '90' },
              { label: 'Année en cours', value: '365' }
            ]"
            option-label="label"
            option-value="value"
            @change="loadStatistics"
          />
          
          <button
            @click="exportData"
            class="px-4 py-2 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exporter
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card p-12 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-gray-600 font-semibold">Chargement des statistiques...</p>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <span class="text-2xl font-bold text-blue-600">{{ stats.totalPOS || 0 }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600">Total PDV</h3>
            <p class="text-xs text-gray-500 mt-1">{{ stats.activePOS || 0 }} actifs</p>
          </div>

          <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <span class="text-2xl font-bold text-green-600">{{ stats.validationRate || 0 }}%</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600">Taux de validation</h3>
            <p class="text-xs text-gray-500 mt-1">Sur les {{ selectedPeriod }} derniers jours</p>
          </div>

          <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <span class="text-2xl font-bold text-moov-orange">{{ stats.averageValidationTime || 0 }}h</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600">Délai moyen</h3>
            <p class="text-xs text-gray-500 mt-1">De validation</p>
          </div>

          <div class="glass-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <span class="text-2xl font-bold text-purple-600">{{ stats.totalDealers || 0 }}</span>
            </div>
            <h3 class="text-sm font-semibold text-gray-600">Dealers actifs</h3>
            <p class="text-xs text-gray-500 mt-1">{{ stats.newDealersThisMonth || 0 }} ce mois</p>
          </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- Evolution Chart -->
          <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
              Évolution des PDV
            </h2>
            <div class="h-80 flex items-center justify-center bg-gray-50 rounded-xl border border-gray-200">
              <div class="text-center text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
                <p class="text-sm font-semibold mb-2">Graphique en ligne</p>
                <p class="text-xs">Évolution du nombre de PDV créés par jour</p>
                <p class="text-xs text-moov-orange mt-2">Chart.js / ApexCharts requis</p>
              </div>
            </div>
          </div>

          <!-- Distribution by Region -->
          <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
              Distribution par région
            </h2>
            <div class="h-80">
              <div class="space-y-4">
                <div v-for="region in stats.regionDistribution" :key="region.name" class="space-y-2">
                  <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-gray-700">{{ region.name }}</span>
                    <span class="font-bold text-gray-900">{{ region.count }} ({{ region.percentage }}%)</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-3">
                    <div
                      class="bg-gradient-to-r from-moov-orange to-moov-orange-dark h-3 rounded-full transition-all duration-500"
                      :style="{ width: region.percentage + '%' }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- Status Distribution -->
          <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
              </svg>
              Répartition par statut
            </h2>
            <div class="h-80 flex items-center justify-center">
              <div class="w-full max-w-xs">
                <div class="space-y-4">
                  <div class="flex items-center justify-between p-4 rounded-xl bg-green-50 border border-green-200">
                    <div class="flex items-center gap-3">
                      <div class="w-4 h-4 rounded-full bg-green-500"></div>
                      <span class="font-semibold text-gray-700">Validés</span>
                    </div>
                    <span class="text-2xl font-bold text-green-600">{{ stats.validatedPOS || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between p-4 rounded-xl bg-yellow-50 border border-yellow-200">
                    <div class="flex items-center gap-3">
                      <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                      <span class="font-semibold text-gray-700">En attente</span>
                    </div>
                    <span class="text-2xl font-bold text-yellow-600">{{ stats.pendingPOS || 0 }}</span>
                  </div>
                  <div class="flex items-center justify-between p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-center gap-3">
                      <div class="w-4 h-4 rounded-full bg-red-500"></div>
                      <span class="font-semibold text-gray-700">Rejetés</span>
                    </div>
                    <span class="text-2xl font-bold text-red-600">{{ stats.rejectedPOS || 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Dealers -->
          <div class="glass-card p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
              </svg>
              Top 5 Dealers
            </h2>
            <div class="h-80 overflow-y-auto">
              <div class="space-y-3">
                <div
                  v-for="(dealer, index) in stats.topDealers"
                  :key="dealer.id"
                  class="flex items-center gap-4 p-4 rounded-xl bg-white border border-gray-200 hover:shadow-lg transition-all duration-200"
                >
                  <div
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                    :class="index === 0 ? 'bg-yellow-500' : index === 1 ? 'bg-gray-400' : index === 2 ? 'bg-orange-600' : 'bg-gray-300'"
                  >
                    {{ index + 1 }}
                  </div>
                  <div class="flex-1">
                    <p class="font-bold text-gray-900">{{ dealer.name }}</p>
                    <p class="text-xs text-gray-500">{{ dealer.region }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-2xl font-bold text-moov-orange">{{ dealer.pos_count }}</p>
                    <p class="text-xs text-gray-500">PDV</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Performance Table -->
        <div class="glass-card p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Performance mensuelle
          </h2>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Mois</th>
                  <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Créations</th>
                  <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Validations</th>
                  <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Rejets</th>
                  <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Taux validation</th>
                  <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Délai moyen</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="month in stats.monthlyPerformance"
                  :key="month.month"
                  class="border-b border-gray-100 hover:bg-white/50 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ month.month }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-blue-600">{{ month.created }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-green-600">{{ month.validated }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-red-600">{{ month.rejected }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-moov-orange">{{ month.validation_rate }}%</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ month.avg_time }}h</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Navbar from '../components/Navbar.vue';
import FormSelect from '../components/FormSelect.vue';
import StatisticsService from '../services/StatisticsService';

const loading = ref(true);
const selectedPeriod = ref('30');

const stats = ref({
  totalPOS: 0,
  activePOS: 0,
  validationRate: 0,
  averageValidationTime: 0,
  totalDealers: 0,
  newDealersThisMonth: 0,
  validatedPOS: 0,
  pendingPOS: 0,
  rejectedPOS: 0,
  regionDistribution: [
    { name: 'Maritime', count: 450, percentage: 35 },
    { name: 'Plateaux', count: 320, percentage: 25 },
    { name: 'Centrale', count: 256, percentage: 20 },
    { name: 'Kara', count: 154, percentage: 12 },
    { name: 'Savanes', count: 103, percentage: 8 }
  ],
  topDealers: [
    { id: 1, name: 'TOGO TELECOM SA', region: 'Maritime', pos_count: 125 },
    { id: 2, name: 'ATLANTIQUE TELECOM', region: 'Maritime', pos_count: 98 },
    { id: 3, name: 'TOGOCOM', region: 'Plateaux', pos_count: 87 },
    { id: 4, name: 'MOOV AFRICA', region: 'Centrale', pos_count: 76 },
    { id: 5, name: 'T-MONEY', region: 'Kara', pos_count: 54 }
  ],
  monthlyPerformance: [
    { month: 'Décembre 2025', created: 45, validated: 38, rejected: 5, validation_rate: 84, avg_time: 18 },
    { month: 'Novembre 2025', created: 78, validated: 72, rejected: 4, validation_rate: 92, avg_time: 15 },
    { month: 'Octobre 2025', created: 92, validated: 85, rejected: 6, validation_rate: 92, avg_time: 16 },
    { month: 'Septembre 2025', created: 67, validated: 61, rejected: 5, validation_rate: 91, avg_time: 14 },
    { month: 'Août 2025', created: 85, validated: 78, rejected: 7, validation_rate: 92, avg_time: 17 }
  ]
});

const loadStatistics = async () => {
  loading.value = true;
  try {
    const data = await StatisticsService.getOverview(selectedPeriod.value);
    // Merge with mock data
    stats.value = {
      ...stats.value,
      ...data
    };
  } catch (error) {
    console.error('Error loading statistics:', error);
  } finally {
    loading.value = false;
  }
};

const exportData = () => {
  alert('Fonctionnalité d\'export en développement. Formats disponibles : PDF, Excel');
};

onMounted(() => {
  loadStatistics();
});
</script>
