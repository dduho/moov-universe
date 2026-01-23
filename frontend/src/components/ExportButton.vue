<template>
  <div class="relative inline-block">
    <button
      @click="toggleMenu"
      :disabled="disabled || loading"
      class="px-4 py-2 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      :class="buttonClass"
    >
      <svg
        v-if="!loading"
        class="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg>
      <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
      <span>{{ loading ? 'Export en cours...' : label }}</span>
      <svg
        class="w-4 h-4 transition-transform"
        :class="{ 'rotate-180': showMenu }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="showMenu"
      class="absolute right-0 mt-2 w-48 bg-white/15 backdrop-blur-xl border border-white/30 rounded-xl shadow-xl overflow-hidden z-50"
    >
      <button
        @click="handleExport('excel')"
        class="w-full px-4 py-3 text-left hover:bg-moov-orange/10 transition-colors border-b border-gray-100 flex items-center gap-3"
      >
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <div>
          <p class="text-sm font-semibold text-gray-900">Excel (.xlsx)</p>
          <p class="text-xs text-gray-600">Format recommandé</p>
        </div>
      </button>
      
      <button
        @click="handleExport('csv')"
        class="w-full px-4 py-3 text-left hover:bg-moov-orange/10 transition-colors flex items-center gap-3"
      >
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <div>
          <p class="text-sm font-semibold text-gray-900">CSV (.csv)</p>
          <p class="text-xs text-gray-600">Compatible universellement</p>
        </div>
      </button>
    </div>

    <!-- Backdrop -->
    <div
      v-if="showMenu"
      class="fixed inset-0 z-40"
      @click="showMenu = false"
    ></div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Exporter'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  buttonClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['export']);

const showMenu = ref(false);

const toggleMenu = () => {
  if (!props.loading) {
    showMenu.value = !showMenu.value;
  }
};

const handleExport = (format) => {
  showMenu.value = false;
  emit('export', format);
};
</script>


