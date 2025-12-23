<template>
  <div class="min-h-screen">
    <Navbar />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <button
          @click="$router.push('/dealers')"
          class="mb-6 flex items-center gap-2 text-gray-600 hover:text-moov-orange font-semibold transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Retour a la liste
        </button>

        <div v-if="!loading && organization" class="space-y-6">
          <!-- Header Card -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-8 rounded-2xl">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-4">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center">
                  <span class="text-white font-bold text-2xl">{{ organization.code?.substring(0, 2) || 'XX' }}</span>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ organization.name }}</h1>
                  <p class="text-gray-600 font-medium">Code: {{ organization.code }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span
                  :class="{
                    'px-4 py-2 rounded-full text-sm font-semibold': true,
                    'bg-green-100 text-green-800': organization.is_active,
                    'bg-gray-100 text-gray-800': !organization.is_active,
                  }"
                >
                  {{ organization.is_active ? 'Actif' : 'Inactif' }}
                </span>
                <button
                  @click="editOrganization"
                  class="px-6 py-2 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:border-moov-orange transition-all"
                >
                  Modifier
                </button>
              </div>
            </div>

            <!-- Contact Info -->
            <div class="mt-8 pt-8 border-t border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Contact / Responsable</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-if="organization.contact_firstname || organization.contact_lastname" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-moov-orange mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <div>
                    <p class="text-sm text-gray-500 font-medium">Nom complet</p>
                    <p class="text-gray-900 font-semibold">{{ organization.contact_firstname }} {{ organization.contact_lastname }}</p>
                  </div>
                </div>

                <div v-if="organization.contact_phone" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-moov-orange mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                  </svg>
                  <div>
                    <p class="text-sm text-gray-500 font-medium">Telephone</p>
                    <p class="text-gray-900 font-semibold">{{ organization.contact_phone }}</p>
                  </div>
                </div>

                <div v-if="organization.contact_email" class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-moov-orange mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                  </svg>
                  <div>
                    <p class="text-sm text-gray-500 font-medium">Email</p>
                    <p class="text-gray-900 font-semibold">{{ organization.contact_email }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Dealer Stats -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div>
                <h2 class="text-xl font-bold text-gray-900">Statistiques Dealer</h2>
                <p class="text-sm text-gray-600">Transactions et performances sur la periode selectionnee</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="opt in periods"
                  :key="opt.value"
                  @click="changePeriod(opt.value)"
                  :class="[
                    'px-3 py-2 rounded-lg text-sm font-semibold transition-all',
                    selectedPeriod === opt.value
                      ? 'bg-moov-orange text-white shadow-lg'
                      : 'bg-white text-gray-700 border border-gray-200 hover:border-moov-orange'
                  ]"
                >
                  {{ opt.label }}
                </button>
              </div>
            </div>

            <div v-if="statsLoading" class="py-8 text-center">
              <div class="animate-spin rounded-full h-10 w-10 border-b-4 border-moov-orange mx-auto mb-3"></div>
              <p class="text-gray-600 font-semibold">Chargement des statistiques...</p>
            </div>

            <template v-else>
              <div v-if="dealerStats">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                  <div class="p-4 rounded-xl border border-orange-100 bg-orange-50">
                    <p class="text-sm font-semibold text-orange-700">Chiffre d'affaires (CA)</p>
                    <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(dealerStats.summary.ca) }}</p>
                    <p class="text-xs text-gray-500">Total transactions: {{ formatNumber(dealerStats.summary.total_transactions) }}</p>
                  </div>
                  <div class="p-4 rounded-xl border border-purple-100 bg-purple-50">
                    <p class="text-sm font-semibold text-purple-700">Commissions dealer</p>
                    <p class="text-2xl font-bold text-purple-900">{{ formatCurrency(dealerStats.summary.dealer_commissions) }}</p>
                    <p class="text-xs text-gray-500">Commissions PDV: {{ formatCurrency(dealerStats.summary.pdv_commissions) }}</p>
                  </div>
                  <div class="p-4 rounded-xl border border-green-100 bg-green-50">
                    <p class="text-sm font-semibold text-green-700">Depots</p>
                    <p class="text-2xl font-bold text-green-900">{{ formatCurrency(dealerStats.summary.depot_amount) }}</p>
                    <p class="text-xs text-gray-500">{{ formatNumber(dealerStats.summary.depot_count) }} operations</p>
                  </div>
                  <div class="p-4 rounded-xl border border-red-100 bg-red-50">
                    <p class="text-sm font-semibold text-red-700">Retraits</p>
                    <p class="text-2xl font-bold text-red-900">{{ formatCurrency(dealerStats.summary.retrait_amount) }}</p>
                    <p class="text-xs text-gray-500">{{ formatNumber(dealerStats.summary.retrait_count) }} operations</p>
                  </div>
                </div>

                <!-- GIVE + Actifs -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                  <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50">
                    <h3 class="font-bold text-gray-900 mb-3">Transferts GIVE</h3>
                    <div class="grid grid-cols-2 gap-3">
                      <div class="p-3 rounded-lg bg-white border border-gray-100">
                        <p class="text-sm font-semibold text-blue-700">Envoyes</p>
                        <p class="text-lg font-bold text-blue-900">{{ formatCurrency(dealerStats.transfers.sent.amount) }}</p>
                        <p class="text-xs text-gray-500">{{ formatNumber(dealerStats.transfers.sent.count) }} operations</p>
                        <p class="text-[11px] text-gray-500">Dans reseau: {{ formatCurrency(dealerStats.transfers.sent.in_amount) }} / Hors: {{ formatCurrency(dealerStats.transfers.sent.out_amount) }}</p>
                      </div>
                      <div class="p-3 rounded-lg bg-white border border-gray-100">
                        <p class="text-sm font-semibold text-cyan-700">Recus</p>
                        <p class="text-lg font-bold text-cyan-900">{{ formatCurrency(dealerStats.transfers.received.amount) }}</p>
                        <p class="text-xs text-gray-500">{{ formatNumber(dealerStats.transfers.received.count) }} operations</p>
                        <p class="text-[11px] text-gray-500">Dans reseau: {{ formatCurrency(dealerStats.transfers.received.in_amount) }} / Hors: {{ formatCurrency(dealerStats.transfers.received.out_amount) }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="p-4 rounded-xl border border-teal-100 bg-teal-50">
                    <h3 class="font-bold text-gray-900 mb-3">PDV actifs</h3>
                    <p class="text-3xl font-bold text-teal-900">{{ formatNumber(dealerStats.summary.active_pdvs) }}</p>
                    <p class="text-sm text-gray-500 mb-3">PDV ayant au moins un depot ou retrait sur la periode</p>
                    <div class="grid grid-cols-5 gap-2">
                      <div
                        v-for="item in dealerStats.actives.breakdown"
                        :key="item.days"
                        class="p-2 rounded-lg bg-white border border-gray-100 text-center"
                      >
                        <p class="text-xs font-semibold text-gray-700">J-{{ item.days }}</p>
                        <p class="text-sm font-bold text-moov-orange">{{ formatNumber(item.count) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Charts -->
                <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 rounded-2xl mb-4 space-y-6">
                  <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Evolution</h3>
                    <p class="text-xs text-gray-500">Du {{ dealerStats.period.start }} au {{ dealerStats.period.end }}</p>
                  </div>
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="p-3 rounded-xl border border-orange-100 bg-orange-50/50" style="height: 320px;">
                      <p class="text-sm font-semibold text-orange-700 mb-2">CA, depots, retraits (journalier)</p>
                      <Line v-if="dealerStats.chart.labels.length" :data="amountsChartData" :options="chartOptions" />
                      <p v-else class="text-xs text-gray-500 text-center py-6">Pas de donnees pour tracer le graphe</p>
                    </div>
                    <div class="p-3 rounded-xl border border-blue-100 bg-blue-50/50" style="height: 320px;">
                      <p class="text-sm font-semibold text-blue-700 mb-2">Transactions, actifs, commissions dealer</p>
                      <Bar v-if="dealerStats.chart.labels.length" :data="activityChartData" :options="chartOptions" />
                      <p v-else class="text-xs text-gray-500 text-center py-6">Pas de donnees pour tracer le graphe</p>
                    </div>
                  </div>
                </div>

                <!-- Top PDV -->
                <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
                  <h3 class="text-lg font-bold text-gray-900 mb-4">Top PDV (CA sur la periode)</h3>
                  <div class="overflow-x-auto">
                    <table class="w-full">
                      <thead>
                        <tr class="border-b border-gray-200">
                          <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">PDV</th>
                          <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Region / Prefecture</th>
                          <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">CA</th>
                          <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Depots</th>
                          <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Retraits</th>
                          <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Transactions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="pdv in dealerStats.top_pdvs"
                          :key="pdv.id"
                          class="border-b border-gray-100 hover:bg-white/60 transition-colors"
                        >
                          <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ pdv.nom_point }}</td>
                          <td class="px-4 py-3 text-sm text-gray-700">{{ pdv.region }} / {{ pdv.prefecture }}</td>
                          <td class="px-4 py-3 text-sm text-right font-bold text-moov-orange">{{ formatCurrency(pdv.ca) }}</td>
                          <td class="px-4 py-3 text-sm text-right text-green-700">{{ formatCurrency(pdv.depot_amount) }}</td>
                          <td class="px-4 py-3 text-sm text-right text-red-700">{{ formatCurrency(pdv.retrait_amount) }}</td>
                          <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatNumber(pdv.tx_count) }}</td>
                        </tr>
                        <tr v-if="dealerStats.top_pdvs.length === 0">
                          <td colspan="6" class="px-4 py-4 text-center text-sm text-gray-500">Aucune donnee</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-sm text-gray-500">Aucune donnee de transactions pour ce dealer.</div>
            </template>
          </div>

          <!-- PDV List for this dealer -->
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900">Points de vente</h2>
              <router-link
                :to="{ name: 'CreatePdv', query: { organization_id: organization.id } }"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white font-bold hover:shadow-lg transition-all"
              >
                + Nouveau PDV
              </router-link>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <FormInput
                v-model="searchQuery"
                label="Rechercher"
                type="text"
                placeholder="Nom du PDV..."
              />
              <FormSelect
                v-model="statusFilter"
                label="Statut"
                :options="[
                  { label: 'Tous les statuts', value: '' },
                  { label: 'En attente', value: 'pending' },
                  { label: 'Valides', value: 'validated' },
                  { label: 'Rejetes', value: 'rejected' }
                ]"
                option-label="label"
                option-value="value"
              />
              <FormSelect
                v-model="regionFilter"
                label="Region"
                :options="[
                  { label: 'Toutes regions', value: '' },
                  { label: 'Maritime', value: 'Maritime' },
                  { label: 'Plateaux', value: 'Plateaux' },
                  { label: 'Centrale', value: 'Centrale' },
                  { label: 'Kara', value: 'Kara' },
                  { label: 'Savanes', value: 'Savanes' }
                ]"
                option-label="label"
                option-value="value"
              />
            </div>

            <!-- Loading State -->
            <div v-if="loadingPOS" class="py-12 text-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-moov-orange mx-auto mb-4"></div>
              <p class="text-gray-600 font-semibold">Chargement des PDV...</p>
            </div>

            <!-- PDV Table -->
            <div v-else-if="filteredPOS.length > 0" class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Nom du PDV</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Flooz</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Region</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Ville</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Statut</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Date creation</th>
                    <th class="px-4 py-3 text-center text-sm font-bold text-gray-700">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="pos in paginatedPOS"
                    :key="pos.id"
                    class="border-b border-gray-100 hover:bg-white/50 transition-colors cursor-pointer"
                    @click="goToPOSDetail(pos.id)"
                  >
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ pos.nom_point }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ formatPhone(pos.numero_flooz) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ pos.region }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ pos.ville }}</td>
                    <td class="px-4 py-3">
                      <span
                        class="px-3 py-1 rounded-lg text-xs font-bold"
                        :class="getStatusClass(pos.status)"
                      >
                        {{ getStatusLabel(pos.status) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ formatDate(pos.created_at) }}</td>
                    <td class="px-4 py-3 text-center" @click.stop>
                      <button
                        @click="goToPOSDetail(pos.id)"
                        class="px-3 py-1 rounded-lg bg-moov-orange/10 text-moov-orange font-bold text-xs hover:bg-moov-orange hover:text-white transition-all"
                      >
                        Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination -->
              <div class="flex items-center justify-between mt-6">
                <p class="text-sm text-gray-600">
                  Affichage de {{ ((currentPage - 1) * perPage) + 1 }} a {{ Math.min(currentPage * perPage, filteredPOS.length) }} sur {{ filteredPOS.length }} PDV
                </p>
                <div class="flex items-center gap-2">
                  <button
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                    class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                  >
                    Precedent
                  </button>
                  <span class="px-4 py-2 text-sm font-semibold text-gray-700">
                    Page {{ currentPage }} / {{ totalPages }}
                  </span>
                  <button
                    @click="currentPage++"
                    :disabled="currentPage === totalPages"
                    class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                  >
                    Suivant
                  </button>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
              <p class="text-gray-600 font-semibold">Aucun point de vente trouve</p>
              <p class="text-sm text-gray-500 mt-2">{{ searchQuery || statusFilter || regionFilter ? 'Essayez de modifier vos filtres' : 'Creez votre premier PDV pour commencer' }}</p>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
          <svg class="animate-spin h-12 w-12 text-moov-orange" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <DealerModal
      v-if="showEditModal"
      :organization="editingOrganization"
      @close="showEditModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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
  Filler,
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import { useRoute, useRouter } from 'vue-router';
import { useOrganizationStore } from '../stores/organization';
import Navbar from '../components/Navbar.vue';
import DealerModal from '../components/DealerModal.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import PointOfSaleService from '../services/PointOfSaleService';
import OrganizationService from '../services/OrganizationService';
import { formatPhone } from '../utils/formatters';

const route = useRoute();
const router = useRouter();
const organizationStore = useOrganizationStore();

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

const showEditModal = ref(false);
const editingOrganization = ref(null);
const loadingPOS = ref(false);
const pointsOfSale = ref([]);
const searchQuery = ref('');
const statusFilter = ref('');
const regionFilter = ref('');
const currentPage = ref(1);
const perPage = ref(10);

const dealerStats = ref(null);
const statsLoading = ref(false);
const selectedPeriod = ref(30);
const periods = [
  { label: 'J-1', value: 1 },
  { label: 'J-7', value: 7 },
  { label: 'J-15', value: 15 },
  { label: 'J-30', value: 30 },
  { label: 'J-90', value: 90 },
];

const organization = computed(() => organizationStore.currentOrganization);
const loading = computed(() => organizationStore.loading);

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { intersect: false, mode: 'index' },
  plugins: {
    legend: { position: 'bottom', labels: { boxWidth: 12 } },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          if (ctx.dataset.yAxisID === 'y1') {
            return `${ctx.dataset.label}: ${formatNumber(ctx.raw)}`;
          }
          return `${ctx.dataset.label}: ${formatCurrency(ctx.raw)}`;
        },
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { callback: (v) => formatCurrency(v) },
    },
    y1: {
      beginAtZero: true,
      position: 'right',
      grid: { drawOnChartArea: false },
      ticks: { callback: (v) => formatNumber(v) },
    },
  },
};

const amountsChartData = computed(() => ({
  labels: dealerStats.value?.chart?.labels || [],
  datasets: [
    {
      label: 'CA',
      data: dealerStats.value?.chart?.ca || [],
      borderColor: '#f97316',
      backgroundColor: 'rgba(249, 115, 22, 0.15)',
      tension: 0.3,
      fill: true,
    },
    {
      label: 'Depots',
      data: dealerStats.value?.chart?.depot || [],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.15)',
      tension: 0.3,
      fill: true,
    },
    {
      label: 'Retraits',
      data: dealerStats.value?.chart?.retrait || [],
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.15)',
      tension: 0.3,
      fill: true,
    },
  ],
}));

const activityChartData = computed(() => ({
  labels: dealerStats.value?.chart?.labels || [],
  datasets: [
    {
      type: 'bar',
      label: 'Transactions',
      data: dealerStats.value?.chart?.tx_count || [],
      backgroundColor: 'rgba(59, 130, 246, 0.35)',
      borderColor: '#3b82f6',
      borderWidth: 1,
      yAxisID: 'y',
    },
    {
      type: 'bar',
      label: 'PDV actifs',
      data: dealerStats.value?.chart?.active_pdvs || [],
      backgroundColor: 'rgba(16, 185, 129, 0.35)',
      borderColor: '#10b981',
      borderWidth: 1,
      yAxisID: 'y1',
    },
    {
      type: 'line',
      label: 'Commissions dealer',
      data: dealerStats.value?.chart?.commissions_dealer || [],
      borderColor: '#a855f7',
      backgroundColor: 'rgba(168, 85, 247, 0.15)',
      borderWidth: 2,
      tension: 0.3,
      yAxisID: 'y',
      fill: true,
    },
  ],
}));

const filteredPOS = computed(() => {
  let filtered = pointsOfSale.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter((pos) =>
      pos.nom_point?.toLowerCase().includes(query) ||
      pos.numero_flooz?.toLowerCase().includes(query) ||
      pos.ville?.toLowerCase().includes(query)
    );
  }

  if (statusFilter.value) {
    filtered = filtered.filter((pos) => pos.status === statusFilter.value);
  }

  if (regionFilter.value) {
    filtered = filtered.filter((pos) => pos.region === regionFilter.value);
  }

  return filtered;
});

const totalPages = computed(() => Math.ceil(filteredPOS.value.length / perPage.value));

const paginatedPOS = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredPOS.value.slice(start, end);
});

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    validated: 'Valide',
    rejected: 'Rejete',
  };
  return labels[status] || status;
};

const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);
const formatCurrency = (amount) =>
  new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'XOF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount || 0);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const goToPOSDetail = (posId) => {
  router.push(`/pdv/${posId}`);
};

const loadDealerStats = async () => {
  statsLoading.value = true;
  try {
    const data = await OrganizationService.getDealerStats(route.params.id, { days: selectedPeriod.value });
    dealerStats.value = data;
  } catch (error) {
    console.error('Error loading dealer stats:', error);
    dealerStats.value = null;
  } finally {
    statsLoading.value = false;
  }
};

const changePeriod = (val) => {
  selectedPeriod.value = val;
  loadDealerStats();
};

const loadPointsOfSale = async () => {
  loadingPOS.value = true;
  try {
    const response = await PointOfSaleService.getAll({
      organization_id: route.params.id,
      per_page: 100, // Charger tous les PDV du dealer
    });
    pointsOfSale.value = response.data || [];
  } catch (error) {
    console.error('Error loading points of sale:', error);
    pointsOfSale.value = [];
  } finally {
    loadingPOS.value = false;
  }
};

const editOrganization = () => {
  editingOrganization.value = { ...organization.value };
  showEditModal.value = true;
};

const handleSaved = async () => {
  showEditModal.value = false;
  editingOrganization.value = null;
  await organizationStore.fetchOrganization(route.params.id);
};

onMounted(async () => {
  await organizationStore.fetchOrganization(route.params.id);
  await loadDealerStats();
  await loadPointsOfSale();
});
</script>
