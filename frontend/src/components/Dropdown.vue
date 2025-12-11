<template>
  <div class="relative inline-block">
    <button
      @click="isOpen = !isOpen"
      class="px-4 py-2 rounded-xl bg-white border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2 min-w-[200px] justify-between"
      :class="buttonClass"
    >
      <span class="flex items-center gap-2">
        <component v-if="selectedOption?.icon" :is="selectedOption.icon" class="w-5 h-5" />
        <span>{{ selectedOption?.label || placeholder }}</span>
      </span>
      <svg
        class="w-5 h-5 text-gray-400 transition-transform"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-40"
        @click="isOpen = false"
      >
        <div
          class="absolute mt-2 bg-white/15 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl overflow-hidden"
          :style="dropdownStyle"
          @click.stop
        >
          <div v-if="searchable" class="p-2 border-b border-gray-200">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher..."
              class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none text-sm"
              @click.stop
            />
          </div>

          <div class="max-h-64 overflow-y-auto">
            <button
              v-for="option in filteredOptions"
              :key="option.value"
              @click="selectOption(option)"
              class="w-full px-4 py-3 text-left hover:bg-moov-orange/10 transition-colors border-b border-gray-100 last:border-b-0 flex items-center gap-3"
              :class="{ 'bg-moov-orange/5': option.value === modelValue }"
            >
              <component v-if="option.icon" :is="option.icon" class="w-5 h-5 text-gray-600" />
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">{{ option.label }}</p>
                <p v-if="option.description" class="text-xs text-gray-600 mt-0.5">
                  {{ option.description }}
                </p>
              </div>
              <svg
                v-if="option.value === modelValue"
                class="w-5 h-5 text-moov-orange"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </button>

            <div
              v-if="filteredOptions.length === 0"
              class="px-4 py-8 text-center text-gray-500 text-sm"
            >
              Aucun résultat trouvé
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null
  },
  options: {
    type: Array,
    required: true,
    // Array of { value, label, description?, icon? }
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...'
  },
  searchable: {
    type: Boolean,
    default: false
  },
  buttonClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const buttonRef = ref(null);
const dropdownStyle = ref({});

const selectedOption = computed(() => {
  return props.options.find(opt => opt.value === props.modelValue);
});

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) {
    return props.options;
  }

  const query = searchQuery.value.toLowerCase();
  return props.options.filter(opt =>
    opt.label.toLowerCase().includes(query) ||
    (opt.description && opt.description.toLowerCase().includes(query))
  );
});

const selectOption = (option) => {
  emit('update:modelValue', option.value);
  emit('change', option);
  isOpen.value = false;
  searchQuery.value = '';
};

// Position dropdown relative to button
const updateDropdownPosition = () => {
  if (buttonRef.value) {
    const rect = buttonRef.value.getBoundingClientRect();
    dropdownStyle.value = {
      position: 'absolute',
      top: `${rect.bottom + window.scrollY}px`,
      left: `${rect.left + window.scrollX}px`,
      width: `${rect.width}px`,
      zIndex: 50
    };
  }
};

watch(isOpen, (newVal) => {
  if (newVal) {
    updateDropdownPosition();
  }
});

onMounted(() => {
  window.addEventListener('scroll', updateDropdownPosition);
  window.addEventListener('resize', updateDropdownPosition);
});

onUnmounted(() => {
  window.removeEventListener('scroll', updateDropdownPosition);
  window.removeEventListener('resize', updateDropdownPosition);
});
</script>


