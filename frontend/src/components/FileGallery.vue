<template>
  <div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div
        v-for="(file, index) in files"
        :key="file.id || index"
        class="group relative aspect-square rounded-xl overflow-hidden bg-gray-100 cursor-pointer hover:shadow-xl transition-all duration-200"
        @click="openViewer(index)"
      >
        <!-- Image Preview -->
        <img
          v-if="isImage(file)"
          :src="getFileUrl(file)"
          :alt="file.name"
          class="w-full h-full object-cover"
          @error="handleImageError"
          loading="lazy"
        >

      <!-- PDF Preview -->
      <div
        v-else-if="isPDF(file)"
        class="w-full h-full flex items-center justify-center bg-red-50"
      >
        <svg class="w-16 h-16 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
      </div>

      <!-- Other File Types -->
      <div
        v-else
        class="w-full h-full flex items-center justify-center bg-gray-200"
      >
        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
      </div>

      <!-- Overlay -->
      <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-200 flex items-center justify-center">
        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center gap-2">
          <button
            @click.stop="openViewer(index)"
            class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:scale-110 transition-all"
            title="Voir"
          >
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
          </button>

          <a
            v-if="allowDownload"
            :href="getFileUrl(file)"
            download
            target="_blank"
            @click.stop
            class="w-10 h-10 rounded-full bg-white flex items-center justify-center hover:scale-110 transition-all"
            title="Télécharger"
          >
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </a>

          <button
            v-if="allowDelete"
            @click.stop="confirmDelete(file, index)"
            class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center hover:scale-110 transition-all"
            title="Supprimer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- File Name -->
      <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/70 to-transparent">
        <p class="text-white text-xs font-semibold truncate">
          {{ file.name || `Fichier ${index + 1}` }}
        </p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="files.length === 0"
      class="col-span-full py-12 text-center"
    >
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
      <p class="text-gray-500 font-semibold">Aucun fichier</p>
    </div>
  </div>

  <!-- File Viewer Modal -->
  <FileViewer
    v-if="showViewer"
    :files="files"
    :initial-index="selectedIndex"
    @close="showViewer = false"
  />

  <!-- Delete Confirmation Dialog -->
  <ConfirmDialog
    v-model:isOpen="showDeleteDialog"
    title="Supprimer le fichier"
    :message="deleteMessage"
    confirm-text="Supprimer"
    cancel-text="Annuler"
    type="danger"
    @confirm="handleConfirmDelete"
  />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import FileViewer from './FileViewer.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import UploadService from '../services/UploadService';

const props = defineProps({
  files: {
    type: Array,
    default: () => []
  },
  allowDownload: {
    type: Boolean,
    default: true
  },
  allowDelete: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['delete']);

const showViewer = ref(false);
const selectedIndex = ref(0);
const showDeleteDialog = ref(false);
const fileToDelete = ref(null);

const deleteMessage = computed(() => {
  if (!fileToDelete.value) return '';
  return `Êtes-vous sûr de vouloir supprimer "${fileToDelete.value.file.name}" ?`;
});

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
    console.log('Created local URL for File object:', url);
    return url;
  }
  
  // If it's an uploaded file with path
  if (file.path) {
    const pathUrl = UploadService.getFileUrl(file.path);
    console.log('Generated URL from file.path:', pathUrl, 'for file:', file);
    return pathUrl;
  }
  
  // If URL is provided
  if (file.url) {
    console.log('Using file.url:', file.url);
    return file.url;
  }
  
  console.warn('Unable to generate URL for file:', file);
  return '';
};

const openViewer = (index) => {
  selectedIndex.value = index;
  showViewer.value = true;
};

const confirmDelete = (file, index) => {
  fileToDelete.value = { file, index };
  showDeleteDialog.value = true;
};

const handleConfirmDelete = () => {
  if (fileToDelete.value) {
    emit('delete', fileToDelete.value);
    fileToDelete.value = null;
  }
};

const handleImageError = (event) => {
  console.error('Image loading error:', event.target.src);
  event.target.style.display = 'none';
  event.target.parentElement.innerHTML = `
    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-200">
      <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
      <p class="text-xs text-gray-500">Image non disponible</p>
    </div>
  `;
};
</script>
