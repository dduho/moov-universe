<template>
  <div class="animate-pulse" :class="containerClass">
    <!-- Text skeleton -->
    <template v-if="type === 'text'">
      <div 
        v-for="i in lines" 
        :key="i" 
        class="skeleton skeleton-text"
        :style="{ width: i === lines ? '60%' : '100%' }"
      ></div>
    </template>
    
    <!-- Title skeleton -->
    <template v-else-if="type === 'title'">
      <div class="skeleton skeleton-title"></div>
    </template>
    
    <!-- Avatar skeleton -->
    <template v-else-if="type === 'avatar'">
      <div 
        class="skeleton rounded-full"
        :style="{ width: size, height: size }"
      ></div>
    </template>
    
    <!-- Card skeleton -->
    <template v-else-if="type === 'card'">
      <div class="skeleton skeleton-card" :style="{ height }"></div>
    </template>
    
    <!-- Image skeleton -->
    <template v-else-if="type === 'image'">
      <div 
        class="skeleton flex items-center justify-center"
        :style="{ width, height, borderRadius: rounded ? '0.75rem' : '0' }"
      >
        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
    </template>
    
    <!-- Button skeleton -->
    <template v-else-if="type === 'button'">
      <div 
        class="skeleton rounded-xl"
        :style="{ width: width || '8rem', height: height || '2.5rem' }"
      ></div>
    </template>
    
    <!-- Input skeleton -->
    <template v-else-if="type === 'input'">
      <div class="space-y-2">
        <div class="skeleton h-4 w-24 rounded"></div>
        <div class="skeleton h-12 w-full rounded-xl"></div>
      </div>
    </template>
    
    <!-- List item skeleton -->
    <template v-else-if="type === 'list-item'">
      <div class="flex items-center gap-4 p-4">
        <div class="skeleton rounded-full" style="width: 3rem; height: 3rem;"></div>
        <div class="flex-1 space-y-2">
          <div class="skeleton h-4 w-3/4 rounded"></div>
          <div class="skeleton h-3 w-1/2 rounded"></div>
        </div>
      </div>
    </template>
    
    <!-- Table row skeleton -->
    <template v-else-if="type === 'table-row'">
      <div class="flex items-center gap-4 p-4 border-b border-gray-100">
        <div v-for="col in columns" :key="col" class="skeleton h-4 rounded" :style="{ width: `${100/columns}%` }"></div>
      </div>
    </template>
    
    <!-- Stats card skeleton -->
    <template v-else-if="type === 'stats'">
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="skeleton h-4 w-24 rounded mb-3"></div>
            <div class="skeleton h-8 w-16 rounded"></div>
          </div>
          <div class="skeleton w-12 h-12 rounded-xl"></div>
        </div>
      </div>
    </template>
    
    <!-- Custom/default skeleton -->
    <template v-else>
      <div class="skeleton" :style="{ width, height }"></div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'text',
    validator: (value) => [
      'text', 'title', 'avatar', 'card', 'image', 
      'button', 'input', 'list-item', 'table-row', 'stats', 'custom'
    ].includes(value)
  },
  lines: {
    type: Number,
    default: 3
  },
  size: {
    type: String,
    default: '3rem'
  },
  width: {
    type: String,
    default: '100%'
  },
  height: {
    type: String,
    default: '8rem'
  },
  columns: {
    type: Number,
    default: 4
  },
  rounded: {
    type: Boolean,
    default: true
  },
  containerClass: {
    type: String,
    default: ''
  }
});
</script>


