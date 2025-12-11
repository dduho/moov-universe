<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 transition-opacity bg-gray-900/75 backdrop-blur-md" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="relative inline-block w-full max-w-2xl p-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-2xl font-bold text-gray-900">
            {{ organization ? 'Modifier le dealer' : 'Nouveau dealer' }}
          </h3>
          <button
            @click="$emit('close')"
            class="p-2 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer"
          >
            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Informations du Dealer -->
          <div>
            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
              <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
              Organisation
            </h4>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Nom du dealer <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-moov-orange/50 focus:border-moov-orange transition-all"
                placeholder="Ex: Dealer Centre Ville"
              />
            </div>
          </div>

          <!-- Informations du Contact/Responsable -->
          <div>
            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
              <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              Contact / Responsable
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Prénom -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Prénom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.contact_firstname"
                  type="text"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-moov-orange/50 focus:border-moov-orange transition-all"
                  placeholder="Jean"
                />
              </div>

              <!-- Nom -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Nom <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.contact_lastname"
                  type="text"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-moov-orange/50 focus:border-moov-orange transition-all"
                  placeholder="Dupont"
                />
              </div>

              <!-- Téléphone -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Téléphone <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.contact_phone"
                  type="tel"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-moov-orange/50 focus:border-moov-orange transition-all"
                  placeholder="+228 90 12 34 56"
                />
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.contact_email"
                  type="email"
                  required
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-moov-orange/50 focus:border-moov-orange transition-all"
                  placeholder="contact@dealer.com"
                />
              </div>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="rounded-xl bg-red-50 border-2 border-red-200 p-4">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="text-sm text-red-800 font-semibold">{{ error }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 px-6 py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 transition-all cursor-pointer"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl hover:shadow-moov-orange/40 hover:scale-105 transition-all disabled:opacity-50 disabled:hover:scale-100 cursor-pointer disabled:cursor-not-allowed"
            >
              <span v-if="!saving">{{ organization ? 'Mettre à jour' : 'Créer' }}</span>
              <span v-else class="flex items-center justify-center gap-2">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Enregistrement...
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useOrganizationStore } from '../stores/organization';

const props = defineProps({
  organization: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved']);

const organizationStore = useOrganizationStore();

const form = ref({
  name: '',
  contact_firstname: '',
  contact_lastname: '',
  contact_phone: '',
  contact_email: '',
});

const saving = ref(false);
const error = ref('');

onMounted(() => {
  if (props.organization) {
    form.value = { ...props.organization };
  }
});

const handleSubmit = async () => {
  saving.value = true;
  error.value = '';

  try {
    if (props.organization) {
      await organizationStore.updateOrganization(props.organization.id, form.value);
    } else {
      await organizationStore.createOrganization(form.value);
    }
    emit('saved');
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue';
  } finally {
    saving.value = false;
  }
};
</script>


