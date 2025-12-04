<template>
  <div>
    <label v-if="label" class="block text-sm font-bold text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <div class="relative">
      <input
        ref="startDateInput"
        :value="displayStartDate"
        @focus="showPicker = true"
        type="text"
        readonly
        :placeholder="placeholder"
        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all cursor-pointer"
        :class="{ 'border-red-500': error }"
      />

      <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    </div>

    <p v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</p>

    <!-- Date Range Picker Modal -->
    <Teleport to="body">
      <div
        v-if="showPicker"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md"
        @click.self="showPicker = false"
      >
        <div class="glass-strong rounded-2xl p-6 shadow-2xl max-w-md w-full mx-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Sélectionner une période</h3>
            <button
              @click="showPicker = false"
              class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center transition-colors"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-semibold text-orange-600 mb-2">Date de début</label>
              <input
                v-model="localStartDate"
                type="date"
                :max="localEndDate || maxDate"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-orange-600 mb-2">Date de fin</label>
              <input
                v-model="localEndDate"
                type="date"
                :min="localStartDate"
                :max="maxDate"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all"
              />
            </div>

            <!-- Quick presets -->
            <div v-if="presets" class="border-t border-gray-200 pt-4">
              <p class="text-sm font-semibold text-orange-600 mb-2">Périodes prédéfinies</p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="preset in quickPresets"
                  :key="preset.label"
                  @click="applyPreset(preset)"
                  class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-moov-orange/10 text-sm font-semibold text-gray-700 hover:text-moov-orange transition-all"
                >
                  {{ preset.label }}
                </button>
              </div>
            </div>

            <div class="flex gap-3 pt-4">
              <button
                @click="clearDates"
                class="flex-1 px-4 py-2 rounded-xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition-all"
              >
                Réinitialiser
              </button>
              <button
                @click="applyDates"
                :disabled="!localStartDate || !localEndDate"
                class="flex-1 px-4 py-2 rounded-xl bg-moov-orange text-white font-bold hover:bg-moov-orange-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Appliquer
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  startDate: {
    type: String,
    default: null
  },
  endDate: {
    type: String,
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Sélectionner une période...'
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  presets: {
    type: Boolean,
    default: true
  },
  maxDate: {
    type: String,
    default: null
  }
});

const emit = defineEmits(['update:startDate', 'update:endDate', 'change']);

const showPicker = ref(false);
const startDateInput = ref(null);
const localStartDate = ref(props.startDate);
const localEndDate = ref(props.endDate);

const displayStartDate = computed(() => {
  if (props.startDate && props.endDate) {
    const start = new Date(props.startDate).toLocaleDateString('fr-FR');
    const end = new Date(props.endDate).toLocaleDateString('fr-FR');
    return `${start} - ${end}`;
  }
  return '';
});

const quickPresets = [
  {
    label: 'Aujourd\'hui',
    getValue: () => {
      const today = new Date().toISOString().split('T')[0];
      return { start: today, end: today };
    }
  },
  {
    label: '7 derniers jours',
    getValue: () => {
      const end = new Date();
      const start = new Date();
      start.setDate(start.getDate() - 7);
      return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0]
      };
    }
  },
  {
    label: '30 derniers jours',
    getValue: () => {
      const end = new Date();
      const start = new Date();
      start.setDate(start.getDate() - 30);
      return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0]
      };
    }
  },
  {
    label: 'Ce mois',
    getValue: () => {
      const now = new Date();
      const start = new Date(now.getFullYear(), now.getMonth(), 1);
      const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      return {
        start: start.toISOString().split('T')[0],
        end: end.toISOString().split('T')[0]
      };
    }
  }
];

const applyPreset = (preset) => {
  const dates = preset.getValue();
  localStartDate.value = dates.start;
  localEndDate.value = dates.end;
  applyDates();
};

const applyDates = () => {
  emit('update:startDate', localStartDate.value);
  emit('update:endDate', localEndDate.value);
  emit('change', {
    startDate: localStartDate.value,
    endDate: localEndDate.value
  });
  showPicker.value = false;
};

const clearDates = () => {
  localStartDate.value = null;
  localEndDate.value = null;
  emit('update:startDate', null);
  emit('update:endDate', null);
  emit('change', { startDate: null, endDate: null });
  showPicker.value = false;
};

watch(() => props.startDate, (newVal) => {
  localStartDate.value = newVal;
});

watch(() => props.endDate, (newVal) => {
  localEndDate.value = newVal;
});
</script>
