<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        @click.self="handleCancel"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <!-- Modal -->
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl transform transition-all">
          <!-- Header -->
          <div class="flex items-center gap-4 p-6 border-b border-gray-200">
            <div
              class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
              :class="iconBgClass"
            >
              <svg class="w-6 h-6" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  v-if="type === 'danger'"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                ></path>
                <path
                  v-else-if="type === 'warning'"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
                <path
                  v-else
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6">
            <p class="text-gray-600">{{ message }}</p>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
            <button
              @click="handleCancel"
              class="px-4 py-2 rounded-lg border-2 border-gray-300 text-gray-700 font-semibold hover:bg-gray-100 transition-all"
            >
              {{ cancelText }}
            </button>
            <button
              @click="handleConfirm"
              class="px-4 py-2 rounded-lg font-semibold text-white transition-all"
              :class="confirmButtonClass"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Confirmation'
  },
  message: {
    type: String,
    required: true
  },
  confirmText: {
    type: String,
    default: 'Confirmer'
  },
  cancelText: {
    type: String,
    default: 'Annuler'
  },
  type: {
    type: String,
    default: 'warning', // 'warning', 'danger', 'info'
    validator: (value) => ['warning', 'danger', 'info'].includes(value)
  }
});

const emit = defineEmits(['confirm', 'cancel', 'update:isOpen']);

const iconBgClass = computed(() => {
  return {
    warning: 'bg-amber-100',
    danger: 'bg-red-100',
    info: 'bg-blue-100'
  }[props.type];
});

const iconColorClass = computed(() => {
  return {
    warning: 'text-amber-600',
    danger: 'text-red-600',
    info: 'text-blue-600'
  }[props.type];
});

const confirmButtonClass = computed(() => {
  return {
    warning: 'bg-amber-500 hover:bg-amber-600',
    danger: 'bg-red-500 hover:bg-red-600',
    info: 'bg-blue-500 hover:bg-blue-600'
  }[props.type];
});

const handleConfirm = () => {
  emit('confirm');
  emit('update:isOpen', false);
};

const handleCancel = () => {
  emit('cancel');
  emit('update:isOpen', false);
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}
</style>
