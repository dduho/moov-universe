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
          Retour à la liste
        </button>

        <div v-if="!loading && organization" class="space-y-6">
          <!-- Header Card -->
          <div class="glass-card p-8 rounded-2xl">
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
                    <p class="text-sm text-gray-500 font-medium">Téléphone</p>
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

          <!-- Stats Cards -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <StatsCard 
              label="Total PDV"
              :value="stats.total || 0"
              icon="HomeIcon"
              color="orange"
            />
            <StatsCard 
              label="En attente"
              :value="stats.pending || 0"
              icon="ClockIcon"
              color="yellow"
            />
            <StatsCard 
              label="Validés"
              :value="stats.validated || 0"
              icon="CheckIcon"
              color="green"
            />
            <StatsCard 
              label="Utilisateurs"
              :value="organization.users_count || 0"
              icon="UserIcon"
              color="blue"
            />
          </div>

          <!-- PDV List for this dealer -->
          <div class="glass-card p-6 rounded-2xl">
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
                  { label: 'Validés', value: 'validated' },
                  { label: 'Rejetés', value: 'rejected' }
                ]"
                option-label="label"
                option-value="value"
              />
              <FormSelect
                v-model="regionFilter"
                label="Région"
                :options="[
                  { label: 'Toutes régions', value: '' },
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
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Région</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Ville</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Statut</th>
                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Date création</th>
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
                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ pos.point_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ pos.flooz_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ pos.region }}</td>
                    <td class="px-4 py-3 text-sm text-gray-700">{{ pos.city }}</td>
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
                        Détails
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination -->
              <div class="flex items-center justify-between mt-6">
                <p class="text-sm text-gray-600">
                  Affichage de {{ ((currentPage - 1) * perPage) + 1 }} à {{ Math.min(currentPage * perPage, filteredPOS.length) }} sur {{ filteredPOS.length }} PDV
                </p>
                <div class="flex items-center gap-2">
                  <button
                    @click="currentPage--"
                    :disabled="currentPage === 1"
                    class="px-4 py-2 rounded-lg bg-white border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                  >
                    Précédent
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
              <p class="text-gray-600 font-semibold">Aucun point de vente trouvé</p>
              <p class="text-sm text-gray-500 mt-2">{{ searchQuery || statusFilter || regionFilter ? 'Essayez de modifier vos filtres' : 'Créez votre premier PDV pour commencer' }}</p>
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
import { useRoute, useRouter } from 'vue-router';
import { useOrganizationStore } from '../stores/organization';
import Navbar from '../components/Navbar.vue';
import StatsCard from '../components/StatsCard.vue';
import DealerModal from '../components/DealerModal.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import PointOfSaleService from '../services/PointOfSaleService';

const route = useRoute();
const router = useRouter();
const organizationStore = useOrganizationStore();

const showEditModal = ref(false);
const editingOrganization = ref(null);
const loadingPOS = ref(false);
const pointsOfSale = ref([]);
const searchQuery = ref('');
const statusFilter = ref('');
const regionFilter = ref('');
const currentPage = ref(1);
const perPage = ref(10);

const organization = computed(() => organizationStore.currentOrganization);
const loading = computed(() => organizationStore.loading);

const stats = computed(() => {
  if (!organization.value) return { total: 0, pending: 0, validated: 0 };
  
  return {
    total: organization.value.point_of_sales_count || 0,
    pending: organization.value.pending_count || 0,
    validated: organization.value.validated_count || 0,
  };
});

const filteredPOS = computed(() => {
  let filtered = pointsOfSale.value;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(pos =>
      pos.point_name.toLowerCase().includes(query) ||
      pos.flooz_number.toLowerCase().includes(query) ||
      pos.city?.toLowerCase().includes(query)
    );
  }

  if (statusFilter.value) {
    filtered = filtered.filter(pos => pos.status === statusFilter.value);
  }

  if (regionFilter.value) {
    filtered = filtered.filter(pos => pos.region === regionFilter.value);
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
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
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
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: 'short', 
    year: 'numeric'
  });
};

const goToPOSDetail = (posId) => {
  router.push(`/pdv/${posId}`);
};

const loadPointsOfSale = async () => {
  loadingPOS.value = true;
  try {
    const data = await PointOfSaleService.getAll({ organization_id: route.params.id });
    pointsOfSale.value = data;
  } catch (error) {
    console.error('Error loading points of sale:', error);
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
  await loadPointsOfSale();
});

// Icon components
const HomeIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
    </svg>
  `
};

const ClockIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
};

const CheckIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
  `
};

const UserIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
  `
};
</script>
