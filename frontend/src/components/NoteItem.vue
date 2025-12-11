<template>
  <div 
    class="p-3 rounded-xl border transition-all duration-200"
    :class="note.is_pinned 
      ? 'bg-yellow-50 border-yellow-200' 
      : 'bg-gray-50 border-gray-200 hover:bg-gray-100'"
  >
    <div class="flex items-start gap-3">
      <!-- Avatar -->
      <div class="w-8 h-8 rounded-full bg-moov-orange/20 flex items-center justify-center flex-shrink-0">
        <span class="text-sm font-bold text-moov-orange">
          {{ note.user?.name?.charAt(0)?.toUpperCase() || '?' }}
        </span>
      </div>
      
      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <span class="font-semibold text-sm text-gray-900">{{ note.user?.name || 'Utilisateur' }}</span>
          <span class="text-xs text-gray-500">{{ formatDate(note.created_at) }}</span>
          <span v-if="note.is_pinned" class="text-xs">📌</span>
        </div>
        <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ note.content }}</p>
      </div>
      
      <!-- Actions -->
      <div v-if="canModify" class="flex items-center gap-1 flex-shrink-0">
        <button
          @click="$emit('toggle-pin', note)"
          class="w-7 h-7 rounded-lg hover:bg-gray-200 flex items-center justify-center transition-colors"
          :title="note.is_pinned ? 'Désépingler' : 'Épingler'"
        >
          <svg class="w-4 h-4" :class="note.is_pinned ? 'text-yellow-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
          </svg>
        </button>
        <button
          @click="$emit('edit', note)"
          class="w-7 h-7 rounded-lg hover:bg-gray-200 flex items-center justify-center transition-colors"
          title="Modifier"
        >
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
          </svg>
        </button>
        <button
          @click="$emit('delete', note)"
          class="w-7 h-7 rounded-lg hover:bg-red-100 flex items-center justify-center transition-colors"
          title="Supprimer"
        >
          <svg class="w-4 h-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  note: { type: Object, required: true },
  currentUserId: { type: Number, required: true },
  isAdmin: { type: Boolean, default: false },
});

defineEmits(['edit', 'delete', 'toggle-pin']);

const canModify = computed(() => {
  return props.note.user_id === props.currentUserId || props.isAdmin;
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  
  // Moins d'une minute
  if (diff < 60000) return 'À l\'instant';
  // Moins d'une heure
  if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
  // Moins de 24h
  if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)}h`;
  // Moins d'une semaine
  if (diff < 604800000) return `Il y a ${Math.floor(diff / 86400000)}j`;
  // Sinon date complète
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>


