<template>
  <div class="min-h-screen" ref="dashboardContainer" @touchstart="handleTouchStart" @touchmove="handleTouchMove" @touchend="handleTouchEnd">
    <!-- Pull-to-refresh indicator -->
    <div 
      class="pull-indicator flex items-center justify-center gap-2 bg-moov-orange text-white px-4 py-2 rounded-full shadow-lg"
      :class="{ 'visible': isPulling }"
    >
      <svg 
        class="w-5 h-5 transition-transform" 
        :class="{ 'rotate-180': pullProgress >= 100 }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
      </svg>
      <span class="text-sm font-medium">{{ pullProgress >= 100 ? 'Relâchez pour actualiser' : 'Tirez pour actualiser' }}</span>
    </div>

    <!-- Navigation -->
    <Navbar />

    <!-- Main Content -->
    <div class="py-8">
      <header class="mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">Tableau de bord</h1>
              <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">Vue d'ensemble de vos points de vente</p>
            </div>
            <div class="text-xs sm:text-sm text-gray-500">
              {{ currentDate }}
            </div>
          </div>
        </div>
      </header>

      <main>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Loading Skeleton -->
          <div v-if="loadingStates.stats && stats.total === 0">
            <!-- Stats Cards Skeleton -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
              <div v-for="i in 4" :key="i" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl animate-pulse">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="h-4 bg-gray-300 rounded w-24 mb-3"></div>
                    <div class="h-8 bg-gray-300 rounded w-16"></div>
                  </div>
                  <div class="w-12 h-12 bg-gray-300 rounded-xl"></div>
                </div>
              </div>
            </div>

            <!-- Time Stats Skeleton -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
              <div v-for="i in 3" :key="i" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl animate-pulse">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 bg-gray-300 rounded-xl"></div>
                  <div class="flex-1">
                    <div class="h-3 bg-gray-300 rounded w-32 mb-2"></div>
                    <div class="h-6 bg-gray-300 rounded w-16"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actual Content -->
          <div v-if="stats.total > 0 || !loadingStates.stats" class="space-y-8">
          <!-- Incomplete required fields alert -->
          <div v-if="incompletePdvs.length">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 p-[2px] shadow-2xl">
              <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl rounded-3xl bg-white p-6 sm:p-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-start gap-4">
                    <div class="relative">
                      <div class="absolute inset-0 animate-pulse rounded-2xl bg-amber-400 opacity-30 blur-lg"></div>
                      <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 shadow-lg">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl sm:text-2xl font-black text-gray-900">
                          Données incomplètes
                        </h3>
                        <span class="inline-flex items-center rounded-full bg-gradient-to-r from-amber-100 to-orange-100 px-3 py-1 text-sm font-bold text-amber-900 ring-2 ring-amber-400/30">
                          {{ incompletePdvs.length }} PDV
                        </span>
                      </div>
                      <p class="text-sm text-gray-600 leading-relaxed max-w-2xl">
                        Ces points de vente ont des champs obligatoires manquants. Complétez-les rapidement pour garantir la qualité de vos données.
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 rounded-full bg-gradient-to-r from-red-50 to-orange-50 px-4 py-2 ring-2 ring-red-200/50 shadow-sm">
                    <div class="h-2.5 w-2.5 animate-pulse rounded-full bg-red-500"></div>
                    <span class="text-sm font-bold text-red-700">Priorité haute</span>
                  </div>
                </div>

                <!-- Expandable List Toggle -->
                <button
                  @click="showIncompleteDetails = !showIncompleteDetails"
                  class="mb-4 w-full flex items-center justify-between rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-4 transition-all duration-300 hover:shadow-md group"
                >
                  <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="font-semibold text-gray-700">
                      {{ showIncompleteDetails ? 'Masquer' : 'Afficher' }} la liste détaillée
                    </span>
                  </div>
                  <svg 
                    class="h-5 w-5 text-amber-600 transition-transform duration-300 group-hover:scale-110" 
                    :class="{ 'rotate-180': showIncompleteDetails }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </button>

                <!-- Expandable Details -->
                <transition
                  enter-active-class="transition-all duration-300 ease-out"
                  enter-from-class="max-h-0 opacity-0"
                  enter-to-class="max-h-[800px] opacity-100"
                  leave-active-class="transition-all duration-300 ease-in"
                  leave-from-class="max-h-[800px] opacity-100"
                  leave-to-class="max-h-0 opacity-0"
                >
                  <div v-show="showIncompleteDetails" class="overflow-hidden">
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                      <div
                        v-for="pdv in incompletePdvs"
                        :key="pdv.id"
                        class="group rounded-2xl bg-gradient-to-br from-white to-gray-50 p-4 shadow-sm ring-1 ring-gray-200/50 transition-all duration-200 hover:shadow-lg hover:ring-amber-300/50 hover:scale-[1.01]"
                      >
                        <div class="flex flex-col gap-3">
                          <!-- PDV Header -->
                          <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                              <div class="flex items-center gap-2 mb-1">
                                <h4 class="text-base font-bold text-gray-900 truncate">
                                  {{ pdv.nom_point || 'N/A' }}
                                </h4>
                                <span class="flex-shrink-0 rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                  {{ pdv.region || 'N/A' }}
                                </span>
                                <span v-if="pdv.organization?.name" class="flex-shrink-0 rounded-md bg-purple-100 px-2 py-0.5 text-xs font-semibold text-purple-700">
                                  {{ pdv.organization?.name }}
                                </span>
                              </div>
                              <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>{{ pdv.creator?.name || 'Inconnu' }}</span>
                              </div>
                            </div>
                            <router-link
                              :to="`/pdv/${pdv.id}/edit`"
                              class="flex-shrink-0 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 px-3 py-1.5 text-xs font-bold text-white shadow-md transition-all duration-200 hover:shadow-lg hover:scale-105"
                            >
                              Compléter
                            </router-link>
                          </div>

                          <!-- Missing Fields Pills -->
                          <div class="flex flex-wrap gap-1.5">
                            <span
                              v-for="(field, idx) in formatMissingFields(pdv)"
                              :key="idx"
                              class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-red-50 to-orange-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200/50"
                            >
                              <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                              </svg>
                              {{ field }}
                            </span>
                          </div>

                          <!-- Progress Bar -->
                          <div class="mt-1">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                              <span class="font-medium">Complétude</span>
                              <span class="font-semibold">{{ getCompletionPercentage(pdv) }}%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                              <div 
                                class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500"
                                :style="{ width: getCompletionPercentage(pdv) + '%' }"
                              ></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatsCard 
              label="Total PDV"
              :value="stats.total || 0"
              :icon="HomeIcon"
              color="orange"
              :trend="stats.total_trend || null"
            />
            <StatsCard 
              label="En attente"
              :value="stats.pending || 0"
              :icon="ClockIcon"
              color="yellow"
              :trend="stats.pending_trend || null"
              :clickable="authStore.isAdmin"
              @click="authStore.isAdmin && $router.push('/validation')"
            />
            <StatsCard 
              label="Validés"
              :value="stats.validated || 0"
              :icon="CheckIcon"
              color="green"
              :trend="stats.validated_trend || null"
            />
            <StatsCard 
              label="Rejetés"
              :value="stats.rejected || 0"
              :icon="XIcon"
              color="red"
              :trend="stats.rejected_trend || null"
            />
          </div>

          <!-- Statistiques supplémentaires -->
          <div class="flex gap-3 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-3">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-blue-50/50 to-blue-100/30 border border-blue-200 flex-shrink-0 w-48 sm:w-auto">
              <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-blue-500 flex items-center justify-center shadow-lg">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-xs sm:text-sm text-gray-600 font-semibold">Validés aujourd'hui</p>
                  <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ stats.validated_today || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-purple-50/50 to-purple-100/30 border border-purple-200 flex-shrink-0 w-48 sm:w-auto">
              <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-purple-500 flex items-center justify-center shadow-lg">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-xs sm:text-sm text-gray-600 font-semibold">Ce mois-ci</p>
                  <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ stats.created_this_month || 0 }}</p>
                </div>
              </div>
            </div>

            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl bg-gradient-to-br from-green-50/50 to-green-100/30 border border-green-200 flex-shrink-0 w-48 sm:w-auto">
              <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-green-500 flex items-center justify-center shadow-lg">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-xs sm:text-sm text-gray-600 font-semibold">Cette semaine</p>
                  <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ stats.created_this_week || 0 }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Pending Validation Alert (Admin Only) -->
          <div v-if="authStore.isAdmin && stats.pending > 0">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl border-2 border-yellow-300 bg-yellow-50/50">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3 sm:gap-4">
                  <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
                      {{ stats.pending }} PDV en attente de validation
                    </h3>
                    <p class="text-xs sm:text-sm text-gray-600">
                      Des demandes de création de points de vente nécessitent votre attention
                    </p>
                  </div>
                </div>
                <router-link
                  to="/validation"
                  class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base"
                >
                  Traiter les demandes
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                  </svg>
                </router-link>
              </div>
            </div>
          </div>

          <!-- GPS Missing Alert -->
          <div v-if="gpsStats.without_gps > 0" class="mb-8">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl border-2 border-red-300 bg-red-50/50">
              <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6 sm:w-7 sm:h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        {{ gpsStats.without_gps }} PDV sans coordonnées GPS
                      </h3>
                      <p class="text-xs sm:text-sm text-gray-600">
                        {{ gpsStats.percentage_without_gps }}% de vos PDV n'ont pas de position GPS valide et n'apparaîtront pas sur la carte
                      </p>
                    </div>
                  </div>
                  <button
                    @click="showGpsDetails = !showGpsDetails"
                    class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base"
                  >
                    {{ showGpsDetails ? 'Masquer' : 'Voir les détails' }}
                    <svg 
                      class="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-200" 
                      :class="{ 'rotate-180': showGpsDetails }"
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                </div>
                
                <!-- GPS Stats Summary -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                  <div class="p-3 rounded-xl bg-white/80">
                    <p class="text-xs text-gray-500 mb-1">Total PDV</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ gpsStats.total_pdv }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-green-50">
                    <p class="text-xs text-green-600 mb-1">Avec GPS</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-700">{{ gpsStats.with_gps }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-red-100">
                    <p class="text-xs text-red-600 mb-1">Sans GPS</p>
                    <p class="text-xl sm:text-2xl font-bold text-red-700">{{ gpsStats.without_gps }}</p>
                  </div>
                </div>
                
                <!-- Detailed list (expandable) -->
                <transition
                  enter-active-class="transition-all duration-300 ease-out"
                  enter-from-class="max-h-0 opacity-0"
                  enter-to-class="max-h-[500px] opacity-100"
                  leave-active-class="transition-all duration-300 ease-in"
                  leave-from-class="max-h-[500px] opacity-100"
                  leave-to-class="max-h-0 opacity-0"
                >
                  <div v-show="showGpsDetails" class="overflow-hidden">
                    <div class="mt-4 max-h-80 overflow-y-auto rounded-xl bg-white/80 border border-red-200">
                      <table class="min-w-full divide-y divide-red-100">
                        <thead class="bg-red-50 sticky top-0">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-red-700">Nom</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-red-700 hidden sm:table-cell">N° Flooz</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-red-700">Région</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-red-700 hidden md:table-cell">Créé par</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-red-700">Action</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-red-50">
                          <tr 
                            v-for="pdv in gpsStats.pdvs_without_gps.slice(0, 20)" 
                            :key="pdv.id"
                            class="hover:bg-red-25 transition-colors"
                          >
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ pdv.nom_point }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 hidden sm:table-cell">{{ pdv.numero_flooz }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ pdv.region }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 hidden md:table-cell">{{ pdv.creator?.name || 'N/A' }}</td>
                            <td class="px-4 py-2 text-center">
                              <router-link 
                                :to="`/pdv/${pdv.id}/edit`"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-white bg-red-500 hover:bg-red-600 transition-colors"
                              >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span class="hidden sm:inline">Modifier</span>
                              </router-link>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-if="gpsStats.pdvs_without_gps.length > 20" class="px-4 py-3 bg-red-50 text-center">
                        <p class="text-xs text-red-600">
                          Et {{ gpsStats.pdvs_without_gps.length - 20 }} autres PDV sans GPS...
                          <router-link to="/pdv/list?gps=missing" class="font-bold hover:underline">Voir tout</router-link>
                        </p>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Geographic Inconsistency Alert (Admin only) -->
          <div v-if="authStore.isAdmin && geoAlerts.alerts_count > 0">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl border-2 border-purple-300 bg-purple-50/50">
              <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6 sm:w-7 sm:h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        {{ geoAlerts.alerts_count }} incohérence{{ geoAlerts.alerts_count > 1 ? 's' : '' }} géographique{{ geoAlerts.alerts_count > 1 ? 's' : '' }}
                      </h3>
                      <p class="text-xs sm:text-sm text-gray-600">
                        Des PDV ont des coordonnées GPS qui ne correspondent pas à leur région déclarée
                      </p>
                    </div>
                  </div>
                  <button
                    @click="showGeoAlerts = !showGeoAlerts"
                    class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-purple-500 to-purple-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base"
                  >
                    {{ showGeoAlerts ? 'Masquer' : 'Voir les détails' }}
                    <svg 
                      class="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-200" 
                      :class="{ 'rotate-180': showGeoAlerts }"
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                </div>
                
                <!-- Geo Stats Summary -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                  <div class="p-3 rounded-xl bg-white/80">
                    <p class="text-xs text-gray-500 mb-1">PDV vérifiés</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ geoAlerts.total_checked }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-purple-100">
                    <p class="text-xs text-purple-600 mb-1">Incohérences</p>
                    <p class="text-xl sm:text-2xl font-bold text-purple-700">{{ geoAlerts.alerts_count }}</p>
                  </div>
                </div>
                
                <!-- Detailed list (expandable) -->
                <transition
                  enter-active-class="transition-all duration-300 ease-out"
                  enter-from-class="max-h-0 opacity-0"
                  enter-to-class="max-h-[500px] opacity-100"
                  leave-active-class="transition-all duration-300 ease-in"
                  leave-from-class="max-h-[500px] opacity-100"
                  leave-to-class="max-h-0 opacity-0"
                >
                  <div v-show="showGeoAlerts" class="overflow-hidden">
                    <div class="mt-4 max-h-80 overflow-y-auto rounded-xl bg-white/80 border border-purple-200">
                      <table class="min-w-full divide-y divide-purple-100">
                        <thead class="bg-purple-50 sticky top-0">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-700">Nom</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-700 hidden sm:table-cell">N° Flooz</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-700">Déclarée</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-purple-700">GPS</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-purple-700">Action</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50">
                          <tr 
                            v-for="alert in geoAlerts.alerts.slice(0, 20)" 
                            :key="alert.id"
                            class="hover:bg-purple-25 transition-colors"
                          >
                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ alert.nom_point }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600 hidden sm:table-cell">{{ alert.numero_flooz }}</td>
                            <td class="px-4 py-2">
                              <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">{{ alert.declared_region }}</span>
                            </td>
                            <td class="px-4 py-2">
                              <span class="px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700">{{ alert.actual_region_name || alert.actual_region || 'Hors Togo' }}</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                              <router-link 
                                :to="`/pdv/${alert.id}`"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-white bg-purple-500 hover:bg-purple-600 transition-colors"
                              >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span class="hidden sm:inline">Voir</span>
                              </router-link>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                      <div v-if="geoAlerts.alerts.length > 20" class="px-4 py-3 bg-purple-50 text-center">
                        <p class="text-xs text-purple-600">
                          Et {{ geoAlerts.alerts.length - 20 }} autres incohérences...
                        </p>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Proximity Alerts -->
          <div v-if="proximityAlerts.count > 0" class="mb-8">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl border-2 border-orange-300 bg-orange-50/50">
              <div class="flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="flex items-center gap-3 sm:gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0">
                      <svg class="w-6 h-6 sm:w-7 sm:h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">
                        {{ proximityAlerts.count }} alerte{{ proximityAlerts.count > 1 ? 's' : '' }} de proximité
                      </h3>
                      <p class="text-xs sm:text-sm text-gray-600">
                        Points de vente situés à moins de {{ proximityAlerts.threshold }}m les uns des autres
                      </p>
                    </div>
                  </div>
                  <button
                    @click="showProximityDetails = !showProximityDetails"
                    class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base"
                  >
                    {{ showProximityDetails ? 'Masquer' : 'Voir les détails' }}
                    <svg 
                      class="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-200" 
                      :class="{ 'rotate-180': showProximityDetails }"
                      fill="none" 
                      stroke="currentColor" 
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                </div>
                
                <!-- Proximity Stats Summary -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                  <div class="p-3 rounded-xl bg-white/80">
                    <p class="text-xs text-gray-500 mb-1">Groupes détectés</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ proximityAlerts.count }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-white/80">
                    <p class="text-xs text-gray-500 mb-1">PDV affectés</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-900">{{ proximityAlerts.total_pdv_affected || 0 }}</p>
                  </div>
                  <div class="p-3 rounded-xl bg-white/80 col-span-2 sm:col-span-1">
                    <p class="text-xs text-gray-500 mb-1">Seuil configuré</p>
                    <p class="text-xl sm:text-2xl font-bold text-orange-600">{{ proximityAlerts.threshold }}m</p>
                  </div>
                </div>
                
                <!-- Detailed list (expandable) -->
                <transition
                  enter-active-class="transition-all duration-300 ease-out"
                  enter-from-class="max-h-0 opacity-0"
                  enter-to-class="max-h-[600px] opacity-100"
                  leave-active-class="transition-all duration-300 ease-in"
                  leave-from-class="max-h-[600px] opacity-100"
                  leave-to-class="max-h-0 opacity-0"
                >
                  <div v-show="showProximityDetails" class="overflow-hidden">
                    <div class="mt-4 max-h-96 overflow-y-auto rounded-xl bg-white/80 border border-orange-200">
                      <div class="divide-y divide-orange-100">
                        <!-- Each cluster -->
                        <div 
                          v-for="(cluster, clusterIndex) in proximityAlerts.clusters.slice(0, 20)" 
                          :key="clusterIndex"
                          class="p-4 hover:bg-orange-25 transition-colors"
                        >
                          <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                              <span class="px-3 py-1 rounded-lg bg-orange-500 text-white text-xs font-bold">
                                Groupe {{ clusterIndex + 1 }}
                              </span>
                              <span class="text-xs text-gray-600">
                                {{ cluster.count }} PDV • Distance min: <span class="font-bold text-orange-600">{{ cluster.min_distance }}m</span>
                              </span>
                            </div>
                          </div>
                          
                          <!-- PDVs in cluster -->
                          <div class="space-y-3 ml-2 border-l-2 border-orange-200 pl-4">
                            <div 
                              v-for="(pdv, pdvIndex) in cluster.pdvs" 
                              :key="pdv.id"
                              class="flex items-start justify-between gap-3"
                            >
                              <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                  <span v-if="pdv.is_own" class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">Vôtre</span>
                                  <span v-else class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ pdv.organization_name }}</span>
                                  <p class="text-sm font-bold text-gray-900">{{ pdv.nom_point }}</p>
                                </div>
                                <p class="text-xs text-gray-600">{{ pdv.numero_flooz }} • {{ pdv.ville }}, {{ pdv.quartier }}</p>
                                
                                <!-- Distances to other PDVs in the cluster -->
                                <div v-if="pdvIndex < cluster.pdvs.length - 1" class="mt-1 ml-2 text-xs text-gray-500">
                                  <span 
                                    v-for="dist in cluster.distances.filter(d => d.from_id === pdv.id)" 
                                    :key="`${dist.from_id}-${dist.to_id}`"
                                    class="inline-flex items-center gap-1 mr-2"
                                  >
                                    <svg class="w-3 h-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                    <span class="font-semibold text-orange-600">{{ dist.distance }}m</span>
                                  </span>
                                </div>
                              </div>
                              
                              <!-- Action button -->
                              <router-link 
                                v-if="pdv.can_access"
                                :to="`/pdv/${pdv.id}`"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-medium text-white bg-orange-500 hover:bg-orange-600 transition-colors whitespace-nowrap flex-shrink-0"
                              >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Voir
                              </router-link>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div v-if="proximityAlerts.clusters.length > 20" class="px-4 py-3 bg-orange-50 text-center">
                        <p class="text-xs text-orange-600">
                          Et {{ proximityAlerts.clusters.length - 20 }} autres groupes...
                        </p>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="mb-8">
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 rounded-2xl">
              <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Actions rapides
              </h3>
              <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-3">
                <router-link
                  to="/pdv/create"
                  class="group flex items-center justify-center gap-2 sm:gap-3 px-4 sm:px-6 py-3 sm:py-4 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl hover:shadow-moov-orange/40 hover:scale-105 transition-all duration-200 text-sm sm:text-base"
                >
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                  Créer un PDV
                </router-link>
                <router-link
                  to="/pdv/list"
                  class="group flex items-center justify-center gap-2 sm:gap-3 px-4 sm:px-6 py-3 sm:py-4 rounded-xl bg-white/80 backdrop-blur-sm border-2 border-gray-200 text-gray-700 font-bold hover:bg-white hover:border-moov-orange hover:shadow-lg hover:scale-105 transition-all duration-200 text-sm sm:text-base"
                >
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                  </svg>
                  Voir tous les PDV
                </router-link>
                <router-link
                  to="/map"
                  class="group flex items-center justify-center gap-2 sm:gap-3 px-4 sm:px-6 py-3 sm:py-4 rounded-xl bg-white/80 backdrop-blur-sm border-2 border-gray-200 text-gray-700 font-bold hover:bg-white hover:border-moov-orange hover:shadow-lg hover:scale-105 transition-all duration-200 text-sm sm:text-base"
                >
                  <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                  </svg>
                  Voir la carte
                </router-link>
              </div>
            </div>
          </div>

          <!-- Distribution par Région et Dealer -->
          <div :class="authStore.isAdmin ? 'grid grid-cols-1 lg:grid-cols-2 gap-8' : 'max-w-2xl mx-auto'">
            <!-- PDV par Région -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
              <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Distribution par Région
              </h3>
              
              <div class="space-y-3">
                <div
                  v-for="(region, index) in byRegion"
                  :key="region.name"
                  class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-white/50 to-white/30 border border-gray-200 hover:border-moov-orange hover:shadow-lg transition-all duration-300 cursor-pointer"
                  @click="filterByRegion(region.name)"
                >
                  <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                      <div class="flex items-center gap-3">
                        <div 
                          class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                          :style="{ backgroundColor: getRegionColor(index) }"
                        >
                          {{ (region.region || '').substring(0, 2).toUpperCase() }}
                        </div>
                        <div>
                          <h4 class="font-bold text-gray-900">{{ region.region }}</h4>
                          <p class="text-xs text-gray-500">Région</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900">{{ region.total || region.count }}</p>
                        <p class="text-xs text-gray-500">PDV</p>
                      </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="absolute top-0 left-0 h-full rounded-full transition-all duration-500"
                        :style="{ 
                          width: `${getPercentage(region.total || region.count)}%`,
                          backgroundColor: getRegionColor(index)
                        }"
                      ></div>
                    </div>
                    
                    <!-- Stats breakdown -->
                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-200">
                      <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-green-500"></div>
                        <span class="text-xs text-gray-600">{{ region.validated || 0 }} validés</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                        <span class="text-xs text-gray-600">{{ region.pending || 0 }} en attente</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                        <span class="text-xs text-gray-600">{{ region.rejected || 0 }} rejetés</span>
                      </div>
                    </div>

                    <!-- Accordion pour les dealers -->
                    <div v-if="region.dealers && region.dealers.length > 0" class="mt-3 pt-3 border-t border-gray-200">
                      <button
                        @click.stop="toggleRegionDealers(region.region)"
                        class="w-full flex items-center justify-between text-xs font-semibold text-gray-600 hover:text-moov-orange transition-colors"
                      >
                        <span>Dealers ({{ region.dealers.length }})</span>
                        <svg 
                          class="w-4 h-4 transition-transform duration-200"
                          :class="{ 'rotate-180': expandedRegions[region.region] }"
                          fill="none" 
                          stroke="currentColor" 
                          viewBox="0 0 24 24"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                      </button>
                      
                      <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-96 opacity-100"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="max-h-96 opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                      >
                        <div v-show="expandedRegions[region.region]" class="overflow-hidden">
                          <div class="flex flex-wrap gap-2 mt-2">
                            <div 
                              v-for="dealer in region.dealers" 
                              :key="dealer.id"
                              class="flex items-center gap-1.5 px-2 py-1 rounded-lg bg-white border border-gray-200 hover:border-moov-orange transition-colors cursor-pointer"
                              @click.stop="$router.push(`/dealers/${dealer.id}`)"
                            >
                              <div class="w-5 h-5 rounded bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center">
                                <span class="text-white text-[10px] font-bold">{{ dealer.code?.substring(0, 2) || 'XX' }}</span>
                              </div>
                              <span class="text-xs font-medium text-gray-700 truncate max-w-[80px]">{{ dealer.name }}</span>
                              <span class="text-xs font-bold text-moov-orange">{{ dealer.count }}</span>
                            </div>
                          </div>
                        </div>
                      </transition>
                    </div>
                  </div>
                  
                  <!-- Hover effect -->
                  <div class="absolute inset-0 bg-gradient-to-r from-moov-orange/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                </div>
              </div>
            </div>

            <!-- PDV par Dealer (Admin only) -->
            <div v-if="authStore.isAdmin" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                  <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                  Top Dealers
                </h3>
                <router-link
                  to="/dealers"
                  class="text-sm font-semibold text-moov-orange hover:text-moov-orange-dark transition-colors flex items-center gap-1"
                >
                  Voir tout
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </router-link>
              </div>

              <!-- Skeleton pour Top Dealers -->
              <div v-if="loadingStates.topDealers && topDealers.length === 0" class="space-y-3">
                <div v-for="i in 5" :key="i" class="animate-pulse rounded-xl bg-gray-100 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-xl bg-gray-300"></div>
                      <div class="space-y-2">
                        <div class="h-4 bg-gray-300 rounded w-32"></div>
                        <div class="h-3 bg-gray-200 rounded w-20"></div>
                      </div>
                    </div>
                    <div class="space-y-2">
                      <div class="h-3 bg-gray-200 rounded w-16"></div>
                      <div class="h-4 bg-gray-300 rounded w-24"></div>
                    </div>
                  </div>
                  <div class="grid grid-cols-3 gap-2">
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                    <div class="h-16 bg-gray-200 rounded-lg"></div>
                  </div>
                </div>
              </div>

              <div v-else class="space-y-3">
                <div
                  v-for="(dealer, index) in topDealers.slice(0, 5)"
                  :key="dealer.id"
                  class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-white/50 to-white/30 border border-gray-200 hover:border-moov-orange hover:shadow-lg transition-all duration-300 cursor-pointer"
                  @click="$router.push(`/dealers/${dealer.id}`)"
                >
                  <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                      <div class="flex items-center gap-3">
                        <div class="relative">
                          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold">{{ dealer.code?.substring(0, 2) || 'XX' }}</span>
                          </div>
                          <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-white border-2 border-moov-orange flex items-center justify-center">
                            <span class="text-xs font-bold text-moov-orange">{{ index + 1 }}</span>
                          </div>
                        </div>
                        <div>
                          <h4 class="font-bold text-gray-900">{{ dealer.name }}</h4>
                          <p class="text-xs text-gray-500">{{ dealer.code }}</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <p class="text-sm font-semibold text-gray-600">CA annuel</p>
                        <p class="text-lg font-bold text-moov-orange">{{ formatCurrency(dealer.revenue || 0) }}</p>
                      </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                      <div class="text-center p-2 rounded-lg bg-orange-50">
                        <p class="text-lg font-bold text-orange-700">{{ formatCurrency(dealer.revenue_yesterday || 0) }}</p>
                        <p class="text-xs text-orange-700">Chiffre d'affaires J-1</p>
                      </div>
                      <div class="text-center p-2 rounded-lg bg-purple-50">
                        <p class="text-lg font-bold text-purple-700">{{ formatCurrency(dealer.dealer_commissions || 0) }}</p>
                        <p class="text-xs text-purple-700">Commissions dealer</p>
                      </div>
                      <div class="text-center p-2 rounded-lg bg-blue-50">
                        <p class="text-lg font-bold text-blue-700">{{ formatNumber(dealer.total_pdv || 0) }}</p>
                        <p class="text-xs text-blue-700">Total PDV</p>
                      </div>
                    </div>
                  </div>

                  <!-- Hover arrow -->
                  <div class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 group-hover:translate-x-2 transition-all duration-300">
                    <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent PDVs -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl mb-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              PDV récents
            </h3>
            <div v-if="recentPdvs.length > 0" class="overflow-x-auto">
              <table class="min-w-full">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Nom
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Dealer
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Région
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="pdv in recentPdvs" :key="pdv.id" class="hover:bg-white/50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ pdv.nom_point }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ pdv.organization?.name || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ pdv.region }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="{
                        'px-3 py-1 inline-flex text-xs font-semibold rounded-full': true,
                        'bg-yellow-100 text-yellow-800': pdv.status === 'pending',
                        'bg-green-100 text-green-800': pdv.status === 'validated',
                        'bg-red-100 text-red-800': pdv.status === 'rejected',
                      }">
                        {{ pdv.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
              <p class="mt-4 text-gray-500 font-medium">Aucun PDV récent</p>
            </div>
          </div>

          <!-- Dealers Stats (Admin only) -->
          <div v-if="authStore.isAdmin && byOrganization.length > 0" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Statistiques détaillées
              </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-blue-700 font-semibold">Dealers actifs</p>
                    <p class="text-2xl font-bold text-blue-900">{{ byOrganization.length }}</p>
                  </div>
                </div>
              </div>

              <div class="p-4 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100/50 border border-purple-200">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-purple-700 font-semibold">Régions couvertes</p>
                    <p class="text-2xl font-bold text-purple-900">{{ byRegion.length }}</p>
                  </div>
                </div>
              </div>

              <div class="p-4 rounded-xl bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-green-700 font-semibold">Taux de validation</p>
                    <p class="text-2xl font-bold text-green-900">{{ validationRate }}%</p>
                  </div>
                </div>
              </div>

              <div class="p-4 rounded-xl bg-gradient-to-br from-orange-50 to-orange-100/50 border border-orange-200">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-lg bg-moov-orange flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm text-orange-700 font-semibold">PDV moyen/Dealer</p>
                    <p class="text-2xl font-bold text-orange-900">{{ averagePdvPerDealer }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import StatisticsService from '../services/StatisticsService';
import PointOfSaleService from '../services/PointOfSaleService';
import Navbar from '../components/Navbar.vue';
import StatsCard from '../components/StatsCard.vue';

const authStore = useAuthStore();
const router = useRouter();

const stats = ref({
  total: 0,
  pending: 0,
  validated: 0,
  rejected: 0,
});

const recentPdvs = ref([]);
const byOrganization = ref([]);
const byRegion = ref([]);
const topDealers = ref([]);
const loadingStates = ref({
  stats: false,
  recentPdvs: false,
  byOrganization: false,
  topDealers: false,
  byRegion: false,
  incompletePdvs: false,
  gpsStats: false,
  geoAlerts: false,
  proximityAlerts: false
});
const expandedRegions = ref({});
const gpsStats = ref({
  total_pdv: 0,
  without_gps: 0,
  with_gps: 0,
  percentage_without_gps: 0,
  pdvs_without_gps: []
});
const showGpsDetails = ref(false);

// Geo alerts (incohérence région/GPS)
const geoAlerts = ref({
  total_checked: 0,
  alerts_count: 0,
  alerts: []
});
const showGeoAlerts = ref(false);

// Proximity alerts
const proximityAlerts = ref({
  clusters: [],
  count: 0,
  threshold: 300,
  total_pdv_affected: 0
});
const showProximityDetails = ref(false);

const currentDate = computed(() => {
  return new Date().toLocaleDateString('fr-FR', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
});

const validationRate = computed(() => {
  if (stats.value.total === 0) return 0;
  return Math.round((stats.value.validated / stats.value.total) * 100);
});

const averagePdvPerDealer = computed(() => {
  if (byOrganization.value.length === 0) return 0;
  const total = byOrganization.value.reduce((sum, org) => sum + (org.point_of_sales_count || 0), 0);
  return Math.round(total / byOrganization.value.length);
});

const getPercentage = (count) => {
  if (stats.value.total === 0) return 0;
  return Math.round((count / stats.value.total) * 100);
};

const getRegionColor = (index) => {
  const colors = [
    '#FF6B00', // Moov Orange
    '#E55A00', // Moov Orange Dark
    '#3B82F6', // Blue
    '#10B981', // Green
    '#8B5CF6', // Purple
    '#F59E0B', // Amber
  ];
  return colors[index % colors.length];
};

const filterByRegion = (regionName) => {
  // Navigate to PDV list with region filter
  router.push({ 
    path: '/pdv/list', 
    query: { region: regionName }
  });
};

const toggleRegionDealers = (regionName) => {
  expandedRegions.value[regionName] = !expandedRegions.value[regionName];
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

const formatMissingFields = (pdv) => {
  return (pdv?.missing_required_fields || []).map((label) => {
    // Capitaliser la première lettre pour rester cohérent avec les autres cartes
    if (typeof label === 'string' && label.length > 0) {
      return label.charAt(0).toUpperCase() + label.slice(1);
    }
    return label;
  });
};

const getCompletionPercentage = (pdv) => {
  const totalRequiredFields = 14; // Nombre total de champs obligatoires
  const missingCount = (pdv?.missing_required_fields || []).length;
  const completedCount = totalRequiredFields - missingCount;
  return Math.round((completedCount / totalRequiredFields) * 100);
};

// Charger les données individuellement
const fetchStats = async () => {
  try {
    loadingStates.value.stats = true;
    const data = await StatisticsService.getDashboard();
    stats.value = data.stats;
    byOrganization.value = data.by_organization || [];
    byRegion.value = data.by_region || [
      { name: 'Maritime', count: 45, validated: 35, pending: 8, rejected: 2, cities: ['Lomé', 'Aného', 'Tsévié'] },
      { name: 'Plateaux', count: 32, validated: 28, pending: 3, rejected: 1, cities: ['Atakpamé', 'Kpalimé'] },
      { name: 'Centrale', count: 28, validated: 22, pending: 5, rejected: 1, cities: ['Sokodé', 'Tchamba'] },
      { name: 'Kara', count: 25, validated: 20, pending: 4, rejected: 1, cities: ['Kara', 'Bassar'] },
      { name: 'Savanes', count: 18, validated: 15, pending: 2, rejected: 1, cities: ['Dapaong', 'Mango'] },
    ];
    incompletePdvs.value = data.incomplete_pdvs || [];
  } catch (error) {
    console.error('Failed to fetch stats:', error);
  } finally {
    loadingStates.value.stats = false;
    loadingStates.value.byOrganization = false;
    loadingStates.value.byRegion = false;
    loadingStates.value.incompletePdvs = false;
  }
};

const fetchRecentPdvs = async () => {
  try {
    loadingStates.value.recentPdvs = true;
    const data = await StatisticsService.getDashboard();
    recentPdvs.value = data.recent_pdvs || [];
  } catch (error) {
    console.error('Failed to fetch recent PDVs:', error);
  } finally {
    loadingStates.value.recentPdvs = false;
  }
};

const fetchTopDealers = async () => {
  try {
    loadingStates.value.topDealers = true;
    const data = await StatisticsService.getDashboard();
    topDealers.value = data.top_dealers || data.topDealers || [];
  } catch (error) {
    console.error('Failed to fetch top dealers:', error);
  } finally {
    loadingStates.value.topDealers = false;
  }
};

const fetchGpsStats = async () => {
  try {
    loadingStates.value.gpsStats = true;
    const gpsData = await PointOfSaleService.getGpsStats();
    gpsStats.value = gpsData;
  } catch (error) {
    console.error('Failed to fetch GPS stats:', error);
  } finally {
    loadingStates.value.gpsStats = false;
  }
};

const fetchGeoAlerts = async () => {
  if (!authStore.isAdmin) return;
  try {
    loadingStates.value.geoAlerts = true;
    const alertsData = await StatisticsService.getGeoAlerts();
    geoAlerts.value = alertsData;
  } catch (error) {
    console.error('Failed to fetch geo alerts:', error);
  } finally {
    loadingStates.value.geoAlerts = false;
  }
};

const fetchProximityAlerts = async () => {
  try {
    loadingStates.value.proximityAlerts = true;
    const proximityData = await PointOfSaleService.getProximityAlerts();
    proximityAlerts.value = proximityData;
  } catch (error) {
    console.error('Failed to fetch proximity alerts:', error);
  } finally {
    loadingStates.value.proximityAlerts = false;
  }
};

const fetchDashboardData = async () => {
  // Charger tous les widgets en parallèle
  await Promise.all([
    fetchStats(),
    fetchRecentPdvs(),
    fetchTopDealers(),
    fetchGpsStats(),
    fetchGeoAlerts(),
    fetchProximityAlerts()
  ]);
};

// Pull-to-refresh functionality
const dashboardContainer = ref(null);
const isPulling = ref(false);
const pullProgress = ref(0);
const touchStartY = ref(0);
const isRefreshing = ref(false);
const incompletePdvs = ref([]);
const showIncompleteDetails = ref(false);

const handleTouchStart = (e) => {
  if (window.scrollY === 0 && !isRefreshing.value) {
    touchStartY.value = e.touches[0].clientY;
  }
};

const handleTouchMove = (e) => {
  if (touchStartY.value === 0 || isRefreshing.value) return;
  
  const currentY = e.touches[0].clientY;
  const diff = currentY - touchStartY.value;
  
  if (diff > 0 && window.scrollY === 0) {
    isPulling.value = true;
    pullProgress.value = Math.min((diff / 100) * 100, 100);
  }
};

const handleTouchEnd = async () => {
  if (pullProgress.value >= 100 && !isRefreshing.value) {
    isRefreshing.value = true;
    
    // Haptic feedback
    if ('vibrate' in navigator) {
      navigator.vibrate(50);
    }
    
    await fetchDashboardData();
    isRefreshing.value = false;
  }
  
  isPulling.value = false;
  pullProgress.value = 0;
  touchStartY.value = 0;
};

onMounted(() => {
  fetchDashboardData();
});

// Icon components for StatsCard using h() render function
import { h } from 'vue';

const HomeIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
    ]);
  }
};

const ClockIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const CheckIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 13l4 4L19 7' })
    ]);
  }
};

const XIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M6 18L18 6M6 6l12 12' })
    ]);
  }
};
</script>
