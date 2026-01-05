<template>
  <div class="min-h-screen relative overflow-hidden">
    <!-- Animated gradient background with orange and blue theme -->
    <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-blue-50"></div>
    
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <!-- Large animated circles with orange and blue -->
      <div class="absolute -top-40 -left-40 w-96 h-96 bg-moov-orange rounded-full opacity-10 blur-3xl animate-pulse"></div>
      <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600 rounded-full opacity-10 blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-orange-400 rounded-full opacity-5 blur-3xl animate-pulse" style="animation-delay: 0.75s;"></div>
      <div class="absolute top-20 left-1/4 w-72 h-72 bg-blue-500 rounded-full opacity-5 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
      
      <!-- Floating geometric shapes - orange and blue themed -->
      <div class="absolute top-32 right-24 w-20 h-20 border-4 border-moov-orange/20 rounded-lg rotate-45 animate-bounce" style="animation-duration: 3s;"></div>
      <div class="absolute bottom-40 left-40 w-16 h-16 bg-blue-500/10 rounded-full animate-bounce" style="animation-duration: 4s; animation-delay: 1s;"></div>
      <div class="absolute top-1/4 right-1/3 w-12 h-12 border-4 border-orange-400/30 rounded-full animate-bounce" style="animation-duration: 5s; animation-delay: 0.5s;"></div>
      <div class="absolute bottom-1/4 left-1/3 w-10 h-10 bg-blue-400/10 rounded-lg rotate-12 animate-bounce" style="animation-duration: 6s; animation-delay: 1.5s;"></div>
      <div class="absolute top-2/3 right-1/4 w-8 h-8 border-4 border-moov-orange-light/20 rounded-lg rotate-45 animate-bounce" style="animation-duration: 4.5s; animation-delay: 0.8s;"></div>
      
      <!-- Subtle grid pattern overlay -->
      <div class="absolute inset-0 opacity-[0.015]" style="background-image: radial-gradient(circle at 1px 1px, rgb(0 0 0) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <Navbar />
    
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 relative z-10">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Tableau de Bord Dealer</h1>
          <p class="text-sm text-gray-600 mt-1">Vue d'ensemble de vos commissions et activités réseau</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <!-- Year Selector -->
          <select
            v-model="selectedYear"
            class="px-4 py-2 rounded-xl font-semibold text-sm bg-white/90 text-gray-700 border border-gray-200 hover:bg-white transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-moov-orange"
          >
            <option v-for="year in availableYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
          
          <!-- Period Selector (visible uniquement pour année en cours) -->
          <div v-if="isCurrentYear" class="flex items-center gap-2">
            <button
              v-for="period in periods"
              :key="period.value"
              @click="changePeriod(period.value)"
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
          
          <!-- Filtres pour années passées -->
          <div v-else class="flex items-center gap-2">
            <!-- Sélecteur de type de période -->
            <select
              v-model="historicalPeriodType"
              class="px-4 py-2 rounded-xl font-semibold text-sm bg-white/90 text-gray-700 border border-gray-200 hover:bg-white transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-moov-orange"
            >
              <option value="year">Année complète</option>
              <option value="month">Par mois</option>
              <option value="week">Par semaine</option>
            </select>
            
            <!-- Sélecteur de mois (si type = month) -->
            <select
              v-if="historicalPeriodType === 'month'"
              v-model.number="selectedMonth"
              class="px-4 py-2 rounded-xl font-semibold text-sm bg-white/90 text-gray-700 border border-gray-200 hover:bg-white transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-moov-orange"
            >
              <option v-for="month in monthsList" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
            
            <!-- Sélecteur de semaine (si type = week) -->
            <select
              v-if="historicalPeriodType === 'week'"
              v-model.number="selectedWeek"
              class="px-4 py-2 rounded-xl font-semibold text-sm bg-white/90 text-gray-700 border border-gray-200 hover:bg-white transition-all cursor-pointer focus:outline-none focus:ring-2 focus:ring-moov-orange"
            >
              <option v-for="week in weeksList" :key="week.value" :value="week.value">
                {{ week.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Content - Progressive Loading with Skeletons -->
      <div class="space-y-6">
        <!-- Date Range Info -->
        <div v-if="analytics.kpi" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-4">
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
          <!-- Skeleton KPI Cards -->
          <template v-if="loadingStates.kpi">
            <div v-for="i in 4" :key="i" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
              <div class="animate-pulse">
                <div class="w-12 h-12 bg-gray-200 rounded-xl mb-4"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div class="h-8 bg-gray-200 rounded w-full mb-2"></div>
                <div class="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          </template>
          
          <!-- Real KPI Cards -->
          <template v-else-if="analytics.kpi">
          <!-- Commissions Totales -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Commissions Totales</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.kpi.commissions.total) }}</p>
            <div class="flex items-center gap-2 mt-2">
              <p class="text-xs text-gray-500">Moy: {{ formatCurrency(analytics.kpi.commissions.moyenne_par_transaction) }}/tx</p>
              <div v-if="analytics.kpi.commissions.comparison" class="ml-auto flex items-center gap-1">
                <svg v-if="analytics.kpi.commissions.comparison.total >= 0" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg v-else class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span :class="analytics.kpi.commissions.comparison.total >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold">
                  {{ Math.abs(analytics.kpi.commissions.comparison.total) }}%
                </span>
              </div>
            </div>
          </div>

          <!-- Retenues Totales -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Retenues Totales</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.kpi.retenues.total) }}</p>
            <div class="flex items-center gap-2 mt-2">
              <p class="text-xs text-gray-500">Dépôt + Retrait</p>
              <div v-if="analytics.kpi.retenues.comparison" class="ml-auto flex items-center gap-1">
                <svg v-if="analytics.kpi.retenues.comparison.total >= 0" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg v-else class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span :class="analytics.kpi.retenues.comparison.total >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold">
                  {{ Math.abs(analytics.kpi.retenues.comparison.total) }}%
                </span>
              </div>
            </div>
          </div>

          <!-- Total Transactions -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 hover:shadow-2xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-600 mb-1">Total Transactions</h3>
            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(analytics.kpi.transactions.total) }}</p>
            <div class="flex items-center gap-2 mt-2">
              <p class="text-xs text-gray-500">Dépôts + Retraits</p>
              <div v-if="analytics.kpi.transactions.comparison" class="ml-auto flex items-center gap-1">
                <svg v-if="analytics.kpi.transactions.comparison.total >= 0" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg v-else class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span :class="analytics.kpi.transactions.comparison.total >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold">
                  {{ Math.abs(analytics.kpi.transactions.comparison.total) }}%
                </span>
              </div>
            </div>
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
            <div class="flex items-center gap-2 mt-2">
              <p class="text-xs text-gray-500">Avec transactions</p>
              <div v-if="analytics.kpi.pdv_actifs_comparison !== undefined" class="ml-auto flex items-center gap-1">
                <svg v-if="analytics.kpi.pdv_actifs_comparison >= 0" class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg v-else class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <span :class="analytics.kpi.pdv_actifs_comparison >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-semibold">
                  {{ Math.abs(analytics.kpi.pdv_actifs_comparison) }}%
                </span>
              </div>
            </div>
          </div>
          </template>
        </div>

        <!-- Commissions & Retenues Detail -->
        <div v-if="loadingStates.kpi" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div v-for="i in 2" :key="i" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <div class="animate-pulse">
              <div class="h-6 bg-gray-200 rounded w-1/2 mb-4"></div>
              <div class="space-y-4">
                <div v-for="j in 3" :key="j" class="p-4 bg-gray-100 rounded-xl">
                  <div class="flex justify-between">
                    <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                    <div class="h-6 bg-gray-200 rounded w-1/4"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="analytics.kpi" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Commissions -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Détail des Commissions</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">Commissions Dépôt</p>
                <p class="text-xl font-bold text-green-600">{{ formatCurrency(analytics.kpi.commissions.depot) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-blue-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">Commissions Retrait</p>
                <p class="text-xl font-bold text-blue-600">{{ formatCurrency(analytics.kpi.commissions.retrait) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-gradient-to-r from-moov-orange/10 to-moov-orange-dark/10 rounded-xl border-2 border-moov-orange/30">
                <p class="text-sm font-bold text-gray-700">Total</p>
                <p class="text-2xl font-bold text-moov-orange">{{ formatCurrency(analytics.kpi.commissions.total) }}</p>
              </div>
            </div>
          </div>

          <!-- Retenues -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Détail des Retenues</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">Retenues Dépôt</p>
                <p class="text-xl font-bold text-red-600">{{ formatCurrency(analytics.kpi.retenues.depot) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-orange-50 rounded-xl">
                <p class="text-sm font-semibold text-gray-600">Retenues Retrait</p>
                <p class="text-xl font-bold text-orange-600">{{ formatCurrency(analytics.kpi.retenues.retrait) }}</p>
              </div>
              <div class="flex items-center justify-between p-4 bg-gradient-to-r from-red-50 to-orange-50 rounded-xl border-2 border-red-300">
                <p class="text-sm font-bold text-gray-700">Total</p>
                <p class="text-2xl font-bold text-red-700">{{ formatCurrency(analytics.kpi.retenues.total) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- GIVE Network Stats -->
        <div v-if="loadingStates.giveStats" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-6"></div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div v-for="i in 2" :key="i" class="space-y-4">
                <div class="h-5 bg-gray-200 rounded w-1/4 mb-4"></div>
                <div v-for="j in 3" :key="j" class="p-4 bg-gray-100 rounded-xl">
                  <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
                  <div class="h-6 bg-gray-200 rounded w-2/3 mb-1"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="analytics.give_network_stats" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Statistiques GIVE (Transferts Réseau)
          </h3>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- GIVE Envoyés -->
            <div class="space-y-4">
              <h4 class="font-semibold text-gray-700 border-b pb-2">GIVE Envoyés</h4>
              
              <div class="p-4 bg-blue-50 rounded-xl">
                <p class="text-sm text-gray-600 mb-1">Total</p>
                <p class="text-2xl font-bold text-blue-700">{{ formatCurrency(analytics.give_network_stats.envoyes.total.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.envoyes.total.count) }} transferts</p>
              </div>
              
              <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm font-semibold text-gray-700">Dans le réseau</p>
                  <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded-full">
                    {{ analytics.give_network_stats.envoyes.in_network.percentage }}%
                  </span>
                </div>
                <p class="text-xl font-bold text-green-700">{{ formatCurrency(analytics.give_network_stats.envoyes.in_network.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.envoyes.in_network.count) }} transferts</p>
              </div>
              
              <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm font-semibold text-gray-700">Hors réseau</p>
                  <span class="px-2 py-1 bg-gray-600 text-white text-xs font-bold rounded-full">
                    {{ analytics.give_network_stats.envoyes.out_network.percentage }}%
                  </span>
                </div>
                <p class="text-xl font-bold text-gray-700">{{ formatCurrency(analytics.give_network_stats.envoyes.out_network.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.envoyes.out_network.count) }} transferts</p>
              </div>
            </div>

            <!-- GIVE Reçus -->
            <div class="space-y-4">
              <h4 class="font-semibold text-gray-700 border-b pb-2">GIVE Reçus</h4>
              
              <div class="p-4 bg-purple-50 rounded-xl">
                <p class="text-sm text-gray-600 mb-1">Total</p>
                <p class="text-2xl font-bold text-purple-700">{{ formatCurrency(analytics.give_network_stats.recus.total.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.recus.total.count) }} transferts</p>
              </div>
              
              <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm font-semibold text-gray-700">Dans le réseau</p>
                  <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded-full">
                    {{ analytics.give_network_stats.recus.in_network.percentage }}%
                  </span>
                </div>
                <p class="text-xl font-bold text-green-700">{{ formatCurrency(analytics.give_network_stats.recus.in_network.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.recus.in_network.count) }} transferts</p>
              </div>
              
              <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-sm font-semibold text-gray-700">Hors réseau</p>
                  <span class="px-2 py-1 bg-gray-600 text-white text-xs font-bold rounded-full">
                    {{ analytics.give_network_stats.recus.out_network.percentage }}%
                  </span>
                </div>
                <p class="text-xl font-bold text-gray-700">{{ formatCurrency(analytics.give_network_stats.recus.out_network.amount) }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.recus.out_network.count) }} transferts</p>
              </div>
            </div>
          </div>

          <!-- Balance GIVE -->
          <div class="mt-6 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border-2 border-indigo-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-gray-700 mb-1">Balance GIVE (Reçus - Envoyés)</p>
                <p class="text-xs text-gray-500">{{ formatNumber(analytics.give_network_stats.balance.count) }} transferts nets</p>
              </div>
              <p :class="[
                'text-3xl font-bold',
                analytics.give_network_stats.balance.amount >= 0 ? 'text-green-600' : 'text-red-600'
              ]">
                {{ analytics.give_network_stats.balance.amount >= 0 ? '+' : '' }}{{ formatCurrency(analytics.give_network_stats.balance.amount) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Top PDV par Commissions -->
        <div v-if="loadingStates.topPdv" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
            <div class="space-y-3">
              <div v-for="i in 8" :key="i" class="flex items-center gap-4 p-4 rounded-xl border border-gray-100">
                <div class="w-8 h-8 bg-gray-200 rounded-full"></div>
                <div class="flex-1">
                  <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                </div>
                <div class="text-right">
                  <div class="h-5 bg-gray-200 rounded w-20 mb-1"></div>
                  <div class="h-3 bg-gray-200 rounded w-16"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else-if="analytics.commissions_by_pdv" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-moov-orange" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
            </svg>
            Top 20 PDV (par commissions générées)
          </h3>
          <div class="space-y-3 max-h-96 overflow-y-auto">
            <div
              v-for="(pdv, index) in analytics.commissions_by_pdv"
              :key="pdv.pdv_numero"
              class="flex items-center gap-4 p-4 rounded-xl hover:bg-moov-orange/5 transition-colors border border-gray-100"
            >
              <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-moov-orange to-moov-orange-dark text-white flex items-center justify-center font-bold text-sm">
                {{ index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ pdv.nom_point }}</p>
                <p class="text-xs text-gray-500">{{ pdv.pdv_numero }} • {{ pdv.region }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(pdv.total_transactions) }} tx • {{ formatCurrency(pdv.commission_par_transaction) }}/tx</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-moov-orange">{{ formatCurrency(pdv.commissions_generees) }}</p>
                <p class="text-xs text-gray-500">Retenues: {{ formatCurrency(pdv.retenues_total) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Revenue Chart -->
        <div v-if="loadingStates.monthlyRevenue" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-6"></div>
            <div class="h-96 bg-gray-100 rounded-xl"></div>
          </div>
        </div>
        <div v-else-if="monthlyRevenue.length" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Évolution Mensuelle {{ selectedYear }}</h3>
          <div style="height: 400px;">
            <Line :key="monthlyChartKey" :data="monthlyChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Evolution Timeline -->
        <div v-if="loadingStates.evolution" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-6"></div>
            <div class="h-[350px] bg-gray-100 rounded-xl"></div>
          </div>
        </div>
        <div v-else-if="analytics.evolution && analytics.evolution.length" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Évolution Quotidienne</h3>
          <div style="height: 350px;">
            <Line :key="evolutionChartKey" :data="evolutionChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- PDV Actifs Evolution -->
        <div v-if="loadingStates.evolution" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <div class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-6"></div>
            <div class="h-[350px] bg-gray-100 rounded-xl"></div>
          </div>
        </div>
        <div v-else-if="analytics.evolution && analytics.evolution.length" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Évolution des PDV Actifs</h3>
          <div style="height: 350px;">
            <Line :key="pdvActifsChartKey" :data="pdvActifsChartData" :options="pdvActifsChartOptions" />
          </div>
        </div>

        <!-- Error State -->
        <div v-if="error" class="bg-white/90 backdrop-blur-md border border-red-200 shadow-2xl rounded-2xl p-8 text-center">
          <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-lg font-semibold text-gray-900 mb-2">Erreur de chargement</p>
          <p class="text-sm text-gray-600">{{ error }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';
import Navbar from '../components/Navbar.vue';
import dealerAnalyticsService from '../services/dealerAnalyticsService';
import { useToast } from '../composables/useToast';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const { toast } = useToast();

const analytics = ref({});
const analyticsCache = ref({}); // Cache frontend pour toutes les périodes
const monthlyRevenue = ref([]);
const loadingStates = ref({
  kpi: false,
  evolution: false,
  topPdv: false,
  giveStats: false,
  monthlyRevenue: false
});
const error = ref(null);

const selectedPeriod = ref('week');
const selectedYear = ref(new Date().getFullYear());
const historicalPeriodType = ref('year'); // 'year', 'month', 'week'
const selectedMonth = ref(new Date().getMonth() + 1);
const selectedWeek = ref(1);

const periods = [
  { label: 'Jour', value: 'day' },
  { label: 'Semaine', value: 'week' },
  { label: 'Mois', value: 'month' },
  { label: 'Trimestre', value: 'quarter' }
];

const monthsList = [
  { label: 'Janvier', value: 1 },
  { label: 'Février', value: 2 },
  { label: 'Mars', value: 3 },
  { label: 'Avril', value: 4 },
  { label: 'Mai', value: 5 },
  { label: 'Juin', value: 6 },
  { label: 'Juillet', value: 7 },
  { label: 'Août', value: 8 },
  { label: 'Septembre', value: 9 },
  { label: 'Octobre', value: 10 },
  { label: 'Novembre', value: 11 },
  { label: 'Décembre', value: 12 }
];

const currentYear = new Date().getFullYear();
const availableYears = computed(() => {
  const years = [];
  for (let year = currentYear; year >= 2020; year--) {
    years.push(year);
  }
  return years;
});

const isCurrentYear = computed(() => selectedYear.value === currentYear);

// État de chargement global - true si au moins une section charge lors du premier chargement
const loading = computed(() => {
  const hasNoData = !analytics.value.kpi && !analytics.value.evolution && !analytics.value.commissions_by_pdv && !analytics.value.give_network_stats;
  const isLoading = loadingStates.value.kpi || loadingStates.value.evolution || loadingStates.value.topPdv || loadingStates.value.giveStats;
  return hasNoData && isLoading;
});

const weeksList = computed(() => {
  const weeks = [];
  const year = selectedYear.value;
  const lastWeek = getWeeksInYear(year);
  
  for (let week = 1; week <= lastWeek; week++) {
    const weekStart = getWeekStartDate(year, week);
    const weekEnd = getWeekEndDate(year, week);
    weeks.push({
      value: week,
      label: `Sem. ${week} (${weekStart} - ${weekEnd})`
    });
  }
  
  return weeks;
});

// Utilitaires pour les semaines
const getWeeksInYear = (year) => {
  const lastDay = new Date(year, 11, 31);
  const lastWeek = getWeekNumber(lastDay);
  return lastWeek === 1 ? 52 : lastWeek;
};

const getWeekNumber = (date) => {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const dayNum = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - dayNum);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
};

const getWeekStartDate = (year, week) => {
  const jan4 = new Date(year, 0, 4);
  const dayOfWeek = jan4.getDay() || 7;
  const weekStart = new Date(jan4.getTime() - (dayOfWeek - 1) * 86400000);
  weekStart.setDate(weekStart.getDate() + (week - 1) * 7);
  return `${weekStart.getDate().toString().padStart(2, '0')}/${(weekStart.getMonth() + 1).toString().padStart(2, '0')}`;
};

const getWeekEndDate = (year, week) => {
  const jan4 = new Date(year, 0, 4);
  const dayOfWeek = jan4.getDay() || 7;
  const weekStart = new Date(jan4.getTime() - (dayOfWeek - 1) * 86400000);
  weekStart.setDate(weekStart.getDate() + (week - 1) * 7 + 6);
  return `${weekStart.getDate().toString().padStart(2, '0')}/${(weekStart.getMonth() + 1).toString().padStart(2, '0')}`;
};

// Construction des params de période
const getPeriodParams = () => {
  let params = {};
  
  if (isCurrentYear.value) {
    params = { period: selectedPeriod.value };
  } else {
    if (historicalPeriodType.value === 'year') {
      params = { 
        period: 'historical_year',
        year: selectedYear.value 
      };
    } else if (historicalPeriodType.value === 'month') {
      params = { 
        period: 'historical_month',
        year: selectedYear.value,
        month: selectedMonth.value
      };
    } else if (historicalPeriodType.value === 'week') {
      params = { 
        period: 'historical_week',
        year: selectedYear.value,
        week: selectedWeek.value
      };
    }
  }
  
  return params;
};

// Génér une clé de cache unique
const getCacheKey = (type, params) => {
  return `${type}_${JSON.stringify(params)}`;
};

// Charger les KPI avec cache
const loadKpi = async () => {
  try {
    loadingStates.value.kpi = true;
    const params = getPeriodParams();
    const cacheKey = getCacheKey('kpi', params);
    
    // Vérifier le cache
    if (analyticsCache.value[cacheKey]) {
      analytics.value = { ...analytics.value, ...analyticsCache.value[cacheKey] };
      loadingStates.value.kpi = false;
      return;
    }
    
    const data = await dealerAnalyticsService.getKpi(params);
    analyticsCache.value[cacheKey] = data;
    analytics.value = { ...analytics.value, ...data };
  } catch (err) {
    console.error('Erreur lors du chargement des KPI:', err);
    toast.error('Erreur de chargement des KPI');
  } finally {
    loadingStates.value.kpi = false;
  }
};

// Charger l'évolution avec cache
const loadEvolution = async () => {
  try {
    loadingStates.value.evolution = true;
    const params = getPeriodParams();
    const cacheKey = getCacheKey('evolution', params);
    
    // Vérifier le cache
    if (analyticsCache.value[cacheKey]) {
      analytics.value = { ...analytics.value, ...analyticsCache.value[cacheKey] };
      loadingStates.value.evolution = false;
      return;
    }
    
    const data = await dealerAnalyticsService.getEvolution(params);
    analyticsCache.value[cacheKey] = data;
    analytics.value = { ...analytics.value, ...data };
  } catch (err) {
    console.error('Erreur lors du chargement de l\'évolution:', err);
    toast.error('Erreur de chargement de l\'évolution');
  } finally {
    loadingStates.value.evolution = false;
  }
};

// Charger les top PDV avec cache
const loadTopPdv = async () => {
  try {
    loadingStates.value.topPdv = true;
    const params = getPeriodParams();
    const cacheKey = getCacheKey('topPdv', params);
    
    if (analyticsCache.value[cacheKey]) {
      analytics.value = { ...analytics.value, ...analyticsCache.value[cacheKey] };
      loadingStates.value.topPdv = false;
      return;
    }
    
    const data = await dealerAnalyticsService.getTopPdv(params);
    analyticsCache.value[cacheKey] = data;
    analytics.value = { ...analytics.value, ...data };
  } catch (err) {
    console.error('Erreur lors du chargement des top PDV:', err);
    toast.error('Erreur de chargement des top PDV');
  } finally {
    loadingStates.value.topPdv = false;
  }
};

// Charger les stats GIVE avec cache
const loadGiveStats = async () => {
  try {
    loadingStates.value.giveStats = true;
    const params = getPeriodParams();
    const cacheKey = getCacheKey('giveStats', params);
    
    if (analyticsCache.value[cacheKey]) {
      // Cloner pour forcer la réactivité lors des changements de filtres
      analytics.value = { ...analytics.value, ...JSON.parse(JSON.stringify(analyticsCache.value[cacheKey])) };
      loadingStates.value.giveStats = false;
      return;
    }
    
    const data = await dealerAnalyticsService.getGiveStats(params);
    analyticsCache.value[cacheKey] = data;
    analytics.value = { ...analytics.value, ...JSON.parse(JSON.stringify(data)) };
  } catch (err) {
    console.error('Erreur lors du chargement des stats GIVE:', err);
    toast.error('Erreur de chargement des stats GIVE');
  } finally {
    loadingStates.value.giveStats = false;
  }
};

// Charger les revenus mensuels
const loadMonthlyRevenue = async () => {
  try {
    loadingStates.value.monthlyRevenue = true;
    monthlyRevenue.value = await dealerAnalyticsService.getMonthlyRevenue(selectedYear.value);
  } catch (err) {
    console.error('Erreur lors du chargement des revenus mensuels:', err);
  } finally {
    loadingStates.value.monthlyRevenue = false;
  }
};

// Charger toutes les données en parallèle
const loadAllData = () => {
  error.value = null;
  loadKpi();
  loadEvolution();
  loadTopPdv();
  loadGiveStats();
  loadMonthlyRevenue();
};

// Pré-charger toutes les périodes en arrière-plan
const preloadAllPeriods = async () => {
  if (!isCurrentYear.value) return;
  
  const periods = ['day', 'week', 'month', 'quarter'];
  // Charger en parallèle
  await Promise.all(
    periods.map(async (period) => {
      if (period === selectedPeriod.value) return; // Déjà chargé
      try {
        const params = { period };
        const cacheKey = getCacheKey('kpi', params);
        if (!analyticsCache.value[cacheKey]) {
          await dealerAnalyticsService.getKpi(params).then(data => {
            analyticsCache.value[getCacheKey('kpi', params)] = data;
          });
          await dealerAnalyticsService.getEvolution(params).then(data => {
            analyticsCache.value[getCacheKey('evolution', params)] = data;
          });
          await dealerAnalyticsService.getTopPdv(params).then(data => {
            analyticsCache.value[getCacheKey('topPdv', params)] = data;
          });
          await dealerAnalyticsService.getGiveStats(params).then(data => {
            analyticsCache.value[getCacheKey('giveStats', params)] = data;
          });
        }
      } catch (error) {
        console.error(`Error preloading ${period}:`, error);
      }
    })
  );
};

// Changer la période
const changePeriod = (period) => {
  selectedPeriod.value = period;
};

onMounted(async () => {
  loadAllData();
  // Pré-charger les autres périodes en arrière-plan
  preloadAllPeriods();
});

watch(selectedPeriod, () => {
  if (isCurrentYear.value) {
    loadKpi();
    loadEvolution();
    loadTopPdv();
    loadGiveStats();
  }
});

watch(selectedYear, () => {
  // Réinitialiser les filtres lors du changement d'année
  historicalPeriodType.value = 'year';
  selectedMonth.value = 1;
  selectedWeek.value = 1;
  
  loadAllData();
});

watch(historicalPeriodType, () => {
  if (!isCurrentYear.value) {
    loadKpi();
    loadEvolution();
    loadTopPdv();
    loadGiveStats();
  }
});

watch(selectedMonth, () => {
  if (!isCurrentYear.value && historicalPeriodType.value === 'month') {
    loadKpi();
    loadEvolution();
    loadTopPdv();
    loadGiveStats();
  }
});

watch(selectedWeek, () => {
  if (!isCurrentYear.value && historicalPeriodType.value === 'week') {
    loadKpi();
    loadEvolution();
    loadTopPdv();
    loadGiveStats();
  }
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
    tooltip: {
      mode: 'index',
      intersect: false,
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        maxTicksLimit: 10,
      },
    },
  },
};

const monthlyChartData = computed(() => ({
  labels: monthlyRevenue.value.map(m => m.label),
  datasets: [
    {
      label: 'Commissions Totales',
      data: monthlyRevenue.value.map(m => m.commissions_total),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.3,
      fill: true,
    },
    {
      label: 'Retenues Totales',
      data: monthlyRevenue.value.map(m => m.retenues_total),
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      tension: 0.3,
      fill: true,
    },
  ],
}));

const evolutionChartData = computed(() => ({
  labels: analytics.value?.evolution?.map(e => formatDate(e.date)) || [],
  datasets: [
    {
      label: 'Commissions',
      data: analytics.value?.evolution?.map(e => e.commissions) || [],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.3,
      fill: true,
    },
    {
      label: 'Retenues',
      data: analytics.value?.evolution?.map(e => e.retenues) || [],
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      tension: 0.3,
      fill: true,
    },
  ],
}));

const pdvActifsChartData = computed(() => ({
  labels: analytics.value?.evolution?.map(e => formatDate(e.date)) || [],
  datasets: [
    {
      label: 'PDV Actifs (uniques)',
      data: analytics.value?.evolution?.map(e => e.pdv_actifs) || [],
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.3,
      fill: true,
    },
  ],
}));

const pdvActifsChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: function(context) {
          return context.dataset.label + ': ' + context.parsed.y + ' PDV';
        }
      }
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
        maxTicksLimit: 10,
        callback: function(value) {
          if (Math.floor(value) === value) {
            return value;
          }
        }
      },
    },
  },
};

// Clés de rendu pour forcer le redraw des graphiques lors des changements de filtres
const monthlyChartKey = computed(() => `monthly-${selectedYear.value}`);
const evolutionChartKey = computed(() => `evo-${selectedYear.value}-${selectedPeriod.value}-${historicalPeriodType.value}-${selectedMonth.value}-${selectedWeek.value}`);
const pdvActifsChartKey = computed(() => `pdv-${selectedYear.value}-${selectedPeriod.value}-${historicalPeriodType.value}-${selectedMonth.value}-${selectedWeek.value}`);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('fr-FR').format(value);
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date);
};
</script>

<style scoped>
.bg-gradient-mesh {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-size: 400% 400%;
  animation: gradient 15s ease infinite;
  min-height: 100vh;
}

@keyframes gradient {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
</style>
