<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <div class="relative" ref="selectContainer">
      <!-- Selected Display -->
      <button
        type="button"
        @click="toggleDropdown"
        :disabled="disabled || loading"
        :class="[
          'form-select-button',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-moov-orange focus:ring-moov-orange',
          disabled || loading ? 'bg-gray-100 cursor-not-allowed' : 'bg-white hover:border-moov-orange',
          isOpen ? 'ring-2 ring-moov-orange ring-opacity-50 border-moov-orange' : ''
        ]"
      >
        <div class="flex items-center gap-3">
          <!-- Icon -->
          <component v-if="icon" :is="icon" class="w-5 h-5 text-gray-400 flex-shrink-0" />
          
          <!-- Selected Text -->
          <span v-if="selectedText" class="text-gray-900 font-medium truncate">
            {{ selectedText }}
          </span>
          <span v-else class="text-gray-400 truncate">
            {{ placeholder }}
          </span>
        </div>

        <!-- Loading Spinner or Arrow Icon -->
        <svg
          v-if="loading"
          class="w-5 h-5 text-moov-orange animate-spin flex-shrink-0"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg
          v-else
          class="w-5 h-5 text-gray-400 transition-transform duration-200 flex-shrink-0"
          :class="{ 'rotate-180': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
    </div>

    <!-- Dropdown Menu (Teleported to body) -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-show="isOpen"
          class="dropdown-menu"
          :style="dropdownStyle"
          @click.stop
        >
          <!-- Search Box -->
          <div v-if="searchable" class="p-2 border-b border-gray-200">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher..."
                class="w-full px-3 py-2 pl-9 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-moov-orange focus:border-moov-orange"
                @click.stop
              />
              <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
          </div>

          <!-- Options List -->
          <div class="max-h-60 overflow-y-auto py-1">
            <!-- Loading State -->
            <div v-if="loading" class="px-4 py-8 flex flex-col items-center justify-center">
              <svg class="w-8 h-8 text-moov-orange animate-spin mb-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <p class="text-sm text-gray-500">Chargement...</p>
            </div>

            <!-- Options -->
            <template v-else>
              <button
                v-for="option in filteredOptions"
                :key="option[optionValue]"
                type="button"
                @click="selectOption(option)"
                :class="[
                  'dropdown-option',
                  isSelected(option) ? 'bg-moov-orange text-white' : 'text-gray-900 hover:bg-moov-orange/10 hover:text-moov-orange'
                ]"
              >
                <span class="truncate">{{ option[optionLabel] }}</span>
                
                <!-- Check Icon -->
                <svg
                  v-if="isSelected(option)"
                  class="w-5 h-5 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>

              <!-- No Results -->
              <div v-if="filteredOptions.length === 0" class="px-4 py-6 text-center text-gray-500 text-sm">
                Aucun résultat trouvé
              </div>
            </template>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Error Message -->
    <p v-if="error" class="error-message">
      {{ error }}
    </p>

    <!-- Help Text -->
    <p v-else-if="helpText" class="help-text">
      {{ helpText }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, Object, Array],
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Sélectionner...'
  },
  options: {
    type: Array,
    required: true,
    default: () => []
  },
  optionLabel: {
    type: String,
    default: 'label'
  },
  optionValue: {
    type: String,
    default: 'value'
  },
  error: {
    type: String,
    default: ''
  },
  helpText: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  searchable: {
    type: Boolean,
    default: true
  },
  icon: {
    type: Object,
    default: null
  },
  multiple: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const selectContainer = ref(null);
const dropdownStyle = ref({});

const id = computed(() => {
  return props.label ? props.label.toLowerCase().replace(/\s+/g, '-') : undefined;
});

const selectedValues = computed(() => {
  if (!props.multiple) return [];
  if (Array.isArray(props.modelValue)) return props.modelValue;
  return props.modelValue ? [props.modelValue] : [];
});

const selectedText = computed(() => {
  if (props.multiple) {
    if (!selectedValues.value.length) return '';
    const labels = props.options
      .filter(opt => selectedValues.value.includes(opt[props.optionValue]))
      .map(opt => opt[props.optionLabel]);
    return labels.join(', ');
  }
  if (!props.modelValue) return '';
  const opt = props.options.find(o => o[props.optionValue] === props.modelValue);
  return opt ? opt[props.optionLabel] : '';
});

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) {
    return props.options;
  }
  
  const query = searchQuery.value.toLowerCase();
  return props.options.filter(option => 
    option[props.optionLabel].toLowerCase().includes(query)
  );
});

const updateDropdownPosition = () => {
  if (!selectContainer.value || !isOpen.value) return;
  
  const rect = selectContainer.value.getBoundingClientRect();
  const dropdownHeight = 300; // Hauteur approximative du dropdown (max-h-60 + padding)
  const spaceBelow = window.innerHeight - rect.bottom;
  const spaceAbove = rect.top;
  
  // Déterminer si on affiche en haut ou en bas
  const shouldOpenUpward = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;
  
  if (shouldOpenUpward) {
    // Afficher au-dessus
    dropdownStyle.value = {
      position: 'fixed',
      bottom: `${window.innerHeight - rect.top + 8}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      top: 'auto'
    };
  } else {
    // Afficher en-dessous (comportement par défaut)
    dropdownStyle.value = {
      position: 'fixed',
      top: `${rect.bottom + 8}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      bottom: 'auto'
    };
  }
};

const toggleDropdown = () => {
  if (!props.disabled) {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
      searchQuery.value = '';
      setTimeout(updateDropdownPosition, 0);
      window.addEventListener('scroll', updateDropdownPosition, true);
      window.addEventListener('resize', updateDropdownPosition);
    } else {
      window.removeEventListener('scroll', updateDropdownPosition, true);
      window.removeEventListener('resize', updateDropdownPosition);
    }
  }
};

const selectOption = (option) => {
  const value = option[props.optionValue];

  if (props.multiple) {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : [];
    const idx = current.indexOf(value);
    if (idx !== -1) {
      current.splice(idx, 1);
    } else {
      current.push(value);
    }
    emit('update:modelValue', current);
    emit('change', current);
  } else {
    emit('update:modelValue', value);
    emit('change', option);
    isOpen.value = false;
    searchQuery.value = '';
  }
};

const isSelected = (option) => {
  if (props.multiple) {
    return Array.isArray(props.modelValue) && props.modelValue.includes(option[props.optionValue]);
  }
  return props.modelValue === option[props.optionValue];
};

const closeDropdown = (event) => {
  if (selectContainer.value && !selectContainer.value.contains(event.target)) {
    isOpen.value = false;
    searchQuery.value = '';
  }
};

const handleEscape = (event) => {
  if (event.key === 'Escape' && isOpen.value) {
    isOpen.value = false;
    searchQuery.value = '';
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
  document.removeEventListener('keydown', handleEscape);
  window.removeEventListener('scroll', updateDropdownPosition, true);
  window.removeEventListener('resize', updateDropdownPosition);
});
</script>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: rgb(55, 65, 81);
  margin-bottom: 0.5rem;
}

.form-select-button {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border-width: 2px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  text-align: left;
  /* Mobile touch optimization */
  min-height: 48px;
  font-size: 16px; /* Prevent iOS zoom */
}

@media (min-width: 640px) {
  .form-select-button {
    min-height: auto;
    font-size: inherit;
    gap: 0.75rem;
  }
}

@media (max-width: 640px) {
  .form-select-button {
    padding: 0.75rem;
  }
}

.form-select-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.5);
}

.form-select-button:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Fix for placeholder/selected text overflow */
.form-select-button > div:first-child {
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.form-select-button span {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Smaller placeholder text on mobile */
@media (max-width: 640px) {
  .form-select-button span.text-gray-400 {
    font-size: 0.8125rem;
  }
}

.dropdown-menu {
  z-index: 9999;
  border-radius: 0.75rem;
  border-width: 2px;
  border-color: rgb(229, 231, 235);
  background-color: white;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

.dropdown-option {
  width: 100%;
  padding: 0.75rem 1rem;
  text-align: left;
  transition: all 0.15s;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  font-weight: 500;
  /* Touch target size */
  min-height: 44px;
}

.dropdown-option span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.error-message {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: rgb(220, 38, 38);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.help-text {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: rgb(107, 114, 128);
}
</style>
