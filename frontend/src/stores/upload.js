import { defineStore } from 'pinia';
import UploadService from '../services/UploadService';

export const useUploadStore = defineStore('upload', {
  state: () => ({
    uploads: [],
    uploading: false,
    progress: 0,
    error: null
  }),

  getters: {
    uploadsByType: (state) => (type) => state.uploads.filter(u => u.type === type),
    recentUploads: (state) => state.uploads.slice(0, 10)
  },

  actions: {
    async uploadFile(file, type = 'general', metadata = {}) {
      this.uploading = true;
      this.progress = 0;
      this.error = null;

      try {
        // Validate file
        UploadService.validateFile(file, metadata.validationOptions || {});

        // Compress image if it's an image
        let fileToUpload = file;
        if (file.type.startsWith('image/')) {
          fileToUpload = await UploadService.compressImage(file);
        }

        // Upload with progress tracking
        const uploadedFile = await UploadService.upload(fileToUpload, type, {
          ...metadata,
          onProgress: (percent) => {
            this.progress = percent;
          }
        });

        this.uploads.unshift(uploadedFile);
        return uploadedFile;
      } catch (error) {
        this.error = error.message || 'Erreur lors de l\'upload';
        throw error;
      } finally {
        this.uploading = false;
        this.progress = 0;
      }
    },

    async uploadMultiple(files, type = 'general', metadata = {}) {
      this.uploading = true;
      this.error = null;

      try {
        const uploadedFiles = await UploadService.uploadMultiple(files, type, metadata);
        this.uploads.unshift(...uploadedFiles);
        return uploadedFiles;
      } catch (error) {
        this.error = error.message || 'Erreur lors de l\'upload multiple';
        throw error;
      } finally {
        this.uploading = false;
      }
    },

    async deleteFile(id) {
      try {
        await UploadService.delete(id);
        this.uploads = this.uploads.filter(u => u.id !== id);
      } catch (error) {
        this.error = error.message || 'Erreur lors de la suppression';
        throw error;
      }
    },

    clearError() {
      this.error = null;
    },

    setError(message) {
      this.error = message;
    }
  }
});
