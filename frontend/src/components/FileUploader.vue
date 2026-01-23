<template>
  <div class="space-y-4">
    <!-- Drop Zone -->
    <div
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @click="triggerFileInput"
      class="relative border-2 border-dashed rounded-2xl p-8 transition-all duration-200 cursor-pointer"
      :class="isDragging 
        ? 'border-moov-orange bg-moov-orange/10' 
        : 'border-gray-300 hover:border-moov-orange hover:bg-gray-50'"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="acceptedTypes"
        :multiple="multiple"
        capture="environment"
        @change="handleFileSelect"
        class="hidden"
      >

      <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-moov-orange/10 flex items-center justify-center">
          <svg class="w-8 h-8 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
          </svg>
        </div>

        <h3 class="text-lg font-bold text-gray-900 mb-2">
          {{ isDragging ? 'Déposez vos fichiers ici' : 'Télécharger des fichiers' }}
        </h3>
        
        <p class="text-sm text-gray-600 mb-2">
          Glissez-déposez ou cliquez pour sélectionner
        </p>

        <p class="text-xs text-gray-500">
          {{ formatAcceptedTypes() }} - Max {{ maxSizeMB }}MB
        </p>
      </div>
    </div>

    <!-- Upload Progress -->
    <div v-if="uploadStore.uploading" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-semibold text-gray-700">Upload en cours...</span>
        <span class="text-sm font-bold text-moov-orange">{{ uploadStore.progress }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-gradient-to-r from-moov-orange to-moov-orange-dark h-2 rounded-full transition-all duration-300"
          :style="{ width: uploadStore.progress + '%' }"
        ></div>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="uploadStore.error" class="p-4 rounded-xl bg-red-50 border border-red-200">
      <div class="flex items-start gap-3">
        <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-red-800">{{ uploadStore.error }}</p>
        </div>
        <button
          @click="uploadStore.clearError()"
          class="text-red-600 hover:text-red-800"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Preview Files -->
    <div v-if="files.length > 0" class="space-y-3">
      <h4 class="text-sm font-bold text-gray-700">Fichiers sélectionnés ({{ files.length }})</h4>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="(file, index) in files"
          :key="index"
          class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 flex items-center gap-4"
        >
          <!-- Preview -->
          <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">
            <img
              v-if="isImage(file)"
              :src="getFilePreview(file)"
              :alt="file.name"
              class="w-full h-full object-cover"
            >
            <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
          </div>

          <!-- Remove -->
          <button
            @click="removeFile(index)"
            class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Button -->
    <div v-if="files.length > 0 && !autoUpload" class="flex items-center justify-end gap-3">
      <button
        @click="clearFiles"
        class="px-6 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all"
      >
        Annuler
      </button>
      <button
        @click="uploadFiles"
        :disabled="uploadStore.uploading"
        class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        {{ uploadStore.uploading ? 'Upload...' : `Upload ${files.length} fichier${files.length > 1 ? 's' : ''}` }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUploadStore } from '../stores/upload';

const props = defineProps({
  multiple: {
    type: Boolean,
    default: false
  },
  maxFiles: {
    type: Number,
    default: 4
  },
  acceptedTypes: {
    type: String,
    default: 'image/jpeg,image/png,image/jpg,application/pdf'
  },
  maxSize: {
    type: Number,
    default: 5 * 1024 * 1024 // 5MB
  },
  type: {
    type: String,
    default: 'general'
  },
  autoUpload: {
    type: Boolean,
    default: false
  },
  metadata: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['uploaded', 'error']);

const uploadStore = useUploadStore();

const fileInput = ref(null);
const files = ref([]);
const isDragging = ref(false);

const maxSizeMB = props.maxSize / (1024 * 1024);

const formatAcceptedTypes = () => {
  const types = props.acceptedTypes.split(',').map(t => t.split('/')[1].toUpperCase());
  return types.join(', ');
};

const isImage = (file) => {
  return file.type.startsWith('image/');
};

const getFilePreview = (file) => {
  return URL.createObjectURL(file);
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleFileSelect = (event) => {
  const selectedFiles = Array.from(event.target.files);
  addFiles(selectedFiles);
  event.target.value = ''; // Reset input
};

const handleDrop = (event) => {
  isDragging.value = false;
  const droppedFiles = Array.from(event.dataTransfer.files);
  addFiles(droppedFiles);
};

const addFiles = (newFiles) => {
  // Check file count limit for multiple uploads
  if (props.multiple) {
    const remainingSlots = props.maxFiles - files.value.length;
    if (remainingSlots <= 0) {
      uploadStore.setError(`Vous pouvez télécharger maximum ${props.maxFiles} fichiers`);
      return;
    }
    
    const filesToAdd = newFiles.slice(0, remainingSlots);
    files.value.push(...filesToAdd);
    
    if (newFiles.length > remainingSlots) {
      uploadStore.setError(`Seuls ${remainingSlots} fichier(s) ont été ajouté(s). Maximum ${props.maxFiles} fichiers autorisés`);
    }
  } else {
    files.value = [newFiles[0]];
  }

  if (props.autoUpload) {
    uploadFiles();
  }
};

const removeFile = (index) => {
  files.value.splice(index, 1);
};

const clearFiles = () => {
  files.value = [];
  uploadStore.clearError();
};

const uploadFiles = async () => {
  try {
    if (props.multiple) {
      const uploaded = await uploadStore.uploadMultiple(files.value, props.type, props.metadata);
      emit('uploaded', uploaded);
    } else {
      const uploaded = await uploadStore.uploadFile(files.value[0], props.type, props.metadata);
      emit('uploaded', uploaded);
    }
    
    clearFiles();
  } catch (error) {
    emit('error', error);
  }
};
</script>


