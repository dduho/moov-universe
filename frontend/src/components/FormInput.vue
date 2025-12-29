<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <div class="relative">
      <!-- Icon Left -->
      <div v-if="iconLeft" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
        <component :is="iconLeft" class="w-5 h-5" />
      </div>

      <!-- Input Field -->
      <input
        :id="id"
        :type="computedType"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur')"
        @focus="$emit('focus')"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :class="[
          'form-input',
          iconLeft ? 'pl-12' : 'pl-4',
          (iconRight || type === 'password') ? 'pr-12' : 'pr-4',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-moov-orange focus:ring-moov-orange',
          disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
          inputClass
        ]"
      />

      <!-- Password Toggle Icon -->
      <button
        v-if="type === 'password'"
        type="button"
        @click="togglePasswordVisibility"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
      >
        <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        </svg>
      </button>

      <!-- Icon Right (if not password) -->
      <div v-else-if="iconRight" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
        <component :is="iconRight" class="w-5 h-5" />
      </div>
    </div>

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
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
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
  readonly: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  iconLeft: {
    type: Object,
    default: null
  },
  iconRight: {
    type: Object,
    default: null
  },
  inputClass: {
    type: String,
    default: ''
  },
  min: {
    type: [String, Number],
    default: undefined
  },
  max: {
    type: [String, Number],
    default: undefined
  },
  step: {
    type: [String, Number],
    default: undefined
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const id = computed(() => {
  return props.label ? props.label.toLowerCase().replace(/\s+/g, '-') : undefined;
});

// Password visibility toggle
const showPassword = ref(false);

const computedType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text';
  }
  return props.type;
});

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};
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

.form-input {
  width: 100%;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  border-radius: 0.75rem;
  border-width: 2px;
  transition: all 0.2s;
  color: rgb(17, 24, 39);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  /* Mobile touch optimization */
  min-height: 48px;
  font-size: 16px; /* Prevent iOS zoom */
}

@media (min-width: 640px) {
  .form-input {
    min-height: auto;
    font-size: inherit;
  }
}

.form-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.5);
}

.form-input:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.form-input::placeholder {
  color: rgb(156, 163, 175);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.875rem;
}

@media (max-width: 640px) {
  .form-input::placeholder {
    font-size: 0.8125rem;
  }
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


