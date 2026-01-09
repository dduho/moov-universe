<template>
  <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
      <div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900">Analyse de Rentabilité</h3>
        <p class="text-xs sm:text-sm text-gray-600 mt-1">ROI et marges par PDV, agent ou région</p>
      </div>
      <div class="flex gap-2 mt-3 sm:mt-0">
        <button 
          @click="exportData" 
          :disabled="loading || !data.length"
          class="flex items-center gap-2 px-3 py-2 text-xs sm:text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Exporter
        </button>
        <button 
          @click="fetchData" 
          :disabled="loading"
          class="p-2 text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50"
          title="Actualiser"
        >
          <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="space-y-4 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Période</label>
          <select v-model="filters.period" @change="fetchData" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="all">Toutes les données</option>
            <option value="7d">7 derniers jours</option>
            <option value="30d">30 derniers jours</option>
            <option value="90d">90 derniers jours</option>
            <option value="custom">Personnalisée</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Grouper par</label>
          <select v-model="filters.groupBy" @change="fetchData" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="pdv">PDV</option>
            <option value="dealer">Dealer</option>
            <option value="region">Région</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Trier par</label>
          <select v-model="filters.sortBy" @change="fetchData" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="roi">ROI</option>
            <option value="margin">Marge</option>
            <option value="revenue">Revenu</option>
            <option value="ca">CA</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Limite</label>
          <select v-model="filters.limit" @change="fetchData" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option :value="10">Top 10</option>
            <option :value="20">Top 20</option>
            <option :value="50">Top 50</option>
            <option :value="100">Top 100</option>
            <option :value="0">Tous</option>
          </select>
        </div>
      </div>
      
      <!-- Champs de dates personnalisées -->
      <div v-if="filters.period === 'custom'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <div>
          <label class="block text-xs font-medium text-blue-700 mb-1">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Date de début
          </label>
          <input 
            v-model="filters.startDate" 
            @change="fetchData"
            type="date" 
            class="w-full px-3 py-2 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-blue-700 mb-1">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Date de fin
          </label>
          <input 
            v-model="filters.endDate" 
            @change="fetchData"
            type="date" 
            class="w-full px-3 py-2 text-sm border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
          />
        </div>
        <div class="col-span-full">
          <p class="text-xs text-blue-600 flex items-center gap-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            Sélectionnez une période spécifique pour analyser les données de rentabilité
          </p>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="summary" class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
        <div class="text-xs text-blue-600 font-medium mb-1">CA Total (RETRAIT_KEYCOST)</div>
        <div class="text-lg sm:text-2xl font-bold text-blue-900">{{ formatCurrency(summary.total_ca) }}</div>
        <div class="text-xs text-blue-600 mt-1">{{ summary.count }} {{ filters.groupBy === 'pdv' ? 'PDVs' : filters.groupBy === 'dealer' ? 'Dealers' : 'Régions' }}</div>
      </div>

      <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4">
        <div class="text-xs text-red-600 font-medium mb-1">Commissions Payées</div>
        <div class="text-lg sm:text-2xl font-bold text-red-900">{{ formatCurrency(summary.total_cost) }}</div>
        <div class="text-xs text-red-600 mt-1">Dealer + PDV</div>
      </div>

      <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
        <div class="text-xs text-green-600 font-medium mb-1">Revenue Moov</div>
        <div class="text-lg sm:text-2xl font-bold text-green-900">{{ formatCurrency(summary.total_revenue) }}</div>
        <div class="text-xs text-green-600 mt-1">ROI: {{ formatPercent(summary.avg_roi) }}</div>
      </div>

      <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
        <div class="text-xs text-purple-600 font-medium mb-1">Marge Moyenne</div>
        <div class="text-lg sm:text-2xl font-bold text-purple-900">{{ formatPercent(summary.avg_margin_rate) }}</div>
        <div class="text-xs text-purple-600 mt-1">{{ formatCurrency(summary.total_margin) }}</div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <div>
          <h4 class="text-sm font-semibold text-red-800">Erreur de chargement</h4>
          <p class="text-xs text-red-600 mt-1">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div v-else-if="data.length" class="overflow-x-auto -mx-4 sm:mx-0">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ filters.groupBy === 'pdv' ? 'PDV' : filters.groupBy === 'dealer' ? 'Dealer' : 'Région' }}
            </th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">CA</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Commissions Payées</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenu Moov</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Marge</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ROI</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="item in data" :key="item.id || item.name" class="hover:bg-gray-50 transition-colors">
            <td class="px-3 py-4">
              <div v-if="filters.groupBy === 'pdv'">
                <div class="text-sm font-medium text-gray-900">{{ item.pdv_name || item.name }}</div>
                <div class="text-xs text-blue-600 font-mono">{{ item.pdv_numero || item.numero }}</div>
                <div class="text-xs text-gray-500 mt-0.5">
                  <span class="inline-flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ item.dealer_name || item.dealer }}
                  </span>
                </div>
                <div v-if="item.region" class="text-xs text-gray-400 mt-0.5">📍 {{ item.region }}</div>
              </div>
              <div v-else-if="filters.groupBy === 'dealer'">
                <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                  <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ item.dealer_name || item.name }}
                </div>
                <div v-if="item.dealer_code || item.code" class="text-xs text-indigo-600 font-mono mt-0.5">ID: {{ item.dealer_code || item.code }}</div>
                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m2 0v-7a2 2 0 012-2h2a2 2 0 012 2v7M7 21h2M19 21h2" />
                  </svg>
                  {{ item.pdv_count || 0 }} PDVs gérés
                </div>
                <div v-if="item.region" class="text-xs text-gray-400 mt-0.5">📍 {{ item.region }}</div>
              </div>
              <div v-else-if="filters.groupBy === 'region'">
                <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ item.region_name || item.name }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ item.dealer_count || item.dealers_count || 0 }} dealers
                </div>
                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m2 0v-7a2 2 0 012-2h2a2 2 0 012 2v7M7 21h2M19 21h2" />
                  </svg>
                  {{ item.pdv_count || 0 }} PDVs
                </div>
              </div>
            </td>
            <td class="px-3 py-4 whitespace-nowrap text-right text-sm text-gray-900 hidden sm:table-cell">
              {{ formatCurrency(item.total_ca) }}
            </td>
            <td class="px-3 py-4 whitespace-nowrap text-right text-sm text-gray-600 hidden lg:table-cell">
              {{ formatCurrency(item.estimated_commission) }}
            </td>
            <td class="px-3 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
              {{ formatCurrency(item.revenue) }}
            </td>
            <td class="px-3 py-4 whitespace-nowrap text-right">
              <span :class="getMarginClass(item.margin_rate)" class="text-sm font-medium">
                {{ formatPercent(item.margin_rate) }}
              </span>
            </td>
            <td class="px-3 py-4 whitespace-nowrap text-right">
              <span :class="getRoiClass(item.roi)" class="text-sm font-bold">
                {{ formatPercent(item.roi) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune donnée</h3>
      <p class="mt-1 text-sm text-gray-500">Aucune transaction trouvée pour cette période</p>
    </div>

    <!-- Top & Bottom Performers -->
    <div v-if="topPerformers.length && bottomPerformers.length" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6 pt-6 border-t border-gray-200">
      <!-- Top Performers -->
      <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
          </svg>
          Top 5 Meilleurs
        </h4>
        <div class="space-y-2">
          <div v-for="(item, index) in topPerformers" :key="index" class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-6 h-6 rounded-full bg-green-600 text-white text-xs font-bold">
                {{ index + 1 }}
              </div>
              <div>
                <div v-if="filters.groupBy === 'pdv'" class="text-sm font-medium text-gray-900">{{ item.pdv_name || item.name }}</div>
                <div v-else-if="filters.groupBy === 'dealer'" class="text-sm font-medium text-gray-900 flex items-center gap-1">
                  <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ item.dealer_name || item.name }}
                </div>
                <div v-else class="text-sm font-medium text-gray-900 flex items-center gap-1">
                  <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  </svg>
                  {{ item.region_name || item.name }}
                </div>
                <div v-if="filters.groupBy === 'pdv'" class="text-xs text-blue-600 font-mono">{{ item.pdv_numero || item.numero }}</div>
                <div v-else-if="filters.groupBy === 'dealer'" class="text-xs text-indigo-600">{{ (item.pdv_count || 0) }} PDVs</div>
                <div v-else class="text-xs text-green-600">{{ (item.dealer_count || item.dealers_count || 0) }} dealers</div>
                <div class="text-xs text-gray-500">{{ formatCurrency(item.total_ca) }} CA</div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold text-green-700">{{ formatPercent(item.roi) }}</div>
              <div class="text-xs text-green-600">ROI</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Performers -->
      <div>
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
          <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
          </svg>
          Top 5 À Améliorer
        </h4>
        <div class="space-y-2">
          <div v-for="(item, index) in bottomPerformers" :key="index" class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-6 h-6 rounded-full bg-red-600 text-white text-xs font-bold">
                {{ index + 1 }}
              </div>
              <div>
                <div v-if="filters.groupBy === 'pdv'" class="text-sm font-medium text-gray-900">{{ item.pdv_name || item.name }}</div>
                <div v-else-if="filters.groupBy === 'dealer'" class="text-sm font-medium text-gray-900 flex items-center gap-1">
                  <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  {{ item.dealer_name || item.name }}
                </div>
                <div v-else class="text-sm font-medium text-gray-900 flex items-center gap-1">
                  <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  </svg>
                  {{ item.region_name || item.name }}
                </div>
                <div v-if="filters.groupBy === 'pdv'" class="text-xs text-blue-600 font-mono">{{ item.pdv_numero || item.numero }}</div>
                <div v-else-if="filters.groupBy === 'dealer'" class="text-xs text-indigo-600">{{ (item.pdv_count || 0) }} PDVs</div>
                <div v-else class="text-xs text-green-600">{{ (item.dealer_count || item.dealers_count || 0) }} dealers</div>
                <div class="text-xs text-gray-500">{{ formatCurrency(item.total_ca) }} CA</div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-bold text-red-700">{{ formatPercent(item.roi) }}</div>
              <div class="text-xs text-red-600">ROI</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Parameters Info -->
    <div v-if="parameters" class="mt-6 pt-6 border-t border-gray-200">
      <details class="text-xs text-gray-500">
        <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">Modèle de calcul</summary>
        <div class="mt-2 space-y-1 pl-4 bg-blue-50 p-3 rounded-lg">
          <div class="font-semibold text-blue-800">• {{ parameters.model }}</div>
          <div class="mt-2 space-y-1">
            <div>• CA = RETRAIT_KEYCOST (frais retrait uniquement)</div>
            <div>• Commissions = DEALER_DEPOT + DEALER_RETRAIT + PDV_DEPOT + PDV_RETRAIT</div>
            <div>• Revenue = CA - Commissions totales</div>
            <div>• Ratio = (Revenue / Commissions) × 100 (rentabilité par FCFA de commission)</div>
            <div>• Période: {{ parameters.start_date }} → {{ parameters.end_date }}</div>
          </div>
        </div>
      </details>
    </div>

    <!-- Cache Info -->
    <div v-if="cacheInfo" class="mt-4 text-xs text-gray-500 flex justify-between items-center">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1">
          <svg class="w-3 h-3" :class="cacheInfo.is_cached ? 'text-green-600' : 'text-orange-600'" fill="currentColor" viewBox="0 0 20 20">
            <path v-if="cacheInfo.is_cached" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            <path v-else fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <span>Données en cache: {{ cacheInfo.is_cached ? 'Oui' : 'Non' }}</span>
        </div>
        <div v-if="cacheInfo.enabled" class="text-gray-400">
          (TTL: {{ cacheInfo.ttl_minutes }}min)
        </div>
        <div v-else class="text-orange-600">
          (Cache désactivé)
        </div>
      </div>
      <div class="text-gray-400">
        {{ new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import rentabilityService from '@/services/rentabilityService';

export default {
  name: 'RentabilityWidget',
  
  setup() {
    const loading = ref(false);
    const error = ref(null);
    const data = ref([]);
    const summary = ref(null);
    const topPerformers = ref([]);
    const bottomPerformers = ref([]);
    const parameters = ref(null);
    const cacheInfo = ref(null);
    
    const filters = ref({
      period: 'all', // Charger toutes les données par défaut
      startDate: '2025-12-11', // Date de début des vraies données
      endDate: '2026-01-06',   // Date de fin des vraies données
      groupBy: 'pdv',
      sortBy: 'roi',
      limit: 20
    });

    const fetchData = async () => {
      loading.value = true;
      error.value = null;
      
      try {
        console.log('🔄 Chargement données rentabilité avec filtres:', filters.value);
        const response = await rentabilityService.analyze(filters.value);
        console.log('📊 Réponse rentabilité:', response);
        
        if (response.success) {
          data.value = response.data.data || [];
          summary.value = response.data.summary || null;
          topPerformers.value = response.data.top_performers || [];
          bottomPerformers.value = response.data.bottom_performers || [];
          parameters.value = response.data.parameters || null;
          cacheInfo.value = response.data.cache_info || null;
          console.log('✅ Données chargées:', data.value.length, 'éléments');
        } else {
          error.value = response.error || 'Erreur lors du chargement des données';
          console.error('❌ Erreur analyse:', response.error);
        }
      } catch (err) {
        console.error('❌ Erreur analyse rentabilité:', err);
        error.value = 'Impossible de charger les données de rentabilité';
      } finally {
        loading.value = false;
      }
    };

    const exportData = () => {
      if (!data.value.length) return;
      
      const csv = [
        ['Nom', 'CA', 'Commission', 'Coûts', 'Revenu', 'Marge (%)', 'ROI (%)'].join(','),
        ...data.value.map(item => [
          `"${item.name}"`,
          item.total_ca,
          item.estimated_commission,
          item.total_cost,
          item.revenue,
          item.margin_rate,
          item.roi
        ].join(','))
      ].join('\n');
      
      const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `rentabilite_${filters.value.groupBy}_${Date.now()}.csv`;
      link.click();
    };

    const formatCurrency = (value) => {
      if (!value && value !== 0) return '-';
      return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value) + ' FCFA';
    };

    const formatPercent = (value) => {
      if (!value && value !== 0) return '-';
      return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
      }).format(value) + '%';
    };

    const getRoiClass = (roi) => {
      if (roi >= 50) return 'text-green-700';
      if (roi >= 20) return 'text-green-600';
      if (roi >= 0) return 'text-yellow-600';
      return 'text-red-600';
    };

    const getMarginClass = (margin) => {
      if (margin >= 30) return 'text-purple-700';
      if (margin >= 15) return 'text-purple-600';
      if (margin >= 5) return 'text-yellow-600';
      return 'text-gray-600';
    };

    onMounted(() => {
      fetchData();
    });

    return {
      loading,
      error,
      data,
      summary,
      topPerformers,
      bottomPerformers,
      parameters,
      cacheInfo,
      filters,
      fetchData,
      exportData,
      formatCurrency,
      formatPercent,
      getRoiClass,
      getMarginClass
    };
  }
};
</script>
