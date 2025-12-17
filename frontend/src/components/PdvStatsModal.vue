<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
          
          <!-- Modal -->
          <div class="relative bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden">
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
              </div>

              <!-- Stats Content -->
              <div v-else class="space-y-6">
                <!-- PDV Info & Period Filter -->
                <div class="bg-gradient-to-r from-moov-orange/10 to-orange-100/50 rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                  <div>
                    <h3 class="font-bold text-gray-900 text-lg">{{ stats.pdv.nom_point }}</h3>
                    <p class="text-sm text-gray-600">Numéro Flooz: {{ stats.pdv.numero_flooz }}</p>
                  </div>
                  
                  <!-- Period Selector -->
                  <div class="flex gap-2">
                    <button 
                      v-for="p in periods" 
                      :key="p.value"
                      @click="changePeriod(p.value)"
                      :class="[
                        'px-4 py-2 rounded-lg font-medium transition-all',
                        selectedPeriod === p.value 
                          ? 'bg-moov-orange text-white shadow-lg' 
                          : 'bg-white text-gray-700 hover:bg-gray-100'
                      ]"
                    >
                      {{ p.label }}
                    </button>
                  </div>
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

                <!-- GIVE Transfers Stats -->
                <div v-if="stats.transfers" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-6 border border-indigo-200">
                  <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z" />
                    </svg>
                    Transferts GIVE
                  </h3>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- GIVE Envoyés -->
                    <div class="bg-white rounded-lg p-4 border-l-4 border-blue-500">
                      <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-blue-900 flex items-center gap-2">
                          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                          </svg>
                          GIVE Envoyés
                        </h4>
                      </div>
                      
                      <div class="space-y-2">
                        <div class="flex justify-between items-center">
                          <span class="text-sm text-gray-600">Total:</span>
                          <div class="text-right">
                            <p class="font-bold text-blue-900">{{ formatNumber(stats.transfers.sent.total_count) }}</p>
                            <p class="text-xs text-gray-500">{{ formatCurrency(stats.transfers.sent.total_amount) }}</p>
                          </div>
                        </div>
                        
                        <div class="flex justify-between items-center bg-green-50 p-2 rounded">
                          <span class="text-sm text-green-700">Dans le réseau:</span>
                          <div class="text-right">
                            <p class="font-semibold text-green-900">{{ formatNumber(stats.transfers.sent.in_network_count) }}</p>
                            <p class="text-xs text-green-600">{{ formatCurrency(stats.transfers.sent.in_network_amount) }}</p>
                          </div>
                        </div>
                        
                        <div class="flex justify-between items-center bg-orange-50 p-2 rounded">
                          <span class="text-sm text-orange-700">Hors réseau:</span>
                          <div class="text-right">
                            <p class="font-semibold text-orange-900">{{ formatNumber(stats.transfers.sent.out_network_count) }}</p>
                            <p class="text-xs text-orange-600">{{ formatCurrency(stats.transfers.sent.out_network_amount) }}</p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- GIVE Reçus -->
                    <div class="bg-white rounded-lg p-4 border-l-4 border-cyan-500">
                      <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-cyan-900 flex items-center gap-2">
                          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                          </svg>
                          GIVE Reçus
                        </h4>
                      </div>
                      
                      <div class="space-y-2">
                        <div class="flex justify-between items-center">
                          <span class="text-sm text-gray-600">Total:</span>
                          <div class="text-right">
                            <p class="font-bold text-cyan-900">{{ formatNumber(stats.transfers.received.total_count) }}</p>
                            <p class="text-xs text-gray-500">{{ formatCurrency(stats.transfers.received.total_amount) }}</p>
                          </div>
                        </div>
                        
                        <div class="flex justify-between items-center bg-green-50 p-2 rounded">
                          <span class="text-sm text-green-700">Dans le réseau:</span>
                          <div class="text-right">
                            <p class="font-semibold text-green-900">{{ formatNumber(stats.transfers.received.in_network_count) }}</p>
                            <p class="text-xs text-green-600">{{ formatCurrency(stats.transfers.received.in_network_amount) }}</p>
                          </div>
                        </div>
                        
                        <div class="flex justify-between items-center bg-orange-50 p-2 rounded">
                          <span class="text-sm text-orange-700">Hors réseau:</span>
                          <div class="text-right">
                            <p class="font-semibold text-orange-900">{{ formatNumber(stats.transfers.received.out_network_count) }}</p>
                            <p class="text-xs text-orange-600">{{ formatCurrency(stats.transfers.received.out_network_amount) }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Résumé -->
                  <div class="mt-4 bg-gradient-to-r from-indigo-100 to-purple-100 rounded-lg p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                      <div>
                        <p class="text-sm text-indigo-700 font-semibold">Total Transferts</p>
                        <p class="text-2xl font-bold text-indigo-900">
                          {{ formatNumber((stats.transfers.sent.total_count || 0) + (stats.transfers.received.total_count || 0)) }}
                        </p>
                      </div>
                      <div>
                        <p class="text-sm text-purple-700 font-semibold">Volume Total</p>
                        <p class="text-2xl font-bold text-purple-900">
                          {{ formatCurrency((stats.transfers.sent.total_amount || 0) + (stats.transfers.received.total_amount || 0)) }}
                        </p>
                      </div>
                      <div>
                        <p class="text-sm text-pink-700 font-semibold">Ratio Env/Reç</p>
                        <p class="text-2xl font-bold text-pink-900">
                          {{ stats.transfers.received.total_count > 0 ? (stats.transfers.sent.total_count / stats.transfers.received.total_count).toFixed(2) : 'N/A' }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Performance Cards -->
                <div v-if="stats.performance" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-green-700 font-semibold mb-2">🏆 Meilleure Performance</p>
                    <p class="text-lg font-bold text-green-900">{{ stats.performance?.best_period?.label || 'N/A' }}</p>
                    <p class="text-sm text-green-600">{{ formatCurrency(stats.performance?.best_period?.total_volume || 0) }}</p>
                  </div>

                  <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-blue-700 font-semibold mb-2">📊 Volume Moyen</p>
                    <p class="text-lg font-bold text-blue-900">{{ formatCurrency(stats.performance?.average_volume || 0) }}</p>
                    <p class="text-sm text-blue-600">Médiane: {{ formatCurrency(stats.performance?.median_volume || 0) }}</p>
                  </div>

                  <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-purple-700 font-semibold mb-2">🎯 Consistance</p>
                    <p class="text-lg font-bold text-purple-900">{{ (stats.performance?.consistency || 0).toFixed(1) }}%</p>
                    <p class="text-sm text-purple-600">Régularité des performances</p>
                  </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" v-if="stats.charts">
                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-bold text-gray-900 mb-4">📈 Évolution du Volume</h3>
                    <Line :data="volumeChartData" :options="chartOptions" />
                  </div>

                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-bold text-gray-900 mb-4">💰 Commissions</h3>
                    <Bar :data="commissionsChartData" :options="chartOptions" />
                  </div>

                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-bold text-gray-900 mb-4">📊 Nombre de Transactions</h3>
                    <Bar :data="transactionsChartData" :options="chartOptions" />
                  </div>

                  <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <h3 class="font-bold text-gray-900 mb-4">🔄 Transferts</h3>
                    <Line :data="transfersChartData" :options="chartOptions" />
                  </div>
                </div>

                <!-- Performance vs Moyenne -->
                <div v-if="stats.trends" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
                  <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-moov-orange" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    Performance vs Moyenne
                  </h3>
                  <p class="text-sm text-gray-600 mb-4">Dernière période: {{ stats.trends?.latest_period || 'N/A' }}</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <p class="text-sm text-gray-600">Dépôts:</p>
                      <p :class="['text-lg font-bold', (stats.trends?.depot_vs_average || 0) >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ (stats.trends?.depot_vs_average || 0) >= 0 ? '+' : '' }}{{ (stats.trends?.depot_vs_average || 0).toFixed(1) }}%
                      </p>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600">Retraits:</p>
                      <p :class="['text-lg font-bold', (stats.trends?.retrait_vs_average || 0) >= 0 ? 'text-green-600' : 'text-red-600']">
                        {{ (stats.trends?.retrait_vs_average || 0) >= 0 ? '+' : '' }}{{ (stats.trends?.retrait_vs_average || 0).toFixed(1) }}%
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Évolution Temporelle with Pagination -->
                <div class="bg-white rounded-lg border border-gray-200">
                  <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="font-bold text-gray-900">Évolution temporelle</h3>
                  </div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dépôts</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retraits</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in (stats.timeline?.data || [])" :key="item.period" class="hover:bg-gray-50">
                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.label }}</td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div>{{ item.depot_count || 0 }} <span class="text-gray-400">({{ formatCurrency(item.depot_amount) }})</span></div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <div>{{ item.retrait_count || 0 }} <span class="text-gray-400">({{ formatCurrency(item.retrait_amount) }})</span></div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-green-600">{{ formatCurrency(item.pdv_commission) }}</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Pagination -->
                  <div v-if="stats.timeline?.last_page > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                      Affichage <span class="font-medium">{{ stats.timeline?.from || 0 }}</span> à <span class="font-medium">{{ stats.timeline?.to || 0 }}</span> sur <span class="font-medium">{{ stats.timeline?.total || 0 }}</span> résultats
                    </div>
                    <div class="flex gap-2">
                      <button 
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        :class="[
                          'px-3 py-1 rounded border',
                          currentPage === 1 
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                            : 'bg-white text-gray-700 hover:bg-gray-50'
                        ]"
                      >
                        Précédent
                      </button>
                      <span class="px-3 py-1 text-sm text-gray-700">
                        Page {{ currentPage }} / {{ stats.timeline?.last_page || 1 }}
                      </span>
                      <button 
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage === (stats.timeline?.last_page || 1)"
                        :class="[
                          'px-3 py-1 rounded border',
                          currentPage === (stats.timeline?.last_page || 1)
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                            : 'bg-white text-gray-700 hover:bg-gray-50'
                        ]"
                      >
                        Suivant
                      </button>
                    </div>
                  </div>
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
import { ref, watch, computed } from 'vue';
import { Line, Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import TransactionService from '../services/transactionService';
import { useToast } from '../composables/useToast';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

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

const periods = [
  { value: 'day', label: 'Jour' },
  { value: 'week', label: 'Semaine' },
  { value: 'month', label: 'Mois' }
];

const selectedPeriod = ref('day');
const currentPage = ref(1);
const perPage = ref(10);

const loadStats = async () => {
  if (!props.isOpen || !props.pdvId) return;
  
  try {
    loading.value = true;
    const response = await TransactionService.getStats(props.pdvId, {
      period: selectedPeriod.value,
      page: currentPage.value,
      per_page: perPage.value
    });
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

const changePeriod = (period) => {
  selectedPeriod.value = period;
  currentPage.value = 1;
  loadStats();
};

const changePage = (page) => {
  currentPage.value = page;
  loadStats();
};

watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    currentPage.value = 1;
    loadStats();
  }
});

const close = () => {
  emit('close');
};

// Chart data
const volumeChartData = computed(() => {
  if (!stats.value.charts) return { labels: [], datasets: [] };
  return {
    labels: stats.value.charts.volumes.labels,
    datasets: [
      {
        label: 'Dépôts',
        data: stats.value.charts.volumes.depot,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4
      },
      {
        label: 'Retraits',
        data: stats.value.charts.volumes.retrait,
        borderColor: '#ef4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        fill: true,
        tension: 0.4
      }
    ]
  };
});

const commissionsChartData = computed(() => {
  if (!stats.value.charts) return { labels: [], datasets: [] };
  return {
    labels: stats.value.charts.commissions.labels,
    datasets: [
      {
        label: 'PDV',
        data: stats.value.charts.commissions.pdv,
        backgroundColor: '#8b5cf6',
      },
      {
        label: 'Dealer',
        data: stats.value.charts.commissions.dealer,
        backgroundColor: '#f97316',
      }
    ]
  };
});

const transactionsChartData = computed(() => {
  if (!stats.value.charts) return { labels: [], datasets: [] };
  return {
    labels: stats.value.charts.transactions.labels,
    datasets: [
      {
        label: 'Dépôts',
        data: stats.value.charts.transactions.depot,
        backgroundColor: '#10b981',
      },
      {
        label: 'Retraits',
        data: stats.value.charts.transactions.retrait,
        backgroundColor: '#ef4444',
      }
    ]
  };
});

const transfersChartData = computed(() => {
  if (!stats.value.charts) return { labels: [], datasets: [] };
  return {
    labels: stats.value.charts.transfers.labels,
    datasets: [
      {
        label: 'Envoyés',
        data: stats.value.charts.transfers.sent,
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4
      },
      {
        label: 'Reçus',
        data: stats.value.charts.transfers.received,
        borderColor: '#06b6d4',
        backgroundColor: 'rgba(6, 182, 212, 0.1)',
        fill: true,
        tension: 0.4
      }
    ]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
  scales: {
    y: {
      beginAtZero: true
    }
  }
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
