import { ref, computed, watch } from 'vue';

export function useAutocomplete(options = {}) {
  const {
    getSuggestions = () => [],
    minChars = 1,
    debounceMs = 300
  } = options;

  const inputValue = ref('');
  const suggestions = ref([]);
  const showSuggestions = ref(false);
  const selectedIndex = ref(-1);
  const isLoading = ref(false);

  let debounceTimeout = null;

  const filteredSuggestions = computed(() => suggestions.value);

  const updateSuggestions = async (query) => {
    if (!query || query.length < minChars) {
      suggestions.value = [];
      showSuggestions.value = false;
      return;
    }

    isLoading.value = true;
    try {
      const results = await getSuggestions(query);
      suggestions.value = results;
      showSuggestions.value = results.length > 0;
      selectedIndex.value = -1;
    } catch (error) {
      console.error('Error getting suggestions:', error);
      suggestions.value = [];
    } finally {
      isLoading.value = false;
    }
  };

  const debouncedUpdate = (query) => {
    if (debounceTimeout) {
      clearTimeout(debounceTimeout);
    }
    debounceTimeout = setTimeout(() => {
      updateSuggestions(query);
    }, debounceMs);
  };

  const selectSuggestion = (suggestion) => {
    inputValue.value = suggestion;
    showSuggestions.value = false;
    selectedIndex.value = -1;
  };

  const handleKeyDown = (event) => {
    if (!showSuggestions.value || suggestions.value.length === 0) return;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        selectedIndex.value = Math.min(
          selectedIndex.value + 1,
          suggestions.value.length - 1
        );
        break;
      case 'ArrowUp':
        event.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, -1);
        break;
      case 'Enter':
        event.preventDefault();
        if (selectedIndex.value >= 0) {
          selectSuggestion(suggestions.value[selectedIndex.value]);
        }
        break;
      case 'Escape':
        showSuggestions.value = false;
        selectedIndex.value = -1;
        break;
    }
  };

  const handleFocus = () => {
    if (inputValue.value && suggestions.value.length > 0) {
      showSuggestions.value = true;
    }
  };

  const handleBlur = () => {
    // Délai pour permettre le clic sur une suggestion
    setTimeout(() => {
      showSuggestions.value = false;
      selectedIndex.value = -1;
    }, 200);
  };

  return {
    inputValue,
    suggestions: filteredSuggestions,
    showSuggestions,
    selectedIndex,
    isLoading,
    debouncedUpdate,
    selectSuggestion,
    handleKeyDown,
    handleFocus,
    handleBlur
  };
}
