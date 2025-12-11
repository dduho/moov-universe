<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
    <!-- Header avec toggle -->
    <div 
      class="flex items-center justify-between cursor-pointer"
      @click="isExpanded = !isExpanded"
    >
      <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
        <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
        </svg>
        Notes
        <span 
          v-if="notes.length > 0" 
          class="ml-2 px-2 py-0.5 text-xs font-bold rounded-full bg-moov-orange/20 text-moov-orange"
        >
          {{ totalNotes }}
        </span>
      </h3>
      <button class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all duration-200">
        <svg 
          class="w-5 h-5 text-gray-600 transition-transform duration-200"
          :class="{ 'rotate-180': isExpanded }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
    </div>

    <!-- Contenu collapsible -->
    <Transition name="collapse">
      <div v-show="isExpanded" class="mt-4">
        <!-- Formulaire d'ajout -->
        <div class="mb-4">
          <div class="flex gap-2">
            <textarea
              ref="noteTextarea"
              v-model="newNoteContent"
              :disabled="submitting"
              placeholder="Ajouter une note..."
              rows="2"
              class="flex-1 px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all duration-200 resize-none text-sm"
              @keydown="handleKeydown"
            ></textarea>
            <button
              @click="submitNote"
              :disabled="!newNoteContent.trim() || submitting"
              class="px-4 py-2 rounded-xl bg-moov-orange text-white font-bold hover:shadow-lg hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 self-end"
            >
              <span v-if="submitting" class="animate-spin inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
              </svg>
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">Ctrl+Entrée pour envoyer</p>
        </div>

        <!-- Loader -->
        <div v-if="loading" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-moov-orange"></div>
        </div>

        <!-- Liste des notes -->
        <div v-else-if="notes.length > 0" class="space-y-3">
          <!-- Notes épinglées -->
          <div v-if="pinnedNotes.length > 0" class="mb-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">📌 Épinglées</p>
            <TransitionGroup name="note-list" tag="div" class="space-y-2">
              <NoteItem
                v-for="note in pinnedNotes"
                :key="note.id"
                :note="note"
                :current-user-id="currentUserId"
                :is-admin="isAdmin"
                @edit="editNote"
                @delete="deleteNote"
                @toggle-pin="togglePin"
              />
            </TransitionGroup>
          </div>

          <!-- Notes récentes (afficher max 3 par défaut) -->
          <div>
            <p v-if="pinnedNotes.length > 0" class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Récentes</p>
            <TransitionGroup name="note-list" tag="div" class="space-y-2">
              <NoteItem
                v-for="note in displayedNotes"
                :key="note.id"
                :note="note"
                :current-user-id="currentUserId"
                :is-admin="isAdmin"
                @edit="editNote"
                @delete="deleteNote"
                @toggle-pin="togglePin"
              />
            </TransitionGroup>
          </div>

          <!-- Bouton "Voir plus" -->
          <div v-if="hasMoreNotes" class="pt-2">
            <button
              @click="showAllNotes = !showAllNotes"
              class="w-full py-2 text-sm font-semibold text-moov-orange hover:bg-moov-orange/10 rounded-lg transition-colors"
            >
              {{ showAllNotes ? 'Voir moins' : `Voir ${regularNotes.length - maxDisplayed} notes de plus...` }}
            </button>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 pt-3 border-t border-gray-200">
            <button
              @click="loadPage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              ←
            </button>
            <span class="text-sm text-gray-600">
              Page {{ currentPage }} / {{ totalPages }}
            </span>
            <button
              @click="loadPage(currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="px-3 py-1 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              →
            </button>
          </div>
        </div>

        <!-- Aucune note -->
        <div v-else class="text-center py-6 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
          </svg>
          <p class="text-sm">Aucune note pour ce PDV</p>
        </div>
      </div>
    </Transition>

    <!-- Modal d'édition -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="editingNote" 
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @click.self="cancelEdit"
        >
          <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Modifier la note</h3>
            <textarea
              v-model="editContent"
              rows="4"
              class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all duration-200 resize-none"
            ></textarea>
            <div class="flex justify-end gap-3 mt-4">
              <button
                @click="cancelEdit"
                class="px-4 py-2 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-colors"
              >
                Annuler
              </button>
              <button
                @click="saveEdit"
                :disabled="!editContent.trim() || submitting"
                class="px-4 py-2 rounded-xl bg-moov-orange text-white font-bold hover:shadow-lg transition-all disabled:opacity-50"
              >
                {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import NoteItem from './NoteItem.vue';
import NoteService from '../services/NoteService';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';

const props = defineProps({
  pointOfSaleId: { type: [Number, String], required: true },
  currentUserId: { type: Number, required: true },
  isAdmin: { type: Boolean, default: false },
});

const { toast } = useToast();
const { confirm } = useConfirm();

const noteTextarea = ref(null);
const loading = ref(true);
const submitting = ref(false);
const notes = ref([]);
const newNoteContent = ref('');
const isExpanded = ref(true);
const showAllNotes = ref(false);
const maxDisplayed = 3;
const currentPage = ref(1);
const totalPages = ref(1);
const totalNotes = ref(0);

// Editing
const editingNote = ref(null);
const editContent = ref('');

// Computed
const pinnedNotes = computed(() => notes.value.filter(n => n.is_pinned));
const regularNotes = computed(() => notes.value.filter(n => !n.is_pinned));
const displayedNotes = computed(() => {
  if (showAllNotes.value) return regularNotes.value;
  return regularNotes.value.slice(0, maxDisplayed);
});
const hasMoreNotes = computed(() => regularNotes.value.length > maxDisplayed);

// Methods
const loadNotes = async (page = 1) => {
  loading.value = true;
  try {
    const response = await NoteService.getNotes(props.pointOfSaleId, page, 20);
    notes.value = response.data || [];
    currentPage.value = response.current_page || 1;
    totalPages.value = response.last_page || 1;
    totalNotes.value = response.total || 0;
  } catch (err) {
    console.error('Error loading notes:', err);
    toast.error('Erreur lors du chargement des notes');
  } finally {
    loading.value = false;
  }
};

const loadPage = (page) => {
  if (page < 1 || page > totalPages.value) return;
  loadNotes(page);
};

// Gérer le raccourci Ctrl+Entrée
const handleKeydown = (event) => {
  if (event.ctrlKey && event.key === 'Enter') {
    event.preventDefault();
    submitNote();
  }
};

const submitNote = async () => {
  if (!newNoteContent.value.trim() || submitting.value) return;
  
  submitting.value = true;
  try {
    const note = await NoteService.createNote(props.pointOfSaleId, {
      content: newNoteContent.value.trim()
    });
    notes.value.unshift(note);
    totalNotes.value++;
    newNoteContent.value = '';
    toast.success('Note ajoutée');
  } catch (err) {
    console.error('Error creating note:', err);
    toast.error('Erreur lors de l\'ajout de la note');
  } finally {
    submitting.value = false;
  }
};

const editNote = (note) => {
  editingNote.value = note;
  editContent.value = note.content;
};

const cancelEdit = () => {
  editingNote.value = null;
  editContent.value = '';
};

const saveEdit = async () => {
  if (!editContent.value.trim() || submitting.value) return;
  
  submitting.value = true;
  try {
    const updated = await NoteService.updateNote(props.pointOfSaleId, editingNote.value.id, {
      content: editContent.value.trim()
    });
    const index = notes.value.findIndex(n => n.id === editingNote.value.id);
    if (index !== -1) {
      notes.value[index] = updated;
    }
    cancelEdit();
    toast.success('Note modifiée');
  } catch (err) {
    console.error('Error updating note:', err);
    toast.error('Erreur lors de la modification');
  } finally {
    submitting.value = false;
  }
};

const deleteNote = async (note) => {
  const confirmed = await confirm({
    title: 'Supprimer la note',
    message: 'Êtes-vous sûr de vouloir supprimer cette note ?',
    confirmText: 'Supprimer',
    type: 'danger'
  });
  if (!confirmed) return;
  
  try {
    await NoteService.deleteNote(props.pointOfSaleId, note.id);
    notes.value = notes.value.filter(n => n.id !== note.id);
    totalNotes.value--;
    toast.success('Note supprimée');
  } catch (err) {
    console.error('Error deleting note:', err);
    toast.error('Erreur lors de la suppression');
  }
};

const togglePin = async (note) => {
  try {
    const updated = await NoteService.togglePin(props.pointOfSaleId, note.id);
    const index = notes.value.findIndex(n => n.id === note.id);
    if (index !== -1) {
      notes.value[index] = updated;
    }
    // Re-trier les notes
    notes.value.sort((a, b) => {
      if (a.is_pinned && !b.is_pinned) return -1;
      if (!a.is_pinned && b.is_pinned) return 1;
      return new Date(b.created_at) - new Date(a.created_at);
    });
  } catch (err) {
    console.error('Error toggling pin:', err);
    toast.error('Erreur lors de l\'épinglage');
  }
};

// Lifecycle
onMounted(() => {
  loadNotes();
});

// Recharger si le PDV change
watch(() => props.pointOfSaleId, () => {
  loadNotes();
});
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
  max-height: 0;
}

.collapse-enter-to,
.collapse-leave-from {
  opacity: 1;
  max-height: 1000px;
}

.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Animation pour l'ajout/suppression de notes */
.note-list-move,
.note-list-enter-active,
.note-list-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.note-list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.note-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.note-list-leave-active {
  position: absolute;
  width: 100%;
}
</style>


