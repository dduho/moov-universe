<template>
  <div class="relative">
    <label v-if="label" class="block text-sm font-bold text-gray-700 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <div class="relative">
      <input
        :value="modelValue"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown="handleKeyDown"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-moov-orange/20"
        :class="[
          error 
            ? 'border-red-500 focus:border-red-500' 
            : 'border-gray-300 focus:border-moov-orange',
          disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'
        ]"
        type="text"
        autocomplete="off"
      />
      
      <!-- Loading spinner -->
      <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-moov-orange"></div>
      </div>
    </div>

    <!-- Dropdown suggestions -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showSuggestions && suggestions.length > 0"
        class="absolute z-50 w-full mt-1 bg-white border-2 border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto"
      >
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @mousedown.prevent="selectSuggestion(suggestion)"
          class="px-4 py-3 cursor-pointer transition-colors duration-150"
          :class="[
            index === selectedIndex 
              ? 'bg-moov-orange text-white' 
              : 'hover:bg-gray-50 text-gray-900'
          ]"
        >
          {{ suggestion }}
        </div>
      </div>
    </transition>

    <!-- Error message -->
    <p v-if="error" class="mt-2 text-sm text-red-600 font-medium">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useAutocomplete } from '../composables/useAutocomplete';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  suggestions: {
    type: Array,
    default: () => []
  },
  getSuggestions: {
    type: Function,
    default: null
  }
});

const emit = defineEmits(['update:modelValue']);

const {
  showSuggestions,
  selectedIndex,
  isLoading,
  debouncedUpdate,
  selectSuggestion: selectSuggestionFromComposable,
  handleKeyDown: handleKeyDownFromComposable,
  handleFocus: handleFocusFromComposable,
  handleBlur: handleBlurFromComposable
} = useAutocomplete({
  getSuggestions: props.getSuggestions || ((query) => {
    return Promise.resolve(
      props.suggestions.filter(s => 
        s.toLowerCase().includes(query.toLowerCase())
      )
    );
  }),
  minChars: 1,
  debounceMs: 300
});

const suggestions = ref([]);

const handleInput = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
  
  if (value) {
    if (props.getSuggestions) {
      debouncedUpdate(value);
    } else {
      suggestions.value = props.suggestions.filter(s => 
        s.toLowerCase().includes(value.toLowerCase())
      );
      showSuggestions.value = suggestions.value.length > 0;
    }
  } else {
    suggestions.value = [];
    showSuggestions.value = false;
  }
};

const selectSuggestion = (suggestion) => {
  emit('update:modelValue', suggestion);
  selectSuggestionFromComposable(suggestion);
  showSuggestions.value = false;
};

const handleKeyDown = (event) => {
  handleKeyDownFromComposable(event);
  if (event.key === 'Enter' && selectedIndex.value >= 0) {
    selectSuggestion(suggestions.value[selectedIndex.value]);
  }
};

const handleFocus = () => {
  handleFocusFromComposable();
  if (props.modelValue && suggestions.value.length === 0) {
    handleInput({ target: { value: props.modelValue } });
  }
};

const handleBlur = () => {
  handleBlurFromComposable();
};

// Watch pour mettre à jour les suggestions quand les props changent
watch(() => props.suggestions, (newSuggestions) => {
  if (props.modelValue && !props.getSuggestions) {
    suggestions.value = newSuggestions.filter(s => 
      s.toLowerCase().includes(props.modelValue.toLowerCase())
    );
  }
}, { immediate: true });
</script>
