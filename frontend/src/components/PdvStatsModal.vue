<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
          
          <!-- Modal -->
          <div class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-moov-orange to-orange-600 px-6 py-4 flex items-center justify-between z-10">
              <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                </svg>
                <h2 class="text-xl font-bold text-white">Statistiques Transactionnelles</h2>
              </div>
              <button @click="close" class="text-white hover:text-gray-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Content -->
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
              <!-- Loading State -->
              <div v-if="loading" class="flex flex-col items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-moov-orange mb-4"></div>
                <p class="text-gray-600">Chargement des statistiques...</p>
              </div>

              <!-- No Data State -->
              <div v-else-if="!stats.hasData" class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune donnée disponible</h3>
                <p class="text-gray-600">{{ stats.message }}</p>
                <p class="text-sm text-gray-500 mt-2">Importez des fichiers de transactions depuis les paramètres système</p>
              </div>

              <!-- Stats Content -->
              <div v-else class="space-y-6">
                <!-- PDV Info -->
                <div class="bg-gradient-to-r from-moov-orange/10 to-orange-100/50 rounded-lg p-4">
                  <h3 class="font-bold text-gray-900 text-lg">{{ stats.pdv.nom_point }}</h3>
                  <p class="text-sm text-gray-600">Numéro Flooz: {{ stats.pdv.numero_flooz }}</p>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                  <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm text-blue-600 font-semibold">Total Transactions</p>
                        <p class="text-2xl font-bold text-blue-900">{{ formatNumber(stats.summary.total_transactions) }}</p>
                      </div>
                      <svg class="w-10 h-10 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>

                  <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm text-green-600 font-semibold">Volume Total</p>
                        <p class="text-2xl font-bold text-green-900">{{ formatCurrency(stats.summary.total_volume) }}</p>
                      </div>
                      <svg class="w-10 h-10 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>

                  <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm text-purple-600 font-semibold">Commissions PDV</p>
                        <p class="text-2xl font-bold text-purple-900">{{ formatCurrency(stats.commissions.pdv.total) }}</p>
                      </div>
                      <svg class="w-10 h-10 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>

                  <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm text-orange-600 font-semibold">Commissions Dealer</p>
                        <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(stats.commissions.dealer.total) }}</p>
                      </div>
                      <svg class="w-10 h-10 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- Dépôts vs Retraits -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="bg-white rounded-lg border-2 border-green-200 p-4">
                    <h4 class="text-lg font-bold text-green-700 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                      </svg>
                      Dépôts
                    </h4>
                    <div class="space-y-2">
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nombre:</span>
                        <span class="font-bold text-gray-900">{{ formatNumber(stats.summary.total_depot_count) }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Montant total:</span>
                        <span class="font-bold text-gray-900">{{ formatCurrency(stats.summary.total_depot_amount) }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Montant moyen:</span>
                        <span class="font-bold text-gray-900">{{ formatCurrency(stats.summary.avg_depot) }}</span>
                      </div>
                      <div class="pt-2 border-t">
                        <div class="flex justify-between items-center text-sm">
                          <span class="text-gray-600">Commission PDV:</span>
                          <span class="font-semibold text-green-600">{{ formatCurrency(stats.commissions.pdv.depot) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                          <span class="text-gray-600">Commission Dealer:</span>
                          <span class="font-semibold text-green-600">{{ formatCurrency(stats.commissions.dealer.depot) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-white rounded-lg border-2 border-red-200 p-4">
                    <h4 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" transform="rotate(180 10 10)" />
                      </svg>
                      Retraits
                    </h4>
                    <div class="space-y-2">
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Nombre:</span>
                        <span class="font-bold text-gray-900">{{ formatNumber(stats.summary.total_retrait_count) }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Montant total:</span>
                        <span class="font-bold text-gray-900">{{ formatCurrency(stats.summary.total_retrait_amount) }}</span>
                      </div>
                      <div class="flex justify-between items-center">
                        <span class="text-gray-600">Montant moyen:</span>
                        <span class="font-bold text-gray-900">{{ formatCurrency(stats.summary.avg_retrait) }}</span>
                      </div>
                      <div class="pt-2 border-t">
                        <div class="flex justify-between items-center text-sm">
                          <span class="text-gray-600">Commission PDV:</span>
                          <span class="font-semibold text-red-600">{{ formatCurrency(stats.commissions.pdv.retrait) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                          <span class="text-gray-600">Commission Dealer:</span>
                          <span class="font-semibold text-red-600">{{ formatCurrency(stats.commissions.dealer.retrait) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Transferts -->
                <div class="bg-white rounded-lg border-2 border-blue-200 p-4">
                  <h4 class="text-lg font-bold text-blue-700 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z" />
                    </svg>
                    Transferts
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <h5 class="font-semibold text-gray-900 mb-2">Envoyés</h5>
                      <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                          <span class="text-gray-600">Total:</span>
                          <span class="font-semibold">{{ formatNumber(stats.transfers.sent.total_count) }} ({{ formatCurrency(stats.transfers.sent.total_amount) }})</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Dans le réseau:</span>
                          <span>{{ formatNumber(stats.transfers.sent.in_network_count) }} ({{ formatCurrency(stats.transfers.sent.in_network_amount) }})</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Hors réseau:</span>
                          <span>{{ formatNumber(stats.transfers.sent.out_network_count) }} ({{ formatCurrency(stats.transfers.sent.out_network_amount) }})</span>
                        </div>
                      </div>
                    </div>
                    <div>
                      <h5 class="font-semibold text-gray-900 mb-2">Reçus</h5>
                      <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                          <span class="text-gray-600">Total:</span>
                          <span class="font-semibold">{{ formatNumber(stats.transfers.received.total_count) }} ({{ formatCurrency(stats.transfers.received.total_amount) }})</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Dans le réseau:</span>
                          <span>{{ formatNumber(stats.transfers.received.in_network_count) }} ({{ formatCurrency(stats.transfers.received.in_network_amount) }})</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Hors réseau:</span>
                          <span>{{ formatNumber(stats.transfers.received.out_network_count) }} ({{ formatCurrency(stats.transfers.received.out_network_amount) }})</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Performance Trend -->
                <div v-if="stats.trends" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg border-2 border-indigo-200 p-4">
                  <h4 class="text-lg font-bold text-indigo-700 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    Performance vs Moyenne
                  </h4>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center justify-between">
                      <span class="text-gray-700">Dépôts:</span>
                      <span 
                        class="px-3 py-1 rounded-full text-sm font-bold"
                        :class="stats.trends.performance.depot_count_vs_avg >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                      >
                        {{ stats.trends.performance.depot_count_vs_avg >= 0 ? '+' : '' }}{{ stats.trends.performance.depot_count_vs_avg.toFixed(1) }}%
                      </span>
                    </div>
                    <div class="flex items-center justify-between">
                      <span class="text-gray-700">Retraits:</span>
                      <span 
                        class="px-3 py-1 rounded-full text-sm font-bold"
                        :class="stats.trends.performance.retrait_count_vs_avg >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                      >
                        {{ stats.trends.performance.retrait_count_vs_avg >= 0 ? '+' : '' }}{{ stats.trends.performance.retrait_count_vs_avg.toFixed(1) }}%
                      </span>
                    </div>
                  </div>
                  <p class="text-xs text-gray-600 mt-2">Dernière période: {{ stats.trends.latest_period.date }}</p>
                </div>

                <!-- Timeline (dernières périodes) -->
                <div v-if="stats.timeline && stats.timeline.length > 0" class="bg-white rounded-lg border-2 border-gray-200 p-4">
                  <h4 class="text-lg font-bold text-gray-900 mb-3">Évolution temporelle</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Date</th>
                          <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Dépôts</th>
                          <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Retraits</th>
                          <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Commissions</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                        <tr v-for="period in stats.timeline.slice(-10)" :key="period.date" class="hover:bg-gray-50">
                          <td class="px-3 py-2 text-gray-900 font-medium">{{ formatDate(period.date) }}</td>
                          <td class="px-3 py-2 text-right text-gray-700">
                            {{ formatNumber(period.depot_count) }}
                            <span class="text-xs text-gray-500">({{ formatCurrency(period.depot_amount) }})</span>
                          </td>
                          <td class="px-3 py-2 text-right text-gray-700">
                            {{ formatNumber(period.retrait_count) }}
                            <span class="text-xs text-gray-500">({{ formatCurrency(period.retrait_amount) }})</span>
                          </td>
                          <td class="px-3 py-2 text-right text-green-600 font-semibold">
                            {{ formatCurrency(period.pdv_commission) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p v-if="stats.timeline.length > 10" class="text-xs text-gray-500 mt-2 text-center">
                    Affichage des 10 dernières périodes ({{ stats.timeline.length }} au total)
                  </p>
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
import { ref, watch } from 'vue';
import TransactionService from '../services/transactionService';
import { useToast } from '../composables/useToast';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  pdvId: {
    type: Number,
    required: true
  }
});

const emit = defineEmits(['close']);

const { toast } = useToast();
const loading = ref(false);
const stats = ref({
  hasData: false,
  message: ''
});

const loadStats = async () => {
  if (!props.isOpen || !props.pdvId) return;
  
  try {
    loading.value = true;
    const response = await TransactionService.getStats(props.pdvId);
    stats.value = response.data;
  } catch (error) {
    console.error('Error loading stats:', error);
    toast.error('Erreur lors du chargement des statistiques');
    stats.value = {
      hasData: false,
      message: 'Erreur lors du chargement des données'
    };
  } finally {
    loading.value = false;
  }
};

watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    loadStats();
  }
});

const close = () => {
  emit('close');
};

const formatNumber = (num) => {
  return new Intl.NumberFormat('fr-FR').format(num || 0);
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount || 0);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.3s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
}
</style>
