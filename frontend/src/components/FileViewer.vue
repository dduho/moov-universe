<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="relative max-w-5xl max-h-[90vh] w-full">
      <!-- Close Button -->
      <button
        @click="$emit('close')"
        class="absolute top-4 right-4 z-10 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all backdrop-blur-sm"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>

      <!-- Navigation Buttons -->
      <button
        v-if="showNavigation && currentIndex > 0"
        @click="previousFile"
        class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all backdrop-blur-sm"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </button>

      <button
        v-if="showNavigation && currentIndex < files.length - 1"
        @click="nextFile"
        class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all backdrop-blur-sm"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </button>

      <!-- Content -->
      <div class="flex flex-col items-center justify-center h-full">
        <!-- Image Viewer -->
        <div
          v-if="isImage(currentFile)"
          class="max-w-full max-h-[80vh] overflow-hidden rounded-2xl"
        >
          <img
            :src="getFileUrl(currentFile)"
            :alt="currentFile.name || 'Image'"
            class="max-w-full max-h-[80vh] object-contain"
          >
        </div>

        <!-- PDF Viewer -->
        <div
          v-else-if="isPDF(currentFile)"
          class="w-full h-[80vh] bg-white rounded-2xl overflow-hidden"
        >
          <iframe
            :src="getFileUrl(currentFile)"
            class="w-full h-full"
            frameborder="0"
          ></iframe>
        </div>

        <!-- Unsupported File Type -->
        <div v-else class="glass-strong rounded-2xl p-12 text-center">
          <svg class="w-20 h-20 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
          </svg>
          <p class="text-white text-lg font-semibold mb-2">
            Aperçu non disponible
          </p>
          <p class="text-gray-300 text-sm mb-6">
            {{ currentFile.name || 'Fichier' }}
          </p>
          <a
            :href="getFileUrl(currentFile)"
            download
            target="_blank"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Télécharger
          </a>
        </div>

        <!-- File Info -->
        <div class="mt-6 text-center">
          <p class="text-white text-lg font-semibold">
            {{ currentFile.name || `Fichier ${currentIndex + 1}` }}
          </p>
          <p v-if="showNavigation" class="text-gray-300 text-sm mt-1">
            {{ currentIndex + 1 }} / {{ files.length }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import UploadService from '../services/UploadService';

const props = defineProps({
  files: {
    type: Array,
    required: true
  },
  initialIndex: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['close', 'indexChange']);

const isOpen = ref(true);
const currentIndex = ref(props.initialIndex);

const currentFile = computed(() => props.files[currentIndex.value]);
const showNavigation = computed(() => props.files.length > 1);

const isImage = (file) => {
  if (!file) return false;
  const path = file.path || file.url || file.name || '';
  return /\.(jpg|jpeg|png|gif|webp)$/i.test(path) || file.type?.startsWith('image/');
};

const isPDF = (file) => {
  if (!file) return false;
  const path = file.path || file.url || file.name || '';
  return /\.pdf$/i.test(path) || file.type === 'application/pdf';
};

const getFileUrl = (file) => {
  if (!file) return '';
  
  // If it's a File object (local preview before upload)
  if (file instanceof File) {
    const url = URL.createObjectURL(file);
    console.log('FileViewer - Created local URL for File object:', url);
    return url;
  }
  
  // If it's an uploaded file with path
  if (file.path) {
    const pathUrl = UploadService.getFileUrl(file.path);
    console.log('FileViewer - Generated URL from file.path:', pathUrl);
    return pathUrl;
  }
  
  // If URL is provided
  if (file.url) {
    console.log('FileViewer - Using file.url:', file.url);
    return file.url;
  }
  
  console.warn('FileViewer - Unable to generate URL for file:', file);
  return '';
};

const previousFile = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--;
  }
};

const nextFile = () => {
  if (currentIndex.value < props.files.length - 1) {
    currentIndex.value++;
  }
};

// Keyboard navigation
const handleKeyPress = (event) => {
  if (event.key === 'ArrowLeft') {
    previousFile();
  } else if (event.key === 'ArrowRight') {
    nextFile();
  } else if (event.key === 'Escape') {
    emit('close');
  }
};

watch(() => isOpen.value, (newVal) => {
  if (newVal) {
    window.addEventListener('keydown', handleKeyPress);
  } else {
    window.removeEventListener('keydown', handleKeyPress);
  }
});

watch(() => currentIndex.value, (newIndex) => {
  emit('indexChange', newIndex);
});

// Cleanup
window.addEventListener('keydown', handleKeyPress);
</script>
