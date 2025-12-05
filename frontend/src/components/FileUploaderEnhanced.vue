<template>
  <div class="space-y-4">
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3">
      <!-- Take Photo Button (Mobile) -->
      <button
        type="button"
        @click="triggerCamera"
        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 sm:py-4 bg-moov-orange text-white font-bold rounded-xl hover:bg-moov-orange-dark transition-all duration-200 active:scale-95 touch-manipulation min-h-[48px]"
      >
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>📷 Prendre une photo</span>
      </button>
      
      <!-- Choose File Button -->
      <button
        type="button"
        @click="triggerFileInput"
        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 sm:py-4 bg-white border-2 border-gray-300 text-gray-700 font-bold rounded-xl hover:border-moov-orange hover:bg-gray-50 transition-all duration-200 active:scale-95 touch-manipulation min-h-[48px]"
      >
        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
        <span>📁 Choisir fichier</span>
      </button>
    </div>

    <!-- Camera Input (hidden, for camera capture) -->
    <input
      ref="cameraInput"
      type="file"
      accept="image/*"
      capture="environment"
      :multiple="burstMode"
      @change="handleCameraCapture"
      class="hidden"
    >
    
    <!-- File Input (hidden, for file selection) -->
    <input
      ref="fileInput"
      type="file"
      :accept="acceptedTypes"
      :multiple="multiple"
      @change="handleFileSelect"
      class="hidden"
    >

    <!-- Drop Zone -->
    <div
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      class="relative border-2 border-dashed rounded-2xl p-4 sm:p-8 transition-all duration-200 cursor-pointer touch-manipulation min-h-[120px]"
      :class="isDragging 
        ? 'border-moov-orange bg-moov-orange/10' 
        : 'border-gray-300 hover:border-moov-orange hover:bg-gray-50'"
      @click="triggerFileInput"
    >
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 rounded-full bg-moov-orange/10 flex items-center justify-center">
          <svg class="w-6 h-6 sm:w-8 sm:h-8 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
          </svg>
        </div>

        <h3 class="text-sm sm:text-lg font-bold text-gray-900 mb-1 sm:mb-2">
          {{ isDragging ? 'Déposez ici' : 'Glissez-déposez' }}
        </h3>
        
        <p class="text-xs sm:text-sm text-gray-500">
          {{ formatAcceptedTypes() }} - Max {{ maxSizeMB }}MB
        </p>
      </div>
    </div>

    <!-- Document Guide Overlay (for ID cards) -->
    <div v-if="showGuide && guideType" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4">
      <div class="relative w-full max-w-md">
        <!-- Close button -->
        <button 
          @click="closeGuide"
          class="absolute -top-12 right-0 p-2 text-white hover:text-gray-300 transition-colors"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <!-- Guide Frame -->
        <div class="relative border-4 border-dashed border-white/60 rounded-xl aspect-[1.586/1] flex items-center justify-center">
          <div class="absolute inset-4 border-2 border-white/30 rounded-lg"></div>
          <p class="text-white text-center text-sm sm:text-base px-4">
            {{ guideType === 'id' ? 'Cadrez la pièce d\'identité ici' : 'Cadrez le document ici' }}
          </p>
        </div>

        <p class="text-white text-center text-xs sm:text-sm mt-4">
          Assurez-vous que le document est bien éclairé et lisible
        </p>

        <!-- Flash Toggle -->
        <button
          @click="toggleFlash"
          class="absolute top-4 left-4 p-3 rounded-full bg-white/20 text-white hover:bg-white/30 transition-colors"
          :class="{ 'bg-yellow-500/50': flashEnabled }"
        >
          <svg v-if="flashEnabled" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7 2v11h3v9l7-12h-4l4-8z"></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Upload Progress -->
    <div v-if="uploadStore.uploading" class="glass-card p-4">
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
        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-red-800 break-words">{{ uploadStore.error }}</p>
        </div>
        <button
          @click="uploadStore.clearError()"
          class="p-1 text-red-600 hover:text-red-800 flex-shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Preview Files -->
    <div v-if="files.length > 0" class="space-y-3">
      <h4 class="text-sm font-bold text-gray-700">Fichiers ({{ files.length }})</h4>
      
      <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3">
        <div
          v-for="(file, index) in files"
          :key="index"
          class="relative glass-card p-2 sm:p-3 group"
        >
          <!-- Preview -->
          <div 
            class="aspect-square rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center cursor-pointer"
            @click="previewFile(file)"
          >
            <img
              v-if="isImage(file)"
              :src="getFilePreview(file)"
              :alt="file.name"
              class="w-full h-full object-cover"
              loading="lazy"
            >
            <svg v-else class="w-10 h-10 sm:w-12 sm:h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
          </div>

          <!-- Info -->
          <div class="mt-2">
            <p class="text-xs font-medium text-gray-900 truncate">{{ file.name }}</p>
            <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
          </div>

          <!-- Remove Button -->
          <button
            @click.stop="removeFile(index)"
            class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-red-500 text-white shadow-lg flex items-center justify-center hover:bg-red-600 transition-all active:scale-95 touch-manipulation"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Upload Button -->
    <div v-if="files.length > 0 && !autoUpload" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
      <button
        @click="clearFiles"
        class="px-4 sm:px-6 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all min-h-[48px] active:scale-95 touch-manipulation"
      >
        Annuler
      </button>
      <button
        @click="uploadFiles"
        :disabled="uploadStore.uploading"
        class="px-4 sm:px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 min-h-[48px] active:scale-95 touch-manipulation"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        {{ uploadStore.uploading ? 'Upload...' : `Upload ${files.length} fichier${files.length > 1 ? 's' : ''}` }}
      </button>
    </div>

    <!-- Image Preview Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div 
          v-if="previewImage" 
          class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
          @click="closePreview"
        >
          <button 
            class="absolute top-4 right-4 p-3 text-white hover:text-gray-300 transition-colors z-10"
            @click="closePreview"
          >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
          
          <img 
            :src="previewImage" 
            class="max-w-full max-h-full object-contain rounded-lg"
            @click.stop
          >
        </div>
      </Transition>
    </Teleport>
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
  },
  burstMode: {
    type: Boolean,
    default: false
  },
  guideType: {
    type: String,
    default: null // 'id', 'passport', 'document'
  }
});

const emit = defineEmits(['uploaded', 'error']);

const uploadStore = useUploadStore();

const cameraInput = ref(null);
const fileInput = ref(null);
const files = ref([]);
const isDragging = ref(false);
const showGuide = ref(false);
const flashEnabled = ref(false);
const previewImage = ref(null);

const maxSizeMB = props.maxSize / (1024 * 1024);

const formatAcceptedTypes = () => {
  const types = props.acceptedTypes.split(',').map(t => t.split('/')[1]?.toUpperCase() || t);
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

const triggerCamera = () => {
  if (props.guideType) {
    showGuide.value = true;
    // Auto-close guide after delay and open camera
    setTimeout(() => {
      showGuide.value = false;
      cameraInput.value?.click();
    }, 2000);
  } else {
    cameraInput.value?.click();
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const closeGuide = () => {
  showGuide.value = false;
};

const toggleFlash = () => {
  flashEnabled.value = !flashEnabled.value;
  // Note: Flash control requires MediaStream API for more advanced implementation
};

const handleCameraCapture = (event) => {
  const selectedFiles = Array.from(event.target.files);
  addFiles(selectedFiles);
  event.target.value = '';
  
  // Haptic feedback
  if ('vibrate' in navigator) {
    navigator.vibrate(50);
  }
};

const handleFileSelect = (event) => {
  const selectedFiles = Array.from(event.target.files);
  addFiles(selectedFiles);
  event.target.value = '';
};

const handleDrop = (event) => {
  isDragging.value = false;
  const droppedFiles = Array.from(event.dataTransfer.files);
  addFiles(droppedFiles);
};

const addFiles = (newFiles) => {
  // Validate file size
  const validFiles = newFiles.filter(file => {
    if (file.size > props.maxSize) {
      uploadStore.setError(`Le fichier "${file.name}" dépasse la taille maximale de ${maxSizeMB}MB`);
      return false;
    }
    return true;
  });

  if (props.multiple) {
    const remainingSlots = props.maxFiles - files.value.length;
    if (remainingSlots <= 0) {
      uploadStore.setError(`Maximum ${props.maxFiles} fichiers autorisés`);
      return;
    }
    
    const filesToAdd = validFiles.slice(0, remainingSlots);
    files.value.push(...filesToAdd);
    
    if (validFiles.length > remainingSlots) {
      uploadStore.setError(`Seuls ${remainingSlots} fichier(s) ajouté(s). Max ${props.maxFiles} fichiers`);
    }
  } else {
    files.value = validFiles.length > 0 ? [validFiles[0]] : [];
  }

  if (props.autoUpload && files.value.length > 0) {
    uploadFiles();
  }
};

const previewFile = (file) => {
  if (isImage(file)) {
    previewImage.value = getFilePreview(file);
  }
};

const closePreview = () => {
  previewImage.value = null;
};

const removeFile = (index) => {
  files.value.splice(index, 1);
  // Haptic feedback
  if ('vibrate' in navigator) {
    navigator.vibrate(30);
  }
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
    
    // Success haptic
    if ('vibrate' in navigator) {
      navigator.vibrate([50, 50, 50]);
    }
    
    clearFiles();
  } catch (error) {
    emit('error', error);
  }
};
</script>
