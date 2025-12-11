<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-md" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="p-6">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div>
                <h3 class="text-xl font-bold text-gray-900">Rejeter le PDV</h3>
                <p class="text-sm text-gray-600">{{ pointOfSale?.point_name }}</p>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Form -->
          <div class="space-y-4">
            <!-- Predefined Reasons -->
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-3">Motif de rejet</label>
              <div class="space-y-2">
                <label
                  v-for="reason in predefinedReasons"
                  :key="reason"
                  class="flex items-center p-3 rounded-xl border-2 cursor-pointer transition-all duration-200"
                  :class="selectedReason === reason
                    ? 'border-moov-orange bg-orange-50'
                    : 'border-gray-200 hover:border-gray-300'"
                >
                  <input
                    type="radio"
                    :value="reason"
                    v-model="selectedReason"
                    class="w-4 h-4 text-moov-orange focus:ring-moov-orange"
                  />
                  <span class="ml-3 text-sm font-semibold text-gray-900">{{ reason }}</span>
                </label>
              </div>
            </div>

            <!-- Custom Reason -->
            <div>
              <label class="flex items-center mb-2">
                <input
                  type="radio"
                  value="custom"
                  v-model="selectedReason"
                  class="w-4 h-4 text-moov-orange focus:ring-moov-orange"
                />
                <span class="ml-3 text-sm font-bold text-gray-700">Autre motif (précisez)</span>
              </label>
              <textarea
                v-model="customReason"
                :disabled="selectedReason !== 'custom'"
                rows="4"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-moov-orange focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                placeholder="Décrivez le motif du rejet..."
              ></textarea>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-200">
              <p class="text-sm font-semibold text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end">
          <button
            @click="$emit('close')"
            class="px-6 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-100 transition-all duration-200"
          >
            Annuler
          </button>
          <button
            @click="handleSubmit"
            :disabled="!canSubmit || loading"
            class="px-6 py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-bold hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Rejet en cours...' : 'Confirmer le rejet' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  pointOfSale: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'reject']);

const predefinedReasons = [
  'Informations incomplètes ou incorrectes',
  'Documents manquants ou invalides',
  'PDV déjà existant (doublon)',
  'Localisation GPS imprécise',
  'Trop proche d\'un PDV existant',
  'Non-conformité aux critères Moov Money'
];

const selectedReason = ref('');
const customReason = ref('');
const error = ref('');
const loading = ref(false);

const canSubmit = computed(() => {
  if (selectedReason.value === 'custom') {
    return customReason.value.trim().length > 0;
  }
  return selectedReason.value !== '';
});

const handleSubmit = async () => {
  error.value = '';
  
  if (!canSubmit.value) {
    error.value = 'Veuillez sélectionner ou saisir un motif de rejet';
    return;
  }

  const reason = selectedReason.value === 'custom' 
    ? customReason.value.trim()
    : selectedReason.value;

  loading.value = true;
  
  try {
    emit('reject', {
      id: props.pointOfSale.id,
      reason: reason
    });
  } catch (err) {
    error.value = 'Une erreur est survenue lors du rejet';
    loading.value = false;
  }
};
</script>


