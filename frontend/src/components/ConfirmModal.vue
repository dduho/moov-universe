<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
      <!-- Backdrop -->
      <div class="fixed inset-0 transition-opacity bg-gray-900/75 backdrop-blur-md" @click="$emit('cancel')"></div>

      <!-- Modal -->
      <div class="relative inline-block w-full max-w-md p-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-3xl shadow-2xl">
        <!-- Icon -->
        <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-red-100">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>

        <!-- Content -->
        <h3 class="text-xl font-bold text-gray-900 text-center mb-2">
          {{ title }}
        </h3>
        <p class="text-gray-600 text-center mb-6">
          {{ message }}
        </p>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            @click="$emit('cancel')"
            class="flex-1 px-6 py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 transition-all"
          >
            {{ cancelText }}
          </button>
          <button
            @click="$emit('confirm')"
            :class="[
              'flex-1 px-6 py-3 rounded-xl text-white font-bold hover:shadow-lg transition-all',
              confirmClass
            ]"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    required: true,
  },
  confirmText: {
    type: String,
    default: 'Confirmer',
  },
  cancelText: {
    type: String,
    default: 'Annuler',
  },
  confirmClass: {
    type: String,
    default: 'bg-moov-orange hover:bg-moov-orange-dark',
  },
});

defineEmits(['confirm', 'cancel']);
</script>
