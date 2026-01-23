import api from './api';

export default {
  /**
   * Upload a file
   */
  async upload(file, type = 'general', metadata = {}) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    
    // Add metadata
    Object.keys(metadata).forEach(key => {
      formData.append(key, metadata[key]);
    });

    const response = await api.post('/uploads', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
        // Emit progress event
        if (metadata.onProgress) {
          metadata.onProgress(percentCompleted);
        }
      }
    });

    return response.data.file;
  },

  /**
   * Upload multiple files
   */
  async uploadMultiple(files, type = 'general', metadata = {}) {
    const formData = new FormData();
    
    files.forEach((file, index) => {
      formData.append(`files[${index}]`, file);
    });
    
    formData.append('type', type);
    
    Object.keys(metadata).forEach(key => {
      formData.append(key, metadata[key]);
    });

    const response = await api.post('/uploads/multiple', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    return response.data.files || [];
  },

  /**
   * Delete a file
   */
  async delete(pathOrId) {
    const response = await api.delete('/uploads', {
      data: { path: pathOrId }
    });
    return response.data;
  },

  /**
   * Get file URL
   */
  getFileUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    // En production, utiliser une URL relative pour éviter les problèmes de Mixed Content
    // Le navigateur résoudra /storage/... vers le domaine actuel
    if (path.startsWith('/storage/')) return path;
    return `/storage/${path}`;
  },

  /**
   * Validate file before upload
   */
  validateFile(file, options = {}) {
    const {
      maxSize = 5 * 1024 * 1024, // 5MB par défaut
      allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
      allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf']
    } = options;

    // Check file size
    if (file.size > maxSize) {
      throw new Error(`Le fichier est trop volumineux. Taille maximum : ${maxSize / (1024 * 1024)}MB`);
    }

    // Check MIME type
    if (!allowedTypes.includes(file.type)) {
      throw new Error(`Type de fichier non autorisé. Types acceptés : ${allowedTypes.join(', ')}`);
    }

    // Check extension
    const extension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(extension)) {
      throw new Error(`Extension non autorisée. Extensions acceptées : ${allowedExtensions.join(', ')}`);
    }

    return true;
  },

  /**
   * Compress image before upload
   */
  async compressImage(file, maxWidth = 1920, quality = 0.8) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      
      reader.onload = (e) => {
        const img = new Image();
        
        img.onload = () => {
          const canvas = document.createElement('canvas');
          let width = img.width;
          let height = img.height;

          // Calculate new dimensions
          if (width > maxWidth) {
            height = (height * maxWidth) / width;
            width = maxWidth;
          }

          canvas.width = width;
          canvas.height = height;

          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);

          canvas.toBlob(
            (blob) => {
              const compressedFile = new File([blob], file.name, {
                type: file.type,
                lastModified: Date.now()
              });
              resolve(compressedFile);
            },
            file.type,
            quality
          );
        };

        img.onerror = reject;
        img.src = e.target.result;
      };

      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }
};
