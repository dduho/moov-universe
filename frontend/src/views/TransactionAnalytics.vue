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

        <!-- CA Mensuel - Année Courante -->
        <div v-if="monthlyRevenue" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Chiffre d'Affaires Mensuel {{ monthlyRevenue.year }}</h3>
            <div class="text-sm text-gray-600">
              Total année: <span class="font-bold text-moov-orange">{{ formatCurrency(monthlyRevenue.total_ca) }}</span>
            </div>
          </div>
          
          <!-- Grille 6 mois par ligne -->
          <div class="space-y-4">
            <!-- Première ligne: Jan-Juin -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
              <div
                v-for="month in monthlyRevenue.months.slice(0, 6)"
                :key="month.month"
                class="bg-gradient-to-br from-gray-50 to-white border-2 rounded-xl p-4 hover:shadow-lg transition-all"
                :class="month.has_data ? 'border-moov-orange/20 hover:border-moov-orange/50' : 'border-gray-200 opacity-60'"
              >
                <div class="text-center">
                  <!-- Icône du mois -->
                  <div class="flex justify-center mb-2" v-html="getMonthIcon(month.month)"></div>
                  <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ month.month_name }}</p>
                  <p class="text-xl font-bold text-gray-900 mb-3">{{ formatCurrencyShort(month.ca) }}</p>
                  
                  <!-- Performance vs mois précédent -->
                  <div class="flex items-center justify-center gap-1 mb-2">
                    <span class="text-xs text-gray-500">vs mois préc:</span>
                    <span 
                      class="text-xs font-bold flex items-center gap-0.5"
                      :class="month.vs_last_month >= 0 ? 'text-green-600' : 'text-red-600'"
                    >
                      <svg v-if="month.vs_last_month >= 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      {{ Math.abs(month.vs_last_month) }}%
                    </span>
                  </div>
                  
                  <!-- Performance vs même mois année N-1 -->
                  <div class="flex items-center justify-center gap-1">
                    <span class="text-xs text-gray-500">vs N-1:</span>
                    <span 
                      class="text-xs font-bold flex items-center gap-0.5"
                      :class="month.vs_last_year >= 0 ? 'text-green-600' : 'text-red-600'"
                    >
                      <svg v-if="month.vs_last_year >= 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      {{ Math.abs(month.vs_last_year) }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Deuxième ligne: Juil-Déc -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
              <div
                v-for="month in monthlyRevenue.months.slice(6, 12)"
                :key="month.month"
                class="bg-gradient-to-br from-gray-50 to-white border-2 rounded-xl p-4 hover:shadow-lg transition-all"
                :class="month.has_data ? 'border-moov-orange/20 hover:border-moov-orange/50' : 'border-gray-200 opacity-60'"
              >
                <div class="text-center">
                  <!-- Icône du mois -->
                  <div class="flex justify-center mb-2" v-html="getMonthIcon(month.month)"></div>
                  <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ month.month_name }}</p>
                  <p class="text-xl font-bold text-gray-900 mb-3">{{ formatCurrencyShort(month.ca) }}</p>
                  
                  <!-- Performance vs mois précédent -->
                  <div class="flex items-center justify-center gap-1 mb-2">
                    <span class="text-xs text-gray-500">vs mois préc:</span>
                    <span 
                      class="text-xs font-bold flex items-center gap-0.5"
                      :class="month.vs_last_month >= 0 ? 'text-green-600' : 'text-red-600'"
                    >
                      <svg v-if="month.vs_last_month >= 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      {{ Math.abs(month.vs_last_month) }}%
                    </span>
                  </div>
                  
                  <!-- Performance vs même mois année N-1 -->
                  <div class="flex items-center justify-center gap-1">
                    <span class="text-xs text-gray-500">vs N-1:</span>
                    <span 
                      class="text-xs font-bold flex items-center gap-0.5"
                      :class="month.vs_last_year >= 0 ? 'text-green-600' : 'text-red-600'"
                    >
                      <svg v-if="month.vs_last_year >= 0" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                      {{ Math.abs(month.vs_last_year) }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
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
import TransactionService from '../services/transactionService';
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

// State
const loading = ref(false);
const analytics = ref(null);
const insights = ref([]);
const loadingInsights = ref(false);
const insightsGeneratedAt = ref('');
const monthlyRevenue = ref(null);

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

// Load monthly revenue
const loadMonthlyRevenue = async () => {
  try {
    const response = await TransactionService.getMonthlyRevenue(new Date().getFullYear());
    monthlyRevenue.value = response.data;
  } catch (error) {
    console.error('Error loading monthly revenue:', error);
    // N'affiche pas de toast d'erreur pour ne pas polluer l'interface
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

const formatCurrencyShort = (value) => {
  if (!value && value !== 0) return '0';
  
  // Format court pour les grandes valeurs
  if (value >= 1000000000) { // Milliards
    return `${(value / 1000000000).toFixed(1)}B`;
  } else if (value >= 1000000) { // Millions
    return `${(value / 1000000).toFixed(1)}M`;
  } else if (value >= 1000) { // Milliers
    return `${(value / 1000).toFixed(1)}K`;
  }
  
  return formatNumber(value);
};

// Obtenir l'icône SVG pour chaque mois
const getMonthIcon = (monthNumber) => {
  const icons = {
    1: `<svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd" /></svg>`, // Janvier - Planète/Nouveau départ
    2: `<svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg>`, // Février - Cœur
    3: `<svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18c-3.866 0-7-3.134-7-7 0-2.21 1.79-4 4-4 .552 0 1 .448 1 1s-.448 1-1 1c-1.104 0-2 .896-2 2 0 2.757 2.243 5 5 5s5-2.243 5-5c0-1.104-.896-2-2-2-.552 0-1-.448-1-1s.448-1 1-1c2.21 0 4 1.79 4 4 0 3.866-3.134 7-7 7z"/></svg>`, // Mars - Pousse verte
    4: `<svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z" /></svg>`, // Avril - Nuage pluie
    5: `<svg class="w-8 h-8 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" /></svg>`, // Mai - Soleil
    6: `<svg class="w-8 h-8 text-orange-400" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" /></svg>`, // Juin - Ampoule/Été
    7: `<svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.5 2a3.5 3.5 0 101.665 6.58L8.585 10l-1.42 1.42a3.5 3.5 0 101.414 1.414l8.128-8.127a1 1 0 00-1.414-1.414L10 8.586l-1.42-1.42A3.5 3.5 0 005.5 2zM4 5.5a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zm0 9a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z" clip-rule="evenodd" /><path d="M12.828 11.414a1 1 0 00-1.414 1.414l3.879 3.88a1 1 0 001.414-1.415l-3.879-3.879z" /></svg>`, // Juillet - Vacances/Parasol
    8: `<svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" /></svg>`, // Août - Feu/Chaleur
    9: `<svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M17.778 8.222c-4.296-4.296-11.26-4.296-15.556 0A1 1 0 01.808 6.808c5.076-5.077 13.308-5.077 18.384 0a1 1 0 01-1.414 1.414zM14.95 11.05a7 7 0 00-9.9 0 1 1 0 01-1.414-1.414 9 9 0 0112.728 0 1 1 0 01-1.414 1.414zM12.12 13.88a3 3 0 00-4.242 0 1 1 0 01-1.415-1.415 5 5 0 017.072 0 1 1 0 01-1.415 1.415zM9 16a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>`, // Septembre - Rentrée/Wifi
    10: `<svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>`, // Octobre - Étoile/Citrouille
    11: `<svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2C7.243 2 5 4.243 5 7c0 2.757 2.243 5 5 5s5-2.243 5-5c0-2.757-2.243-5-5-5zm0 8a3 3 0 110-6 3 3 0 010 6zm-1 7c0-1.104.896-2 2-2s2 .896 2 2v1H7v-1c0-1.104.896-2 2-2z"/><path d="M10 14c-2.21 0-4-1.79-4-4 0-1.104.896-2 2-2s2 .896 2 2c0 1.104-.896 2-2 2z"/></svg>`, // Novembre - Feuille d'automne
    12: `<svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2l-3 5h2v2H7l3 5H8v2h8v-2h-2l3-5h-4V7h2z"/><rect x="10" y="19" width="4" height="3" rx="1" fill="#8B5C2F"/></svg>`, // Décembre - Sapin de Noël
  };
  return icons[monthNumber] || '';
};

// Initial load
onMounted(() => {
  loadAnalytics();
  loadInsights();
  loadMonthlyRevenue();
});
</script>
