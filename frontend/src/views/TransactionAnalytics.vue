<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Tableau de Bord Transactionnel</h1>
          <p class="text-sm text-gray-600 mt-1">Vue d'ensemble et analyse des transactions importées</p>
        </div>
        
        <!-- Period Selector -->
        <div class="flex items-center gap-2">
          <button
            v-for="period in periods"
            :key="period.value"
            @click="selectedPeriod = period.value"
            :class="[
              'px-4 py-2 rounded-xl font-semibold text-sm transition-all',
              selectedPeriod === period.value
                ? 'bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white shadow-lg'
                : 'bg-white/90 text-gray-700 hover:bg-white border border-gray-200'
            ]"
          >
            {{ period.label }}
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl rounded-2xl p-8 sm:p-12 text-center">
        <div class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-sm sm:text-base text-gray-600 font-semibold">Chargement des analytics...</p>
        <p class="text-xs text-gray-500 mt-2">Analyse de ~27 000 PDV en cours...</p>
        <p class="text-xs text-gray-400 mt-1">Première charge : ~10s • Ensuite : &lt;1s (cache)</p>
      </div>

      <!-- Content -->
      <div v-else-if="analytics" class="space-y-6">
        <!-- Date Range Info -->
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-4">
          <div class="flex items-center gap-2 text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span class="font-semibold">Période :</span>
            <span>{{ formatDate(analytics.date_range.start) }} - {{ formatDate(analytics.date_range.end) }}</span>
          </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
          <!-- Chiffre d'Affaires -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Chiffre d'Affaires</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.kpi.chiffre_affaire) }}</p>
            <p class="text-xs text-gray-500 mt-1">RETRAIT_KEYCOST</p>
          </div>

          <!-- Volume Total -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Volume Total</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.kpi.volume_total) }}</p>
            <p class="text-xs text-gray-500 mt-1">Dépôts + Retraits</p>
          </div>

          <!-- Total Transactions -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Transactions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.kpi.total_transactions) }}</p>
            <p class="text-xs text-gray-500 mt-1">Toutes opérations</p>
          </div>

          <!-- PDV Actifs -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">PDV Actifs</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.kpi.pdv_actifs) }}</p>
            <p class="text-xs text-gray-500 mt-1">Avec transactions</p>
          </div>
        </div>

        <!-- Transactions Detail -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Dépôts & Retraits -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Détail des Transactions</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
                <div>
                  <p class="text-sm font-semibold text-gray-600">Dépôts</p>
                  <p class="text-xl font-bold text-green-600">{{ formatNumber(analytics.kpi.transactions_detail.depots.count) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm text-gray-600">Montant</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatCurrency(analytics.kpi.transactions_detail.depots.amount) }}</p>
                  <p class="text-xs text-gray-500">Moy: {{ formatCurrency(analytics.kpi.transactions_detail.depots.average) }}</p>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                <div>
                  <p class="text-sm font-semibold text-gray-600">Retraits</p>
                  <p class="text-xl font-bold text-red-600">{{ formatNumber(analytics.kpi.transactions_detail.retraits.count) }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm text-gray-600">Montant</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatCurrency(analytics.kpi.transactions_detail.retraits.amount) }}</p>
                  <p class="text-xs text-gray-500">Moy: {{ formatCurrency(analytics.kpi.transactions_detail.retraits.average) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Commissions -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Commissions</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-blue-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">PDV</p>
                <p class="text-xl font-bold text-blue-600">{{ formatCurrency(analytics.kpi.commissions.pdv) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-purple-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">Dealers</p>
                <p class="text-xl font-bold text-purple-600">{{ formatCurrency(analytics.kpi.commissions.dealers) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-gradient-to-r from-moov-orange/10 to-moov-orange-dark/10 rounded-xl border-2 border-moov-orange/30">
                <p class="text-sm font-bold text-gray-700">Total</p>
                <p class="text-2xl font-bold text-moov-orange">{{ formatCurrency(analytics.kpi.commissions.total) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Message si pas de données d'évolution -->
        <div v-if="!analytics.evolution || analytics.evolution.length === 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">Aucune donnée d'évolution disponible</h3>
              <p class="mt-1 text-sm text-yellow-700">
                Il n'y a pas encore de transactions pour cette période. Les données seront disponibles après l'import.
              </p>
            </div>
          </div>
        </div>

        <!-- Evolution Chart - CA -->
        <div v-if="analytics.evolution && analytics.evolution.length > 0" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Évolution du Chiffre d'Affaires</h3>
          <div class="h-64">
            <Line :data="caChartData" :options="caChartOptions" />
          </div>
        </div>

        <!-- Evolution Chart - PDV Actifs -->
        <div v-if="analytics.evolution && analytics.evolution.length > 0" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Évolution des PDV Actifs</h3>
          <div class="h-64">
            <Line :data="pdvChartData" :options="pdvChartOptions" />
          </div>
        </div>

        <!-- Top PDV & Top Dealers -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <!-- Top PDV -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              Top 10 PDV (par CA)
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(pdv, index) in analytics.top_pdv"
                :key="pdv.pdv_numero"
                class="flex items-center gap-4 p-4 rounded-xl hover:bg-moov-orange/5 transition-colors border border-gray-100"
              >
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-moov-orange to-moov-orange-dark text-white flex items-center justify-center font-bold text-sm">
                  {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-gray-900 truncate">{{ pdv.nom_point }}</p>
                  <p class="text-xs text-gray-500">{{ pdv.pdv_numero }}</p>
                  <p class="text-xs text-gray-500">Dealer: {{ pdv.dealer_name || 'N/A' }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-moov-orange">{{ formatCurrency(pdv.chiffre_affaire) }}</p>
                  <p class="text-xs text-gray-500">{{ formatNumber(pdv.total_transactions) }} tx</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Dealers -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              Top 10 Dealers (par CA)
            </h3>
            <div class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="(dealer, index) in analytics.top_dealers"
                :key="dealer.dealer_name"
                class="flex items-center gap-4 p-4 rounded-xl hover:bg-moov-orange/5 transition-colors border border-gray-100"
              >
                <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm">
                  {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-gray-900 truncate">{{ dealer.dealer_name || 'N/A' }}</p>
                  <p class="text-xs text-gray-500">{{ dealer.pdv_count }} PDV</p>
                  <p class="text-xs text-gray-500">CA/PDV: {{ formatCurrency(dealer.ca_par_pdv) }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-purple-600">{{ formatCurrency(dealer.chiffre_affaire) }}</p>
                  <p class="text-xs text-gray-500">{{ formatNumber(dealer.total_transactions) }} tx</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Distribution -->
        <div v-if="analytics.distribution" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Distribution des Transactions par Type</h3>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 rounded-xl border-2 border-green-200">
              <p class="text-sm font-semibold text-gray-600 mb-1">Dépôts</p>
              <p class="text-2xl font-bold text-green-600">{{ analytics.distribution.par_type.depots.percentage }}%</p>
              <p class="text-xs text-gray-500">{{ formatNumber(analytics.distribution.par_type.depots.count) }} opérations</p>
            </div>
            <div class="p-4 bg-red-50 rounded-xl border-2 border-red-200">
              <p class="text-sm font-semibold text-gray-600 mb-1">Retraits</p>
              <p class="text-2xl font-bold text-red-600">{{ analytics.distribution.par_type.retraits.percentage }}%</p>
              <p class="text-xs text-gray-500">{{ formatNumber(analytics.distribution.par_type.retraits.count) }} opérations</p>
            </div>
            <div class="p-4 bg-blue-50 rounded-xl border-2 border-blue-200">
              <p class="text-sm font-semibold text-gray-600 mb-1">Transferts GIVE</p>
              <p class="text-2xl font-bold text-blue-600">{{ analytics.distribution.par_type.transfers.percentage }}%</p>
              <p class="text-xs text-gray-500">{{ formatNumber(analytics.distribution.par_type.transfers.count) }} opérations</p>
            </div>
          </div>
        </div>

        <!-- Forecasting Widget -->
        <ForecastWidget 
          scope="global" 
          :autoRefresh="true" 
          :refreshInterval="300000"
        />

        <!-- AI Insights Section -->
        <div v-if="insights && insights.length > 0" class="bg-gradient-to-br from-indigo-50 to-purple-50 border-2 border-indigo-200 shadow-2xl rounded-2xl p-6">
          <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-gray-900">🤖 Insights & Recommandations</h3>
              <p class="text-sm text-gray-600">Analyse intelligente de vos données</p>
            </div>
            <button 
              @click="refreshInsights" 
              :disabled="loadingInsights"
              class="ml-auto px-4 py-2 bg-white rounded-lg shadow hover:shadow-md transition-all text-sm font-semibold text-indigo-600 hover:bg-indigo-50 disabled:opacity-50"
            >
              <svg v-if="!loadingInsights" class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span class="animate-spin inline-block" v-else>⟳</span>
              {{ loadingInsights ? 'Analyse...' : 'Actualiser' }}
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div 
              v-for="(insight, index) in insights" 
              :key="index"
              :class="[
                'p-5 rounded-xl border-l-4 transition-all hover:shadow-lg',
                insight.severity === 'high' && insight.type === 'alert' ? 'bg-red-50 border-red-500' : '',
                insight.severity === 'high' && insight.type !== 'alert' ? 'bg-orange-50 border-orange-500' : '',
                insight.severity === 'medium' ? 'bg-yellow-50 border-yellow-500' : '',
                insight.type === 'success' ? 'bg-green-50 border-green-500' : '',
                insight.type === 'recommendation' && insight.severity !== 'medium' ? 'bg-blue-50 border-blue-500' : '',
              ]"
            >
              <div class="flex items-start gap-3">
                <div class="flex-1">
                  <h4 class="font-bold text-gray-900 text-lg mb-2">{{ insight.title }}</h4>
                  <p class="text-gray-700 mb-3">{{ insight.message }}</p>
                  
                  <!-- Details -->
                  <div v-if="insight.details && insight.details.length > 0" class="mb-3">
                    <button 
                      @click="insight.showDetails = !insight.showDetails"
                      class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1"
                    >
                      <span>{{ insight.showDetails ? '▼' : '▶' }} Voir les détails ({{ insight.details.length }})</span>
                    </button>
                    <div v-if="insight.showDetails" class="mt-2 space-y-2">
                      <div 
                        v-for="(detail, idx) in insight.details.slice(0, 5)" 
                        :key="idx"
                        class="p-3 bg-white/70 rounded-lg text-sm"
                      >
                        <p><strong>PDV:</strong> {{ detail.pdv }} - {{ detail.nom }}</p>
                        <p v-if="detail.dealer" class="text-gray-600"><strong>Dealer:</strong> {{ detail.dealer }}</p>
                        <p v-if="detail.region" class="text-gray-600"><strong>Région:</strong> {{ detail.region }}</p>
                        <p v-if="detail.drop_percent" class="text-red-600 font-semibold">
                          <strong>Baisse:</strong> {{ detail.drop_percent }}%
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Recommendation -->
                  <div class="flex items-start gap-2 p-3 bg-white/70 rounded-lg border border-indigo-200">
                    <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                      <p class="font-semibold text-indigo-900 text-sm">Recommandation :</p>
                      <p class="text-gray-700 text-sm mt-1">{{ insight.recommendation }}</p>
                    </div>
                  </div>
                </div>

                <!-- Badge -->
                <div class="flex-shrink-0">
                  <span 
                    :class="[
                      'inline-block px-3 py-1 rounded-full text-xs font-bold uppercase',
                      insight.category === 'inactivity' ? 'bg-red-200 text-red-800' : '',
                      insight.category === 'trend' ? 'bg-purple-200 text-purple-800' : '',
                      insight.category === 'anomaly' ? 'bg-orange-200 text-orange-800' : '',
                      insight.category === 'optimization' ? 'bg-blue-200 text-blue-800' : '',
                      insight.category === 'regional' ? 'bg-yellow-200 text-yellow-800' : '',
                      insight.category === 'best_practice' ? 'bg-green-200 text-green-800' : '',
                    ]"
                  >
                    {{ insight.category }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 p-4 bg-white/50 rounded-lg border border-indigo-100">
            <p class="text-xs text-gray-600 text-center">
              <svg class="w-4 h-4 inline text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
              Ces insights sont générés automatiquement par analyse des données transactionnelles.
              Dernière mise à jour : {{ insightsGeneratedAt }}
            </p>
          </div>
        </div>
      </div>

      <!-- AI Recommendations Widget -->
      <RecommendationsWidget 
        class="mt-8"
        scope="global" 
        :limit="10"
        :autoRefresh="false"
      />

      <!-- Fraud Detection Widget -->
      <FraudDetectionWidget 
        class="mt-8"
        scope="global"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import Navbar from '../components/Navbar.vue';
import ForecastWidget from '../components/ForecastWidget.vue';
import RecommendationsWidget from '../components/RecommendationsWidget.vue';
import FraudDetectionWidget from '../components/FraudDetectionWidget.vue';
import TransactionAnalyticsService from '../services/transactionAnalyticsService';
import { useToast } from '../composables/useToast';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const { toast } = useToast();
const loading = ref(false);
const analytics = ref(null);
const insights = ref([]);
const loadingInsights = ref(false);
const insightsGeneratedAt = ref('');

const periods = [
  { value: 'day', label: 'Jour' },
  { value: 'week', label: 'Semaine' },
  { value: 'month', label: 'Mois' },
  { value: 'quarter', label: 'Trimestre' }
];

const selectedPeriod = ref('month');

// Load analytics
const loadAnalytics = async () => {
  try {
    loading.value = true;
    const response = await TransactionAnalyticsService.getAnalytics({
      period: selectedPeriod.value
    });
    analytics.value = response.data;
  } catch (error) {
    console.error('Error loading analytics:', error);
    toast.error('Erreur lors du chargement des analytics');
  } finally {
    loading.value = false;
  }
};

// Load AI insights
const loadInsights = async () => {
  try {
    loadingInsights.value = true;
    const response = await TransactionAnalyticsService.getInsights({
      period: selectedPeriod.value
    });
    insights.value = response.data.insights.map(insight => ({
      ...insight,
      showDetails: false
    }));
    insightsGeneratedAt.value = new Date(response.data.generated_at).toLocaleString('fr-FR');
  } catch (error) {
    console.error('Error loading insights:', error);
    // Don't show error toast, insights are optional
  } finally {
    loadingInsights.value = false;
  }
};

// Refresh insights manually
const refreshInsights = () => {
  loadInsights();
};

// Watch period changes
watch(selectedPeriod, () => {
  loadAnalytics();
  loadInsights();
});

// Chart data - CA
const caChartData = computed(() => {
  if (!analytics.value || !analytics.value.evolution) return null;

  return {
    labels: analytics.value.evolution.map(e => e.label),
    datasets: [
      {
        label: 'Chiffre d\'Affaires',
        data: analytics.value.evolution.map(e => e.chiffre_affaire),
        borderColor: '#FF6B00',
        backgroundColor: 'rgba(255, 107, 0, 0.1)',
        fill: true,
        tension: 0.4,
      }
    ]
  };
});

const caChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context) => `CA: ${formatCurrency(context.parsed.y)}`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => formatCurrency(value)
      }
    }
  }
};

// Chart data - PDV Actifs
const pdvChartData = computed(() => {
  if (!analytics.value || !analytics.value.evolution) return null;

  return {
    labels: analytics.value.evolution.map(e => e.label),
    datasets: [
      {
        label: 'PDV Actifs',
        data: analytics.value.evolution.map(e => e.pdv_actifs),
        borderColor: '#8B5CF6',
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        fill: true,
        tension: 0.4,
      }
    ]
  };
});

const pdvChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context) => `PDV Actifs: ${formatNumber(context.parsed.y)}`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: false,
      ticks: {
        callback: (value) => formatNumber(value)
      }
    }
  }
};

// Formatters
const formatCurrency = (value) => {
  if (!value && value !== 0) return '0 FCFA';
  
  const formatted = new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
    useGrouping: true,
  }).format(value);
  
  return `${formatted} FCFA`;
};

const formatNumber = (value) => {
  if (!value && value !== 0) return '0';
  
  return new Intl.NumberFormat('fr-FR', {
    useGrouping: true,
  }).format(value);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

// Initial load
onMounted(() => {
  loadAnalytics();
  loadInsights();
});
</script>
