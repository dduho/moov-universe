<template>
  <div class="min-h-screen">
    <Navbar />

    <div class="py-4 sm:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900">Gestion des Dealers</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">Gérez les organisations partenaires</p>
          </div>
          <div class="flex items-center gap-2 sm:gap-3">
            <ExportButton
              @export="handleExport"
              label="Exporter"
              class="flex-1 sm:flex-none"
            />
            <button
              @click="showCreateModal = true"
              class="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 sm:px-6 py-2 sm:py-3 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl hover:shadow-moov-orange/40 hover:scale-105 transition-all duration-200 cursor-pointer text-sm sm:text-base"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              <span class="hidden sm:inline">Nouveau Dealer</span>
              <span class="sm:hidden">Nouveau</span>
            </button>
          </div>
        </div>

        <!-- Search and Filters -->
        <div class="glass-card p-4 sm:p-6 rounded-2xl mb-4 sm:mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
            <FormInput
              v-model="searchQuery"
              label="Rechercher"
              type="text"
              placeholder="Nom, code, téléphone..."
              @input="debouncedSearch"
            />
            
            <FormSelect
              v-model="filterStatus"
              label="Statut"
              :options="[
                { label: 'Tous', value: '' },
                { label: 'Actifs', value: '1' },
                { label: 'Inactifs', value: '0' }
              ]"
              option-label="label"
              option-value="value"
              @change="fetchOrganizations"
            />
          </div>
        </div>

        <!-- Organizations Grid -->
        <div v-if="!loading && organizations.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <div
            v-for="org in organizations"
            :key="org.id"
            class="glass-card p-4 sm:p-6 rounded-2xl hover:shadow-xl transition-all duration-300 cursor-pointer group"
            @click="viewOrganization(org.id)"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center">
                  <span class="text-white font-bold text-lg">{{ org.code?.substring(0, 2) || 'XX' }}</span>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 group-hover:text-moov-orange transition-colors">
                    {{ org.name }}
                  </h3>
                  <p class="text-sm text-gray-500">{{ org.code }}</p>
                </div>
              </div>
              <span
                :class="{
                  'px-3 py-1 rounded-full text-xs font-semibold': true,
                  'bg-green-100 text-green-800': org.is_active,
                  'bg-gray-100 text-gray-800': !org.is_active,
                }"
              >
                {{ org.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </div>

            <!-- Info -->
            <div class="space-y-2 mb-4">
              <div v-if="org.contact_firstname || org.contact_lastname" class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                {{ org.contact_firstname }} {{ org.contact_lastname }}
              </div>
              <div v-if="org.contact_phone" class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                {{ org.contact_phone }}
              </div>
              <div v-if="org.contact_email" class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                </svg>
                {{ org.contact_email }}
              </div>
            </div>

            <!-- Stats -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ org.point_of_sales_count || 0 }}</p>
                <p class="text-xs text-gray-500">PDV</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ org.users_count || 0 }}</p>
                <p class="text-xs text-gray-500">Utilisateurs</p>
              </div>
              <div class="text-center">
                <p class="text-2xl font-bold text-green-600">{{ org.validated_pdv_count || 0 }}</p>
                <p class="text-xs text-gray-500">Validés</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-200">
              <button
                @click.stop="editOrganization(org)"
                class="flex-1 px-4 py-2 rounded-lg bg-white/80 border-2 border-gray-200 text-gray-700 font-semibold hover:bg-white hover:border-moov-orange transition-all"
              >
                Modifier
              </button>
              <button
                @click.stop="confirmDelete(org)"
                class="px-4 py-2 rounded-lg bg-red-50 border-2 border-red-200 text-red-700 font-semibold hover:bg-red-100 transition-all"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && organizations.length === 0" class="glass-card p-12 rounded-2xl text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun dealer trouvé</h3>
          <p class="text-gray-600 mb-6">Commencez par créer votre premier dealer</p>
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white font-bold hover:shadow-lg transition-all cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Créer un Dealer
          </button>
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

    <!-- Create/Edit Modal -->
    <DealerModal
      v-if="showCreateModal || showEditModal"
      :organization="editingOrganization"
      @close="closeModals"
      @saved="handleSaved"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmModal
      v-if="showDeleteModal"
      title="Supprimer le dealer"
      :message="`Êtes-vous sûr de vouloir supprimer ${deletingOrganization?.name} ? Cette action est irréversible.`"
      confirm-text="Supprimer"
      confirm-class="bg-red-600 hover:bg-red-700"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useOrganizationStore } from '../stores/organization';
import Navbar from '../components/Navbar.vue';
import DealerModal from '../components/DealerModal.vue';
import ConfirmModal from '../components/ConfirmModal.vue';
import ExportButton from '../components/ExportButton.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import ExportService from '../services/ExportService';

const router = useRouter();
const organizationStore = useOrganizationStore();

const searchQuery = ref('');
const filterStatus = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingOrganization = ref(null);
const deletingOrganization = ref(null);

const organizations = computed(() => organizationStore.organizations);
const loading = computed(() => organizationStore.loading);

let searchTimeout;

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchOrganizations();
  }, 300);
};

const fetchOrganizations = async () => {
  const params = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (filterStatus.value !== '') params.is_active = filterStatus.value;
  
  await organizationStore.fetchOrganizations(params);
};

const viewOrganization = (id) => {
  router.push({ name: 'DealerDetail', params: { id } });
};

const editOrganization = (org) => {
  editingOrganization.value = { ...org };
  showEditModal.value = true;
};

const confirmDelete = (org) => {
  deletingOrganization.value = org;
  showDeleteModal.value = true;
};

const handleDelete = async () => {
  try {
    await organizationStore.deleteOrganization(deletingOrganization.value.id);
    showDeleteModal.value = false;
    deletingOrganization.value = null;
  } catch (error) {
    console.error('Erreur lors de la suppression:', error);
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  editingOrganization.value = null;
};

const handleSaved = () => {
  closeModals();
  fetchOrganizations();
};

const handleExport = (format) => {
  const dataToExport = organizations.value;
  ExportService.exportDealers(dataToExport, format);
};

onMounted(() => {
  fetchOrganizations();
});
</script>
