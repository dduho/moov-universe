<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Détection de Fraude</h3>
          <p class="text-sm text-gray-600">Alertes basées sur patterns suspects</p>
        </div>
      </div>

      <!-- Summary Badges -->
      <div v-if="!loading && summary" class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
          {{ summary.high_risk }} Risque élevé
        </span>
        <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
          {{ summary.medium_risk }} Risque moyen
        </span>
        <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
          {{ summary.total_alerts }} Total
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-red-500 mx-auto mb-4"></div>
      <p class="text-sm text-gray-600 font-semibold">Analyse des patterns de fraude...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12">
      <svg class="w-12 h-12 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-red-600 font-semibold">{{ error }}</p>
      <button @click="loadFraudData" class="mt-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
        Réessayer
      </button>
    </div>

    <!-- Content -->
    <div v-else-if="alerts && alerts.length > 0" class="space-y-4">
      <!-- Filters -->
      <div class="flex flex-wrap gap-2 mb-3">
        <button
          v-for="level in ['all', 'high', 'medium', 'low']"
          :key="level"
          @click="filterLevel = level"
          :class="[
            'px-3 py-1 rounded-lg text-sm font-semibold transition-all',
            filterLevel === level
              ? 'bg-red-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ level === 'all' ? 'Tous' : level === 'high' ? 'Élevé' : level === 'medium' ? 'Moyen' : 'Faible' }}
        </button>
      </div>

      <!-- Type Filters -->
      <div class="flex flex-wrap gap-2 mb-4">
        <button
          v-for="type in availableTypes"
          :key="type"
          @click="filterType = type"
          :class="[
            'px-3 py-1 rounded-lg text-sm font-semibold transition-all',
            filterType === type
              ? 'bg-blue-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ type === 'all' ? 'Tous les types' : getAlertTypeLabel(type) }}
        </button>
      </div>

      <!-- Alerts List -->
      <div class="space-y-4">
        <div
          v-for="alert in paginatedAlerts"
          :key="`${alert.pdv_id}-${alert.date}-${alert.type}`"
          class="rounded-xl border-2 p-5 transition-all hover:shadow-lg"
          :class="{
            'border-red-200 bg-red-50/50': alert.risk_level === 'high',
            'border-yellow-200 bg-yellow-50/50': alert.risk_level === 'medium',
            'border-blue-200 bg-blue-50/50': alert.risk_level === 'low'
          }"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h4 class="text-lg font-bold text-gray-900">{{ alert.pdv_name }}</h4>
                <span class="text-xs font-mono text-gray-500">{{ alert.pdv_numero }}</span>
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-bold"
                  :class="{
                    'bg-red-100 text-red-700': alert.risk_level === 'high',
                    'bg-yellow-100 text-yellow-700': alert.risk_level === 'medium',
                    'bg-blue-100 text-blue-700': alert.risk_level === 'low'
                  }"
                >
                  Risque {{ alert.risk_level === 'high' ? 'Élevé' : alert.risk_level === 'medium' ? 'Moyen' : 'Faible' }}
                </span>
              </div>
              <div class="flex items-center gap-3 text-sm text-gray-600">
                <span>{{ alert.region }}</span>
                <span>•</span>
                <span>{{ alert.dealer_name }}</span>
                <span>•</span>
                <span>{{ formatDate(alert.date) }}</span>
              </div>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div class="px-3 py-1 rounded-lg bg-red-100">
                <span class="text-xs text-red-600 font-semibold">Score</span>
                <span class="text-lg font-bold text-red-700 ml-1">{{ alert.risk_score }}</span>
              </div>
              <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs font-semibold">
                {{ getAlertTypeLabel(alert.type) }}
              </span>
            </div>
          </div>

          <!-- Description -->
          <p class="text-sm text-gray-700 mb-3">{{ alert.description }}</p>

          <!-- Amount (if present) -->
          <div v-if="alert.flagged_amount > 0" class="flex items-center gap-2 text-sm text-gray-700">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold">{{ formatAmount(alert.flagged_amount) }} FCFA</span>
          </div>
        </div>

        <!-- Empty state for filtered results -->
        <div v-if="filteredAlerts.length === 0" class="text-center py-8">
          <p class="text-gray-600">Aucune alerte pour ce filtre</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-6">
        <button
          @click="currentPage = 1"
          :disabled="currentPage === 1"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          :class="currentPage === 1 ? 'bg-gray-100 text-gray-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
        >
          ‹‹
        </button>
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          :class="currentPage === 1 ? 'bg-gray-100 text-gray-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
        >
          ‹
        </button>
        
        <div class="flex gap-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="currentPage = page"
            class="w-10 h-10 rounded-lg text-sm font-semibold transition-all"
            :class="currentPage === page ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          :class="currentPage === totalPages ? 'bg-gray-100 text-gray-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
        >
          ›
        </button>
        <button
          @click="currentPage = totalPages"
          :disabled="currentPage === totalPages"
          class="px-3 py-2 rounded-lg text-sm font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          :class="currentPage === totalPages ? 'bg-gray-100 text-gray-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'"
        >
          ››
        </button>
      </div>

      <!-- Pagination Info -->
      <div v-if="filteredAlerts.length > 0" class="text-center mt-4 text-sm text-gray-600">
        Affichage de {{ startIndex + 1 }}-{{ endIndex }} sur {{ filteredAlerts.length }} alerte(s)
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore && !loading" class="text-center mt-4">
        <button
          @click="loadMoreAlerts"
          class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold transition-colors"
        >
          Charger plus d'alertes
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg class="w-16 h-16 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
      </svg>
      <p class="text-gray-600 font-semibold">Aucune fraude détectée</p>
      <p class="text-sm text-gray-500 mt-1">Tout semble normal pour la période analysée</p>
    </div>

    <!-- Footer -->
    <div v-if="!loading && alerts" class="mt-6 pt-4 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500">
      <span>Dernière analyse: {{ generatedAt }}</span>
      <button @click="loadFraudData" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 font-semibold transition-colors">
        Actualiser
      </button>
    </div>
  </div>
</template>

<script>
import fraudDetectionService from '../services/fraudDetectionService';

export default {
  name: 'FraudDetectionWidget',
  props: {
    scope: {
      type: String,
      default: 'global' // global, dealer, pdv
    },
    entityId: {
      type: Number,
      default: null
    },
    startDate: {
      type: String,
      default: null
    },
    endDate: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      loading: false,
      error: null,
      summary: null,
      alerts: [],
      generatedAt: null,
      filterLevel: 'all',
      filterType: 'all',
      currentPage: 1,
      itemsPerPage: 10,
      hasMore: false,
      apiOffset: 0
    };
  },
  computed: {
    filteredAlerts() {
      let list = this.alerts;
      if (this.filterLevel !== 'all') {
        list = list.filter(alert => alert.risk_level === this.filterLevel);
      }
      if (this.filterType !== 'all') {
        list = list.filter(alert => alert.type === this.filterType);
      }
      return list;
    },
    availableTypes() {
      const types = new Set(this.alerts.map(a => a.type));
      return ['all', ...types];
    },
    totalPages() {
      return Math.ceil(this.filteredAlerts.length / this.itemsPerPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredAlerts.length);
    },
    paginatedAlerts() {
      return this.filteredAlerts.slice(this.startIndex, this.endIndex);
    },
    visiblePages() {
      const pages = [];
      const maxVisible = 5;
      let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
      let end = Math.min(this.totalPages, start + maxVisible - 1);
      
      if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      return pages;
    }
  },
  mounted() {
    this.loadFraudData();
  },
  watch: {
    filterLevel() {
      this.currentPage = 1;
    },
    filterType() {
      this.currentPage = 1;
    }
  },
  methods: {
    async loadFraudData(loadMore = false) {
      this.loading = true;
      this.error = null;

      try {
        const params = {
          scope: this.scope,
          entity_id: this.entityId,
          start_date: this.startDate,
          end_date: this.endDate,
          limit: 30,
          offset: loadMore ? this.apiOffset : 0
        };

        const data = await fraudDetectionService.detectFraud(params);
        this.summary = data.summary;
        
        if (loadMore) {
          // Ajouter les nouvelles alertes aux existantes
          this.alerts = [...this.alerts, ...(data.alerts || [])];
        } else {
          // Remplacer les alertes
          this.alerts = data.alerts || [];
          this.apiOffset = 0;
        }
        
        this.hasMore = data.pagination?.has_more || false;
        this.apiOffset += 30;
        this.generatedAt = this.formatDateTime(data.generated_at);
      } catch (err) {
        console.error('Error loading fraud detection:', err);
        this.error = err.response?.data?.message || 'Erreur lors du chargement des données de fraude';
      } finally {
        this.loading = false;
      }
    },
    async loadMoreAlerts() {
      if (!this.hasMore || this.loading) return;
      await this.loadFraudData(true);
    },
    getAlertTypeLabel(type) {
      const labels = {
        'split_deposit_fraud': 'Split Deposits',
        'commission_over_ca': 'Commission > CA',
        'off_hours_large_transaction': 'Heures creuses',
        'activity_spike': 'Pic d\'activité'
      };
      return labels[type] || type;
    },
    formatAmount(amount) {
      return new Intl.NumberFormat('fr-FR').format(amount);
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      });
    },
    formatDateTime(dateTime) {
      return new Date(dateTime).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>
