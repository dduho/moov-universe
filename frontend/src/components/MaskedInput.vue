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
        :value="displayValue"
        @input="handleInput"
        @keypress="handleKeyPress"
        @blur="handleBlur"
        @focus="$emit('focus')"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :maxlength="maxLength"
        :inputmode="mask === 'phone' || mask === 'shortcode' ? 'numeric' : undefined"
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
  mask: {
    type: String,
    default: '', // 'phone' or 'shortcode'
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
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const id = computed(() => {
  return props.label ? props.label.toLowerCase().replace(/\s+/g, '-') : undefined;
});

const maxLength = computed(() => {
  if (props.mask === 'phone') return 15; // "228 XX XX XX XX" = 15 chars (228 + 4 spaces + 8 digits)
  if (props.mask === 'shortcode') return 8; // "XXX XXXX"
  return undefined;
});

const displayValue = computed(() => {
  const value = String(props.modelValue || '');
  
  if (props.mask === 'phone') {
    // Format: 228 XX XX XX XX
    // Remove all non-digits
    let digits = value.replace(/\D/g, '');
    
    // If empty or doesn't start with 228, start with 228
    if (!digits || digits.length === 0) {
      return '228 ';
    }
    
    // Ensure it starts with 228
    if (!digits.startsWith('228')) {
      digits = '228' + digits;
    }
    
    // Limit to 11 total digits (228 + 8 digits)
    digits = digits.slice(0, 11);
    
    // Format with spaces: 228 XX XX XX XX
    let formatted = '228';
    const remaining = digits.slice(3); // Get digits after 228
    
    if (remaining.length > 0) {
      formatted += ' ' + remaining.slice(0, 2);
    }
    if (remaining.length > 2) {
      formatted += ' ' + remaining.slice(2, 4);
    }
    if (remaining.length > 4) {
      formatted += ' ' + remaining.slice(4, 6);
    }
    if (remaining.length > 6) {
      formatted += ' ' + remaining.slice(6, 8);
    }
    
    return formatted;
  }
  
  if (props.mask === 'shortcode') {
    // Format: XXX XXXX
    const digits = value.replace(/\D/g, '');
    if (digits.length === 0) return '';
    if (digits.length <= 3) return digits;
    return `${digits.slice(0, 3)} ${digits.slice(3, 7)}`;
  }
  
  return value;
});

const handleInput = (event) => {
  let value = event.target.value;
  
  if (props.mask === 'phone') {
    // Extract only digits
    let digits = value.replace(/\D/g, '');
    
    // Ensure it starts with 228
    if (!digits.startsWith('228')) {
      // If user is trying to delete 228, prevent it
      if (digits.length < 3) {
        digits = '228';
      } else {
        digits = '228' + digits;
      }
    }
    
    // Limit to 11 digits total (228 + 8 digits)
    digits = digits.slice(0, 11);
    
    emit('update:modelValue', digits);
  } else if (props.mask === 'shortcode') {
    // Extract only digits
    const digits = value.replace(/\D/g, '').slice(0, 7);
    emit('update:modelValue', digits);
  } else {
    emit('update:modelValue', value);
  }
};

const handleBlur = (event) => {
  emit('blur', event);
};

const handleKeyPress = (event) => {
  // For phone and shortcode masks, only allow digits
  if (props.mask === 'phone' || props.mask === 'shortcode') {
    const char = event.key;
    // Allow: backspace, delete, tab, escape, enter, and digits 0-9
    if (!/[0-9]/.test(char) && 
        !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(char)) {
      event.preventDefault();
    }
  }
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


