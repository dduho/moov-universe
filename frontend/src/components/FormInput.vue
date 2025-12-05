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
        :type="type"
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
          iconRight ? 'pr-12' : 'pr-4',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-moov-orange focus:ring-moov-orange',
          disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
          inputClass
        ]"
      />

      <!-- Icon Right -->
      <div v-if="iconRight" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
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
import { computed } from 'vue';

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
