<template>
  <div class="relative overflow-hidden" :class="containerClass" :style="containerStyle">
    <!-- Skeleton placeholder -->
    <div 
      v-if="!isLoaded" 
      class="absolute inset-0 skeleton flex items-center justify-center"
      :class="skeletonClass"
    >
      <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
    </div>
    
    <!-- Actual image -->
    <img
      ref="imgRef"
      :src="isIntersecting ? src : placeholder"
      :alt="alt"
      :class="[imageClass, { 'opacity-0': !isLoaded, 'opacity-100': isLoaded }]"
      class="transition-opacity duration-300"
      @load="handleLoad"
      @error="handleError"
      loading="lazy"
    />
    
    <!-- Pinch to zoom overlay (mobile) -->
    <div 
      v-if="zoomable && isLoaded" 
      class="absolute inset-0 cursor-zoom-in pinch-zoom"
      @click="openFullscreen"
    ></div>
  </div>
  
  <!-- Fullscreen viewer -->
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
        v-if="isFullscreen" 
        class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
        @click="closeFullscreen"
      >
        <button 
          class="absolute top-4 right-4 p-3 text-white hover:text-gray-300 transition-colors z-10"
          @click.stop="closeFullscreen"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        
        <img 
          :src="src" 
          :alt="alt"
          class="max-w-full max-h-full object-contain pinch-zoom"
          @click.stop
        />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' // 1x1 transparent gif
  },
  containerClass: {
    type: String,
    default: ''
  },
  containerStyle: {
    type: Object,
    default: () => ({})
  },
  imageClass: {
    type: String,
    default: 'w-full h-full object-cover'
  },
  skeletonClass: {
    type: String,
    default: ''
  },
  zoomable: {
    type: Boolean,
    default: false
  },
  rootMargin: {
    type: String,
    default: '100px'
  }
});

const emit = defineEmits(['load', 'error']);

const imgRef = ref(null);
const isIntersecting = ref(false);
const isLoaded = ref(false);
const isFullscreen = ref(false);

let observer = null;

const handleLoad = () => {
  isLoaded.value = true;
  emit('load');
};

const handleError = (e) => {
  emit('error', e);
};

const openFullscreen = () => {
  if (props.zoomable) {
    isFullscreen.value = true;
    // Haptic feedback
    if ('vibrate' in navigator) {
      navigator.vibrate(30);
    }
  }
};

const closeFullscreen = () => {
  isFullscreen.value = false;
};

onMounted(() => {
  if ('IntersectionObserver' in window && imgRef.value) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            isIntersecting.value = true;
            observer?.disconnect();
          }
        });
      },
      {
        rootMargin: props.rootMargin,
        threshold: 0
      }
    );

    observer.observe(imgRef.value.parentElement);
  } else {
    // Fallback
    isIntersecting.value = true;
  }
});

onUnmounted(() => {
  observer?.disconnect();
});
</script>


