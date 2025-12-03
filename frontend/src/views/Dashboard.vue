<template>
  <div class="min-h-screen">
    <!-- Navigation -->
    <Navbar />

    <!-- Main Content -->
    <div class="py-8">
      <header class="mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-4xl font-bold text-gray-900">Tableau de bord</h1>
              <p class="mt-2 text-gray-600">Vue d'ensemble de vos points de vente</p>
            </div>
            <div class="text-sm text-gray-500">
              {{ currentDate }}
            </div>
          </div>
        </div>
      </header>

      <main>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <!-- Stats Cards -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <StatsCard 
              label="Total PDV"
              :value="stats.total || 0"
              :icon="HomeIcon"
              color="orange"
              :trend="5.2"
            />
            <StatsCard 
              label="En attente"
              :value="stats.pending || 0"
              :icon="ClockIcon"
              color="yellow"
              :trend="-2.1"
              :clickable="authStore.isAdmin"
              @click="authStore.isAdmin && $router.push('/validation')"
            />
            <StatsCard 
              label="Validés"
              :value="stats.validated || 0"
              :icon="CheckIcon"
              color="green"
              :trend="8.3"
            />
            <StatsCard 
              label="Rejetés"
              :value="stats.rejected || 0"
              :icon="XIcon"
              color="red"
              :trend="1.5"
            />
          </div>

          <!-- Pending Validation Alert (Admin Only) -->
          <div v-if="authStore.isAdmin && stats.pending > 0" class="mb-8">
            <div class="glass-card p-6 rounded-2xl border-2 border-yellow-300 bg-yellow-50/50">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-14 h-14 rounded-xl bg-yellow-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                      {{ stats.pending }} PDV en attente de validation
                    </h3>
                    <p class="text-sm text-gray-600">
                      Des demandes de création de points de vente nécessitent votre attention
                    </p>
                  </div>
                </div>
                <router-link
                  to="/validation"
                  class="px-6 py-3 rounded-xl bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center gap-2"
                >
                  Traiter les demandes
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                  </svg>
                </router-link>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="mb-8">
            <div class="glass-card p-6 rounded-2xl">
              <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Actions rapides
              </h3>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <router-link
                  to="/pdv/create"
                  class="group flex items-center justify-center gap-3 px-6 py-4 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl hover:shadow-moov-orange/40 hover:scale-105 transition-all duration-200"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                  Créer un PDV
                </router-link>
                <router-link
                  to="/pdv/list"
                  class="group flex items-center justify-center gap-3 px-6 py-4 rounded-xl bg-white/80 backdrop-blur-sm border-2 border-gray-200 text-gray-700 font-bold hover:bg-white hover:border-moov-orange hover:shadow-lg hover:scale-105 transition-all duration-200"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                  </svg>
                  Voir tous les PDV
                </router-link>
                <router-link
                  to="/map"
                  class="group flex items-center justify-center gap-3 px-6 py-4 rounded-xl bg-white/80 backdrop-blur-sm border-2 border-gray-200 text-gray-700 font-bold hover:bg-white hover:border-moov-orange hover:shadow-lg hover:scale-105 transition-all duration-200"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                  </svg>
                  Voir la carte
                </router-link>
              </div>
            </div>
          </div>

          <!-- Distribution par Région et Dealer -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- PDV par Région -->
            <div class="glass-card p-6 rounded-2xl">
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
                          {{ region.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div>
                          <h4 class="font-bold text-gray-900">{{ region.name }}</h4>
                          <p class="text-xs text-gray-500">{{ region.cities?.length || 0 }} villes</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900">{{ region.count }}</p>
                        <p class="text-xs text-gray-500">PDV</p>
                      </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="absolute top-0 left-0 h-full rounded-full transition-all duration-500"
                        :style="{ 
                          width: `${getPercentage(region.count)}%`,
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
                  </div>
                  
                  <!-- Hover effect -->
                  <div class="absolute inset-0 bg-gradient-to-r from-moov-orange/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                </div>
              </div>
            </div>

            <!-- PDV par Dealer (Admin only) -->
            <div v-if="authStore.isAdmin" class="glass-card p-6 rounded-2xl">
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

              <div class="space-y-3">
                <div
                  v-for="(org, index) in byOrganization.slice(0, 5)"
                  :key="org.id"
                  class="group relative overflow-hidden rounded-xl bg-gradient-to-r from-white/50 to-white/30 border border-gray-200 hover:border-moov-orange hover:shadow-lg transition-all duration-300 cursor-pointer"
                  @click="$router.push(`/dealers/${org.id}`)"
                >
                  <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                      <div class="flex items-center gap-3">
                        <div class="relative">
                          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold">{{ org.code?.substring(0, 2) || 'XX' }}</span>
                          </div>
                          <div class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-white border-2 border-moov-orange flex items-center justify-center">
                            <span class="text-xs font-bold text-moov-orange">{{ index + 1 }}</span>
                          </div>
                        </div>
                        <div>
                          <h4 class="font-bold text-gray-900">{{ org.name }}</h4>
                          <p class="text-xs text-gray-500">{{ org.code }}</p>
                        </div>
                      </div>
                      <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900">{{ org.point_of_sales_count || 0 }}</p>
                        <p class="text-xs text-gray-500">PDV</p>
                      </div>
                    </div>

                    <!-- Mini chart -->
                    <div class="grid grid-cols-3 gap-2">
                      <div class="text-center p-2 rounded-lg bg-green-50">
                        <p class="text-lg font-bold text-green-600">{{ org.validated_count || 0 }}</p>
                        <p class="text-xs text-green-700">Validés</p>
                      </div>
                      <div class="text-center p-2 rounded-lg bg-yellow-50">
                        <p class="text-lg font-bold text-yellow-600">{{ org.pending_count || 0 }}</p>
                        <p class="text-xs text-yellow-700">En attente</p>
                      </div>
                      <div class="text-center p-2 rounded-lg bg-gray-50">
                        <p class="text-lg font-bold text-gray-600">{{ org.rejected_count || 0 }}</p>
                        <p class="text-xs text-gray-700">Rejetés</p>
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
          <div class="glass-card p-6 rounded-2xl mb-8">
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
                      {{ pdv.dealer_name }}
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
          <div v-if="authStore.isAdmin && byOrganization.length > 0" class="glass-card p-6 rounded-2xl">
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
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import StatisticsService from '../services/StatisticsService';
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

const fetchDashboardData = async () => {
  try {
    const data = await StatisticsService.getDashboard();
    stats.value = data.stats;
    recentPdvs.value = data.recent_pdvs || [];
    byOrganization.value = data.by_organization || [];
    byRegion.value = data.by_region || [
      { name: 'Maritime', count: 45, validated: 35, pending: 8, rejected: 2, cities: ['Lomé', 'Aného', 'Tsévié'] },
      { name: 'Plateaux', count: 32, validated: 28, pending: 3, rejected: 1, cities: ['Atakpamé', 'Kpalimé'] },
      { name: 'Centrale', count: 28, validated: 22, pending: 5, rejected: 1, cities: ['Sokodé', 'Tchamba'] },
      { name: 'Kara', count: 25, validated: 20, pending: 4, rejected: 1, cities: ['Kara', 'Bassar'] },
      { name: 'Savanes', count: 18, validated: 15, pending: 2, rejected: 1, cities: ['Dapaong', 'Mango'] },
    ];
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error);
  }
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
