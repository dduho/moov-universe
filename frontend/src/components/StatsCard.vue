<template>
  <div 
    class="glass-card rounded-2xl p-6 hover:shadow-xl transition-all duration-300 border border-white/50"
    :class="clickable ? 'cursor-pointer hover:scale-105' : ''"
    @click="clickable && $emit('click')"
  >
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <div 
          class="w-14 h-14 rounded-xl flex items-center justify-center"
          :class="iconBgClass"
        >
          <component :is="icon" class="w-7 h-7 text-white" />
        </div>
        <div>
          <p class="text-sm font-medium text-gray-600">{{ label }}</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ value }}</p>
          <p v-if="trend" class="text-xs mt-1" :class="trend > 0 ? 'text-green-600' : 'text-red-600'">
            <span v-if="trend > 0">↑</span>
            <span v-else>↓</span>
            {{ Math.abs(trend) }}% vs dernier mois
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: Object,
    required: true,
  },
  color: {
    type: String,
    default: 'orange',
  },
  trend: {
    type: Number,
    default: null,
  },
  clickable: {
    type: Boolean,
    default: false
  }
});

defineEmits(['click']);

const iconBgClass = computed(() => {
  const colors = {
    orange: 'bg-gradient-to-br from-moov-orange to-moov-orange-dark',
    yellow: 'bg-gradient-to-br from-yellow-400 to-yellow-600',
    green: 'bg-gradient-to-br from-green-400 to-green-600',
    red: 'bg-gradient-to-br from-red-400 to-red-600',
    blue: 'bg-gradient-to-br from-blue-400 to-blue-600',
  };
  return colors[props.color] || colors.orange;
});
</script>
