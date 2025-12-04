<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Gestion des utilisateurs</h1>
        <div class="flex items-center gap-3">
          <ExportButton
            @export="handleExport"
            label="Exporter"
          />
          <button
            @click="showCreateModal = true"
            class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nouvel utilisateur
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <FormInput
            v-model="filters.search"
            label="Rechercher"
            placeholder="Nom, email..."
            type="text"
          />
          
          <FormSelect
            v-model="filters.role"
            label="Rôle"
            :options="[
              { label: 'Tous les rôles', value: '' },
              { label: 'Administrateur', value: 'admin' },
              { label: 'Propriétaire Dealer', value: 'dealer_owner' },
              { label: 'Commercial Dealer', value: 'dealer_agent' }
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-if="authStore.isAdmin"
            v-model="filters.organization"
            label="Organisation"
            :options="[
              { label: 'Toutes les organisations', value: '' },
              ...organizations.map(org => ({ label: org.name, value: org.id }))
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-model="filters.status"
            label="Statut"
            :options="[
              { label: 'Tous les statuts', value: '' },
              { label: 'Actifs', value: 'active' },
              { label: 'Inactifs', value: 'inactive' }
            ]"
            option-label="label"
            option-value="value"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-gray-600">Total</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.total }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="glass-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-gray-600">Actifs</p>
              <p class="text-3xl font-bold text-green-600">{{ stats.active }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="glass-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-gray-600">Admins</p>
              <p class="text-3xl font-bold text-purple-600">{{ stats.admins }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="glass-card p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-semibold text-gray-600">Dealers</p>
              <p class="text-3xl font-bold text-moov-orange">{{ stats.dealers }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card p-12 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-gray-600 font-semibold">Chargement des utilisateurs...</p>
      </div>

      <!-- Users Table -->
      <div v-else class="glass-card overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-white/50">
              <tr class="border-b border-gray-200">
                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Utilisateur</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Rôle</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Organisation</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Email</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700">Téléphone</th>
                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Statut</th>
                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in filteredUsers"
                :key="user.id"
                class="border-b border-gray-100 hover:bg-white/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-moov-orange to-moov-orange-dark flex items-center justify-center text-white font-bold">
                      {{ getUserInitials(user) }}
                    </div>
                    <div>
                      <p class="font-bold text-gray-900">{{ user.name }}</p>
                      <p class="text-xs text-gray-500">Créé le {{ formatDate(user.created_at) }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span
                    class="px-3 py-1 rounded-lg text-xs font-bold"
                    :class="getRoleBadgeClass(user.role?.name || user.role)"
                  >
                    {{ getRoleLabel(user.role?.name || user.role) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-gray-700">{{ user.organization?.name || 'N/A' }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-gray-700">{{ user.email }}</p>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-gray-700">{{ user.phone || 'N/A' }}</p>
                </td>
                <td class="px-6 py-4 text-center">
                  <button
                    @click="toggleUserStatus(user)"
                    class="inline-flex items-center gap-2"
                  >
                    <span
                      class="w-3 h-3 rounded-full"
                      :class="user.is_active ? 'bg-green-500' : 'bg-gray-400'"
                    ></span>
                    <span class="text-xs font-semibold" :class="user.is_active ? 'text-green-700' : 'text-gray-500'">
                      {{ user.is_active ? 'Actif' : 'Inactif' }}
                    </span>
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="editUser(user)"
                      class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all"
                      title="Modifier"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </button>
                    <button
                      @click="showResetPasswordModal(user)"
                      class="p-2 rounded-lg bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-all"
                      title="Réinitialiser le mot de passe"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                      </svg>
                    </button>
                    <button
                      @click="confirmDeleteUser(user)"
                      class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all"
                      title="Supprimer"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty State -->
          <div v-if="filteredUsers.length === 0" class="py-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <p class="text-gray-600 font-semibold">Aucun utilisateur trouvé</p>
            <p class="text-sm text-gray-500 mt-2">Essayez de modifier vos filtres</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <UserModal
      v-if="showCreateModal || showEditModal"
      :user="selectedUser"
      :organizations="organizations"
      @close="closeModals"
      @save="handleSaveUser"
    />

    <!-- Reset Password Modal -->
    <ResetPasswordModal
      v-if="showPasswordModal"
      :user="selectedUser"
      @close="showPasswordModal = false"
      @reset="handleResetPassword"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Navbar from '../components/Navbar.vue';
import UserModal from '../components/UserModal.vue';
import ResetPasswordModal from '../components/ResetPasswordModal.vue';
import ExportButton from '../components/ExportButton.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import { useUserStore } from '../stores/user';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import ExportService from '../services/ExportService';

const userStore = useUserStore();
const authStore = useAuthStore();
const organizationStore = useOrganizationStore();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showPasswordModal = ref(false);
const selectedUser = ref(null);
const organizations = ref([]);

const filters = ref({
  search: '',
  role: '',
  organization: '',
  status: ''
});

const loading = computed(() => userStore.loading);

const filteredUsers = computed(() => {
  let users = userStore.users;

  if (filters.value.search) {
    const query = filters.value.search.toLowerCase();
    users = users.filter(u =>
      u.name.toLowerCase().includes(query) ||
      u.email.toLowerCase().includes(query) ||
      u.phone?.toLowerCase().includes(query)
    );
  }

  if (filters.value.role) {
    users = users.filter(u => (u.role?.name || u.role) === filters.value.role);
  }

  if (filters.value.organization) {
    users = users.filter(u => u.organization_id === parseInt(filters.value.organization));
  }

  if (filters.value.status) {
    const isActive = filters.value.status === 'active';
    users = users.filter(u => u.is_active === isActive);
  }

  return users;
});

const stats = computed(() => ({
  total: userStore.users.length,
  active: userStore.users.filter(u => u.is_active).length,
  admins: userStore.users.filter(u => (u.role?.name || u.role) === 'admin').length,
  dealers: userStore.users.filter(u => {
    const roleName = u.role?.name || u.role;
    return roleName === 'dealer_owner' || roleName === 'dealer_agent';
  }).length
}));

const getUserInitials = (user) => {
  const names = user.name.split(' ');
  if (names.length >= 2) {
    return (names[0][0] + names[1][0]).toUpperCase();
  }
  return user.name.substring(0, 2).toUpperCase();
};

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-purple-100 text-purple-800',
    dealer_owner: 'bg-blue-100 text-blue-800',
    dealer_agent: 'bg-green-100 text-green-800'
  };
  return classes[role] || 'bg-gray-100 text-gray-800'
};

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrateur',
    dealer_owner: 'Propriétaire Dealer',
    dealer_agent: 'Commercial Dealer'
  };
  return labels[role] || role;
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

const editUser = (user) => {
  selectedUser.value = { ...user };
  showEditModal.value = true;
};

const showResetPasswordModal = (user) => {
  selectedUser.value = user;
  showPasswordModal.value = true;
};

const confirmDeleteUser = async (user) => {
  if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${user.name}" ?`)) {
    try {
      await userStore.deleteUser(user.id);
      alert('Utilisateur supprimé avec succès');
    } catch (error) {
      alert('Erreur lors de la suppression');
    }
  }
};

const toggleUserStatus = async (user) => {
  try {
    await userStore.toggleStatus(user.id);
  } catch (error) {
    alert('Erreur lors du changement de statut');
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  selectedUser.value = null;
};

const handleSaveUser = async (userData) => {
  try {
    if (selectedUser.value?.id) {
      await userStore.updateUser(selectedUser.value.id, userData);
      alert('Utilisateur mis à jour avec succès');
    } else {
      await userStore.createUser(userData);
      alert('Utilisateur créé avec succès');
    }
    closeModals();
  } catch (error) {
    alert('Erreur lors de l\'enregistrement');
  }
};

const handleResetPassword = async ({ userId, password }) => {
  try {
    await userStore.resetPassword(userId, password);
    alert('Mot de passe réinitialisé avec succès');
    showPasswordModal.value = false;
    selectedUser.value = null;
  } catch (error) {
    alert('Erreur lors de la réinitialisation du mot de passe');
  }
};

const handleExport = (format) => {
  const dataToExport = filteredUsers.value.length > 0 ? filteredUsers.value : userStore.users;
  ExportService.exportUsers(dataToExport, format);
};

onMounted(async () => {
  await userStore.fetchUsers();
  if (authStore.isAdmin) {
    await organizationStore.fetchOrganizations();
    organizations.value = organizationStore.organizations;
  }
});
</script>
