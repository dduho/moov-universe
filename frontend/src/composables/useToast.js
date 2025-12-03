import { ref } from 'vue';

const toastComponent = ref(null);

export const useToast = () => {
  const setToastComponent = (component) => {
    toastComponent.value = component;
  };

  const toast = {
    success: (message, title = 'Succès') => {
      if (toastComponent.value) {
        toastComponent.value.success(message, title);
      }
    },
    error: (message, title = 'Erreur') => {
      if (toastComponent.value) {
        toastComponent.value.error(message, title);
      }
    },
    warning: (message, title = 'Attention') => {
      if (toastComponent.value) {
        toastComponent.value.warning(message, title);
      }
    },
    info: (message, title = 'Information') => {
      if (toastComponent.value) {
        toastComponent.value.info(message, title);
      }
    }
  };

  return {
    toast,
    setToastComponent
  };
};
