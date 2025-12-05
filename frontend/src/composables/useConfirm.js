import { ref } from 'vue';

const confirmState = ref({
  show: false,
  title: '',
  message: '',
  confirmText: 'Confirmer',
  cancelText: 'Annuler',
  type: 'warning', // 'warning', 'danger', 'info'
  resolve: null
});

export const useConfirm = () => {
  const confirm = (options) => {
    return new Promise((resolve) => {
      if (typeof options === 'string') {
        confirmState.value = {
          show: true,
          title: 'Confirmation',
          message: options,
          confirmText: 'Confirmer',
          cancelText: 'Annuler',
          type: 'warning',
          resolve
        };
      } else {
        confirmState.value = {
          show: true,
          title: options.title || 'Confirmation',
          message: options.message,
          confirmText: options.confirmText || 'Confirmer',
          cancelText: options.cancelText || 'Annuler',
          type: options.type || 'warning',
          resolve
        };
      }
    });
  };

  const handleConfirm = () => {
    if (confirmState.value.resolve) {
      confirmState.value.resolve(true);
    }
    confirmState.value.show = false;
  };

  const handleCancel = () => {
    if (confirmState.value.resolve) {
      confirmState.value.resolve(false);
    }
    confirmState.value.show = false;
  };

  return {
    confirmState,
    confirm,
    handleConfirm,
    handleCancel
  };
};
