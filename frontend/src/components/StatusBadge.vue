<template>
  <span
    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-all"
    :class="badgeClass"
  >
    <span v-if="showDot" class="w-2 h-2 rounded-full" :class="dotClass"></span>
    <span>{{ label }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'default' // default, pdv, user, notification
  },
  showDot: {
    type: Boolean,
    default: false
  }
});

const badgeClass = computed(() => {
  const { status, type } = props;

  if (type === 'pdv') {
    const classes = {
      validated: 'bg-green-100 text-green-700',
      pending: 'bg-yellow-100 text-yellow-700',
      rejected: 'bg-red-100 text-red-700'
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
  }

  if (type === 'user') {
    const classes = {
      active: 'bg-green-100 text-green-700',
      inactive: 'bg-gray-100 text-gray-700'
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
  }

  if (type === 'notification') {
    const classes = {
      pdv_created: 'bg-blue-100 text-blue-700',
      pdv_validated: 'bg-green-100 text-green-700',
      pdv_rejected: 'bg-red-100 text-red-700',
      user_created: 'bg-purple-100 text-purple-700',
      proximity_alert: 'bg-orange-100 text-orange-700',
      system: 'bg-gray-100 text-gray-700'
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
  }

  // Default type
  const classes = {
    success: 'bg-green-100 text-green-700',
    warning: 'bg-yellow-100 text-yellow-700',
    error: 'bg-red-100 text-red-700',
    info: 'bg-blue-100 text-blue-700'
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
});

const dotClass = computed(() => {
  const { status, type } = props;

  if (type === 'pdv') {
    const classes = {
      validated: 'bg-green-600',
      pending: 'bg-yellow-600',
      rejected: 'bg-red-600'
    };
    return classes[status] || 'bg-gray-600';
  }

  if (type === 'user') {
    const classes = {
      active: 'bg-green-600',
      inactive: 'bg-gray-600'
    };
    return classes[status] || 'bg-gray-600';
  }

  const classes = {
    success: 'bg-green-600',
    warning: 'bg-yellow-600',
    error: 'bg-red-600',
    info: 'bg-blue-600'
  };
  return classes[status] || 'bg-gray-600';
});

const label = computed(() => {
  const { status, type } = props;

  if (type === 'pdv') {
    const labels = {
      validated: 'Validé',
      pending: 'En attente',
      rejected: 'Rejeté'
    };
    return labels[status] || status;
  }

  if (type === 'user') {
    const labels = {
      active: 'Actif',
      inactive: 'Inactif'
    };
    return labels[status] || status;
  }

  if (type === 'notification') {
    const labels = {
      pdv_created: 'Nouveau PDV',
      pdv_validated: 'PDV validé',
      pdv_rejected: 'PDV rejeté',
      user_created: 'Nouvel utilisateur',
      proximity_alert: 'Alerte proximité',
      system: 'Système'
    };
    return labels[status] || status;
  }

  // Return status as-is for default type
  return status.charAt(0).toUpperCase() + status.slice(1);
});
</script>
