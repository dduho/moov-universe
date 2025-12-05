<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">Points de Vente</h1>
          <p class="text-sm sm:text-base text-gray-600">{{ total }} PDV au total - {{ filteredPOS.length }} affichés</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <ExportButton
            @export="handleExport"
            label="Exporter"
            class="flex-1 sm:flex-none"
          />
          <router-link
            v-if="authStore.isAdmin"
            to="/pdv/import"
            class="flex-1 sm:flex-none px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 transition-all flex items-center justify-center gap-2 text-sm sm:text-base"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <span class="hidden sm:inline">Importer</span>
          </router-link>
          <router-link
            to="/pdv/create"
            class="flex-1 sm:flex-none px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2 text-sm sm:text-base"
          >
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="hidden sm:inline">Créer un PDV</span>
          </router-link>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card p-4 sm:p-6 mb-6 sm:mb-8">
        <!-- Ligne 1: Recherche, Statut, Région, Dealer -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4 mb-3 sm:mb-4">
          <div class="sm:col-span-2 md:col-span-2">
            <FormInput
              v-model="filters.search"
              label="Recherche"
              type="text"
              placeholder="Nom, Flooz, Dealer..."
            />
          </div>
          
          <FormSelect
            v-model="filters.status"
            label="Statut"
            :options="[
              { label: 'Tous', value: '' },
              { label: 'En attente', value: 'pending' },
              { label: 'Validés', value: 'validated' },
              { label: 'Rejetés', value: 'rejected' }
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-model="filters.region"
            label="Région"
            :options="[
              { label: 'Toutes', value: '' },
              ...regions.map(r => ({ label: r, value: r }))
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-if="authStore.isAdmin"
            v-model="filters.dealer"
            label="Dealer"
            :options="[
              { label: 'Tous', value: '' },
              ...dealers.map(d => ({ label: d.name, value: d.id }))
            ]"
            option-label="label"
            option-value="value"
          />
        </div>

        <!-- Ligne 2: Filtres géographiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-3 sm:mb-4">
          <FormSelect
            v-model="filters.prefecture"
            label="Préfecture"
            :options="[
              { label: 'Toutes', value: '' },
              ...prefectures.map(p => ({ label: p, value: p }))
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-model="filters.commune"
            label="Commune"
            :options="[
              { label: 'Toutes', value: '' },
              ...communes.map(c => ({ label: c, value: c }))
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormInput
            v-model="filters.ville"
            label="Ville"
            type="text"
            placeholder="Rechercher une ville..."
          />
          
          <FormInput
            v-model="filters.quartier"
            label="Quartier"
            type="text"
            placeholder="Rechercher un quartier..."
          />
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-2 sm:gap-3">
            <button
              @click="viewMode = 'grid'"
              class="px-3 sm:px-4 py-2 rounded-xl font-bold transition-all duration-200"
              :class="viewMode === 'grid' ? 'bg-moov-orange text-white' : 'bg-white/50 text-gray-700 hover:bg-white'"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
              </svg>
            </button>
            <button
              @click="viewMode = 'list'"
              class="px-3 sm:px-4 py-2 rounded-xl font-bold transition-all duration-200"
              :class="viewMode === 'list' ? 'bg-moov-orange text-white' : 'bg-white/50 text-gray-700 hover:bg-white'"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
              </svg>
            </button>
          </div>

          <div class="flex items-center gap-2 sm:gap-3">
            <div>
              <button
                @click="clearFilters"
                class="px-3 sm:px-4 py-2 sm:py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 whitespace-nowrap text-sm sm:text-base"
              >
                Réinitialiser
              </button>
            </div>
            <FormSelect
              v-model="filters.sortBy"
              label="Trier par"
              :options="[
                { label: 'Date de création', value: 'created_at' },
                { label: 'Nom', value: 'point_name' },
                { label: 'Statut', value: 'status' },
                { label: 'Région', value: 'region' }
              ]"
              option-label="label"
              option-value="value"
              class="w-32 sm:w-auto"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card p-12 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-gray-600 font-semibold">Chargement des PDV...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPOS.length === 0" class="glass-card p-12 text-center">
        <div class="text-6xl mb-4">📍</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun PDV trouvé</h3>
        <p class="text-gray-600 mb-6">Aucun point de vente ne correspond à vos critères de recherche</p>
        <button
          @click="clearFilters"
          class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200"
        >
          Réinitialiser les filtres
        </button>
      </div>

      <!-- Grid View -->
      <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="pos in paginatedPOS"
          :key="pos.id"
          class="glass-card overflow-hidden hover:shadow-2xl transition-all duration-300 cursor-pointer group"
          @click="$router.push(`/pdv/${pos.id}`)"
        >
          <!-- Header with gradient -->
          <div class="bg-gradient-to-r from-moov-orange to-moov-orange-dark p-4">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-bold text-white mb-1 line-clamp-1">{{ pos.nom_point || pos.point_name }}</h3>
                <div class="flex items-center gap-2 text-white/90 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                  </svg>
                  <span class="font-semibold">{{ formatPhone(pos.numero_flooz || pos.flooz_number) }}</span>
                </div>
              </div>
              <span
                class="px-3 py-1 rounded-xl text-xs font-bold shadow-lg"
                :class="getStatusClass(pos.status)"
              >
                {{ getStatusLabel(pos.status) }}
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="p-5 space-y-3">
            <!-- Dealer -->
            <div class="flex items-start gap-3 pb-3 border-b border-gray-100">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-moov-orange/10 to-moov-orange-light/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Dealer</p>
                <p class="text-sm font-bold text-gray-900 truncate">{{ pos.dealer_name || pos.organization?.name || 'N/A' }}</p>
              </div>
            </div>

            <!-- Gérant -->
            <div class="flex items-start gap-3 pb-3 border-b border-gray-100">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Gérant</p>
                <p class="text-sm font-bold text-gray-900 truncate">{{ pos.firstname }} {{ pos.lastname }}</p>
                <p class="text-xs text-gray-600 truncate">{{ formatPhone(pos.numero_proprietaire) || 'N/A' }}</p>
              </div>
            </div>

            <!-- Localisation -->
            <div class="flex items-start gap-3 pb-3 border-b border-gray-100">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Localisation</p>
                <p class="text-sm font-bold text-gray-900 truncate">{{ pos.ville || pos.city }}, {{ pos.quartier || pos.neighborhood }}</p>
                <p class="text-xs text-gray-600">{{ pos.prefecture }} • {{ pos.region }}</p>
              </div>
            </div>

            <!-- Info supplémentaires -->
            <div class="grid grid-cols-2 gap-3">
              <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-purple-50">
                <svg class="w-4 h-4 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-gray-600">Profil</p>
                  <p class="text-xs font-bold text-purple-900 truncate">{{ pos.profil || 'N/A' }}</p>
                </div>
              </div>
              
              <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50">
                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-gray-600">Créé le</p>
                  <p class="text-xs font-bold text-amber-900 truncate">{{ formatDate(pos.created_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Alerte proximité -->
            <div v-if="pos.proximity_alert" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border-2 border-red-200 mt-3">
              <svg class="w-5 h-5 text-red-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              <span class="text-xs font-bold text-red-700">Alerte proximité détectée</span>
            </div>
          </div>

          <!-- Footer hover effect -->
          <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
            <span class="text-xs font-semibold text-gray-500">Cliquez pour voir les détails</span>
            <svg class="w-5 h-5 text-moov-orange transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="glass-card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50/80">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">PDV</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Dealer</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Gérant</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Localisation</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Profil</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white/50 divide-y divide-gray-200">
            <tr
              v-for="pos in paginatedPOS"
              :key="pos.id"
              class="hover:bg-white/80 transition-colors cursor-pointer"
              @click="$router.push(`/pdv/${pos.id}`)"
            >
              <td class="px-6 py-4">
                <div>
                  <div class="text-sm font-bold text-gray-900">{{ pos.nom_point || pos.point_name }}</div>
                  <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    {{ formatPhone(pos.numero_flooz || pos.flooz_number) }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-bold text-gray-900">{{ pos.dealer_name || pos.organization?.name || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900">{{ pos.firstname }} {{ pos.lastname }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ formatPhone(pos.numero_proprietaire) || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-semibold text-gray-900">{{ pos.ville || pos.city }}{{ pos.quartier ? ', ' + pos.quartier : '' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ pos.prefecture }} • {{ pos.region }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-700 font-medium">{{ pos.profil || 'N/A' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-3 py-1 rounded-xl text-xs font-bold"
                  :class="getStatusClass(pos.status)"
                >
                  {{ getStatusLabel(pos.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                {{ formatDate(pos.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click.stop="$router.push(`/pdv/${pos.id}`)"
                  class="text-moov-orange hover:text-moov-orange-dark font-bold flex items-center gap-1 ml-auto"
                >
                  Voir
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Load More Button -->
      <div v-if="!loading && currentPage < lastPage" class="mt-8 flex justify-center">
        <button
          @click="loadMore"
          :disabled="loadingMore"
          class="px-8 py-4 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-3"
        >
          <span v-if="!loadingMore">Charger plus de PDV</span>
          <span v-else class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Chargement...
          </span>
        </button>
      </div>

      <!-- Pagination Info -->
      <div v-if="!loading && filteredPOS.length > 0" class="mt-4 text-center">
        <div class="text-sm text-gray-600 font-semibold">
          Page {{ currentPage }} sur {{ lastPage }} - {{ filteredPOS.length }} PDV affichés sur {{ total }} au total
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import ExportButton from '../components/ExportButton.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import PointOfSaleService from '../services/PointOfSaleService';
import ExportService from '../services/ExportService';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import { formatPhone, formatShortcode } from '../utils/formatters';

const router = useRouter();
const authStore = useAuthStore();
const organizationStore = useOrganizationStore();

const loading = ref(true);
const loadingMore = ref(false);
const pointsOfSale = ref([]);
const viewMode = ref('grid');
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(12); // Pagination par multiple de 12

const filters = ref({
  search: '',
  status: '',
  region: '',
  prefecture: '',
  commune: '',
  ville: '',
  quartier: '',
  dealer: '',
  sortBy: 'created_at'
});

const regions = ref([
  'Savanes',
  'Kara',
  'Centrale',
  'Plateaux',
  'Maritime'
]);

const dealers = computed(() => organizationStore.organizations);

const prefectures = computed(() => {
  const uniquePrefectures = new Set();
  pointsOfSale.value.forEach(pos => {
    if (pos.prefecture) {
      uniquePrefectures.add(pos.prefecture);
    }
  });
  return Array.from(uniquePrefectures).sort();
});

const communes = computed(() => {
  const uniqueCommunes = new Set();
  pointsOfSale.value.forEach(pos => {
    if (pos.commune) {
      // Format commune name
      const formatted = pos.commune.replace(/_/g, ' ');
      uniqueCommunes.add(formatted);
    }
  });
  return Array.from(uniqueCommunes).sort();
});

// Les PDV sont déjà filtrés côté serveur
const filteredPOS = computed(() => {
  let filtered = pointsOfSale.value;

  // Filtres locaux supplémentaires (non gérés par le serveur)
  if (filters.value.commune) {
    filtered = filtered.filter(pos => {
      const formatted = pos.commune?.replace(/_/g, ' ');
      return formatted === filters.value.commune;
    });
  }

  if (filters.value.ville) {
    const villeLower = filters.value.ville.toLowerCase();
    filtered = filtered.filter(pos => pos.ville?.toLowerCase().includes(villeLower));
  }

  if (filters.value.quartier) {
    const quartierLower = filters.value.quartier.toLowerCase();
    filtered = filtered.filter(pos => pos.quartier?.toLowerCase().includes(quartierLower));
  }

  return filtered;
});

const totalPages = computed(() => lastPage.value);

const paginatedPOS = computed(() => {
  // Pas besoin de pagination côté client, déjà fait côté serveur
  return filteredPOS.value;
});

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 border border-yellow-300 text-yellow-800',
    validated: 'bg-green-100 border border-green-300 text-green-800',
    rejected: 'bg-red-100 border border-red-300 text-red-800'
  };
  return classes[status] || 'bg-gray-100 border border-gray-300 text-gray-800';
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    validated: 'Validé',
    rejected: 'Rejeté'
  };
  return labels[status] || status;
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const clearFilters = () => {
  filters.value = {
    search: '',
    status: '',
    region: '',
    prefecture: '',
    commune: '',
    ville: '',
    quartier: '',
    dealer: '',
    sortBy: 'created_at'
  };
  currentPage.value = 1;
};

const handleExport = async (format) => {
  try {
    loading.value = true;
    
    // Récupérer TOUS les PDV en faisant des appels successifs
    let allPDV = [];
    let currentPage = 1;
    let hasMore = true;
    
    while (hasMore) {
      const params = {
        page: currentPage,
        per_page: 100, // Maximum autorisé par l'API
        sort_by: filters.value.sortBy,
        sort_order: 'desc'
      };
      
      // Ajouter les filtres actifs
      if (filters.value.search) params.search = filters.value.search;
      if (filters.value.status) params.status = filters.value.status;
      if (filters.value.region) params.region = filters.value.region;
      if (filters.value.prefecture) params.prefecture = filters.value.prefecture;
      if (filters.value.commune) params.commune = filters.value.commune;
      if (filters.value.ville) params.ville = filters.value.ville;
      if (filters.value.quartier) params.quartier = filters.value.quartier;
      if (filters.value.dealer) params.organization_id = filters.value.dealer;
      
      const response = await PointOfSaleService.getAll(params);
      allPDV = allPDV.concat(response.data);
      
      // Vérifier s'il y a encore des pages
      hasMore = currentPage < response.last_page;
      currentPage++;
    }
    
    ExportService.exportPDV(allPDV, format);
  } catch (error) {
    console.error('Erreur lors de l\'export:', error);
  } finally {
    loading.value = false;
  }
};

// Fonction pour charger les PDV avec pagination
const fetchPointsOfSale = async (append = false) => {
  if (append) {
    loadingMore.value = true;
  } else {
    loading.value = true;
  }
  
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      sort_by: filters.value.sortBy,
      sort_order: 'desc'
    };
    
    // Ajouter les filtres actifs
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.region) params.region = filters.value.region.toUpperCase();
    if (filters.value.prefecture) params.prefecture = filters.value.prefecture;
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.dealer) params.organization_id = filters.value.dealer;
    
    const response = await PointOfSaleService.getAll(params);
    
    if (append) {
      // Ajouter à la liste existante (infinite scroll)
      pointsOfSale.value = [...pointsOfSale.value, ...(response.data || [])];
    } else {
      // Remplacer la liste (nouveau chargement)
      pointsOfSale.value = response.data || [];
    }
    
    lastPage.value = response.last_page || 1;
    total.value = response.total || 0;
    currentPage.value = response.current_page || 1;
    
  } catch (error) {
    console.error('Error loading points of sale:', error);
  } finally {
    loading.value = false;
    loadingMore.value = false;
  }
};

// Charger plus de résultats (infinite scroll)
const loadMore = async () => {
  if (currentPage.value < lastPage.value && !loadingMore.value) {
    currentPage.value++;
    await fetchPointsOfSale(true);
  }
};

// Rafraîchir la liste (reset)
const refreshList = async () => {
  currentPage.value = 1;
  await fetchPointsOfSale(false);
};

// Reset to page 1 when changing filters
watch([() => filters.value.search, () => filters.value.status, () => filters.value.region, () => filters.value.prefecture, () => filters.value.dealer, () => filters.value.sortBy], () => {
  refreshList();
}, { deep: true });

onMounted(async () => {
  await fetchPointsOfSale();
  await organizationStore.fetchOrganizations();
});
</script>
