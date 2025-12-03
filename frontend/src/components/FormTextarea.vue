<template>
  <div class="form-group">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <div class="relative">
      <textarea
        :id="id"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur')"
        @focus="$emit('focus')"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :rows="rows"
        :class="[
          'form-textarea',
          error ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-moov-orange focus:ring-moov-orange',
          disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
          textareaClass
        ]"
      ></textarea>

      <!-- Character Count -->
      <div v-if="maxLength" class="absolute bottom-3 right-3 text-xs text-gray-400">
        {{ characterCount }} / {{ maxLength }}
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
  rows: {
    type: Number,
    default: 4
  },
  maxLength: {
    type: Number,
    default: null
  },
  textareaClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const id = computed(() => {
  return props.label ? props.label.toLowerCase().replace(/\s+/g, '-') : undefined;
});

const characterCount = computed(() => {
  return props.modelValue ? props.modelValue.length : 0;
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

.form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  border-width: 2px;
  transition: all 0.2s;
  color: rgb(17, 24, 39);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.5);
}

.form-textarea:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.form-textarea::placeholder {
  color: rgb(156, 163, 175);
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
