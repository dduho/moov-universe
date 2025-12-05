<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="confirmState.show" class="fixed inset-0 z-[70] flex items-center justify-center">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="handleCancel"></div>
        
        <!-- Modal -->
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4">
          <!-- Header avec icône -->
          <div class="p-6 pb-4">
            <div class="flex items-start gap-4">
              <!-- Icône selon le type -->
              <div :class="iconBgClass" class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center">
                <!-- Warning -->
                <svg v-if="confirmState.type === 'warning'" class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <!-- Danger -->
                <svg v-else-if="confirmState.type === 'danger'" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <!-- Info -->
                <svg v-else class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">{{ confirmState.title }}</h3>
                <p class="mt-2 text-sm text-gray-600">{{ confirmState.message }}</p>
              </div>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
            <button
              @click="handleCancel"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              {{ confirmState.cancelText }}
            </button>
            <button
              @click="handleConfirm"
              :class="confirmBtnClass"
              class="px-4 py-2 text-sm font-medium text-white rounded-lg transition"
            >
              {{ confirmState.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useConfirm } from '../composables/useConfirm';

const { confirmState, handleConfirm, handleCancel } = useConfirm();

const iconBgClass = computed(() => {
  switch (confirmState.value.type) {
    case 'danger':
      return 'bg-red-100';
    case 'warning':
      return 'bg-yellow-100';
    default:
      return 'bg-blue-100';
  }
});

const confirmBtnClass = computed(() => {
  switch (confirmState.value.type) {
    case 'danger':
      return 'bg-red-600 hover:bg-red-700';
    case 'warning':
      return 'bg-moov-orange hover:bg-moov-orange/90';
    default:
      return 'bg-blue-600 hover:bg-blue-700';
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
