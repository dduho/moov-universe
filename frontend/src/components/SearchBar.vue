<template>
  <div class="relative">
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
        <svg
          class="w-5 h-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          ></path>
        </svg>
      </div>
      
      <input
        :value="modelValue"
        @input="handleInput"
        type="text"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full pl-12 pr-12 py-3 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
        :class="inputClass"
      />

      <button
        v-if="modelValue && clearable"
        @click="handleClear"
        class="absolute inset-y-0 right-0 pr-4 flex items-center"
      >
        <svg
          class="w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          ></path>
        </svg>
      </button>
    </div>

    <!-- Search suggestions (optional) -->
    <div
      v-if="showSuggestions && suggestions.length > 0"
      class="absolute z-50 w-full mt-2 bg-white/15 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl max-h-64 overflow-y-auto"
    >
      <button
        v-for="(suggestion, index) in suggestions"
        :key="index"
        @click="handleSuggestionClick(suggestion)"
        class="w-full px-4 py-3 text-left hover:bg-moov-orange/10 transition-colors border-b border-gray-100 last:border-b-0"
      >
        <p class="text-sm font-semibold text-gray-900">{{ suggestion.label }}</p>
        <p v-if="suggestion.description" class="text-xs text-gray-600 mt-0.5">
          {{ suggestion.description }}
        </p>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Rechercher...'
  },
  debounce: {
    type: Number,
    default: 300
  },
  clearable: {
    type: Boolean,
    default: true
  },
  disabled: {
    type: Boolean,
    default: false
  },
  inputClass: {
    type: String,
    default: ''
  },
  suggestions: {
    type: Array,
    default: () => []
  },
  showSuggestions: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'search', 'clear', 'suggestion-click']);

let debounceTimeout = null;

const handleInput = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);

  // Debounced search
  if (debounceTimeout) {
    clearTimeout(debounceTimeout);
  }

  debounceTimeout = setTimeout(() => {
    emit('search', value);
  }, props.debounce);
};

const handleClear = () => {
  emit('update:modelValue', '');
  emit('clear');
};

const handleSuggestionClick = (suggestion) => {
  emit('suggestion-click', suggestion);
  emit('update:modelValue', suggestion.label);
};
</script>


