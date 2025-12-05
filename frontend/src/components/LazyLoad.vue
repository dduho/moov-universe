<template>
  <div ref="targetRef" :class="rootClass">
    <slot v-if="isVisible" />
    <slot v-else name="placeholder">
      <div class="skeleton" :class="placeholderClass" :style="placeholderStyle"></div>
    </slot>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  rootClass: {
    type: String,
    default: ''
  },
  placeholderClass: {
    type: String,
    default: 'skeleton-card'
  },
  placeholderStyle: {
    type: Object,
    default: () => ({})
  },
  rootMargin: {
    type: String,
    default: '100px'
  },
  threshold: {
    type: Number,
    default: 0
  }
});

const targetRef = ref(null);
const isVisible = ref(false);
let observer = null;

onMounted(() => {
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isVisible.value = true;
            observer?.disconnect();
          }
        });
      },
      {
        rootMargin: props.rootMargin,
        threshold: props.threshold
      }
    );

    if (targetRef.value) {
      observer.observe(targetRef.value);
    }
  } else {
    // Fallback for browsers without IntersectionObserver
    isVisible.value = true;
  }
});

onUnmounted(() => {
  observer?.disconnect();
});
</script>
