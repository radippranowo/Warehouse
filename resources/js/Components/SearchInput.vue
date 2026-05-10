<template>
    <div class="search-input-wrapper" ref="wrapperRef">
        <!-- Search Input -->
        <div class="position-relative">
            <input
                :id="id"
                ref="inputRef"
                type="text"
                :value="searchQuery"
                @input="handleInput"
                @keydown="handleKeydown"
                @focus="showResults = true"
                :placeholder="placeholder"
                :class="inputClass"
                :disabled="disabled"
                autocomplete="off"
                :aria-label="ariaLabel"
                :style="{ paddingRight: searchQuery ? '70px' : '40px' }"
            />
            
            <!-- Clear Button -->
            <button
                v-if="searchQuery"
                type="button"
                @click="clearSearch"
                class="btn-clear position-absolute"
                style="right: 38px; top: 50%; transform: translateY(-50%); border: none; background: none; padding: 2px 4px; cursor: pointer; z-index: 5;"
                title="Clear"
            >
                <i class="bx bx-x" style="font-size: 20px; color: #6c757d;"></i>
            </button>
            
            <!-- Search Icon / Loading -->
            <i v-if="!isSearching && !searchQuery" class="bx bx-search position-absolute" 
               style="right: 12px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 18px; pointer-events: none;"></i>
            <div v-else-if="isSearching" class="spinner-border spinner-border-sm position-absolute" 
                 style="right: 12px; top: 50%; transform: translateY(-50%); color: #0d6efd; width: 16px; height: 16px;" 
                 role="status">
                <span class="visually-hidden">Searching...</span>
            </div>
        </div>

        <!-- Results Dropdown -->
        <Teleport to="body">
            <div
                v-if="showResults && filteredOptions.length > 0"
                ref="dropdownRef"
                class="search-results-dropdown"
                :style="dropdownStyle"
            >
                <!-- Result Counter -->
                <div class="search-results-header">
                    <small class="text-muted">
                        <i class="bx bx-list-ul me-1"></i>
                        {{ filteredOptions.length }} hasil
                        <span v-if="filteredOptions.length >= maxResults" class="text-warning">
                            (menampilkan {{ maxResults }} teratas)
                        </span>
                    </small>
                </div>

                <!-- Results List -->
                <div class="search-results-list">
                    <div
                        v-for="(option, index) in filteredOptions"
                        :key="option.value"
                        @click="selectOption(option)"
                        @mouseenter="highlightedIndex = index"
                        class="search-result-item"
                        :class="{ 'highlighted': index === highlightedIndex }"
                    >
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="result-label" v-html="highlightText(option.label)"></div>
                                <small v-if="option.sublabel" class="result-sublabel text-muted">
                                    {{ typeof option.sublabel === 'function' ? option.sublabel() : option.sublabel }}
                                </small>
                            </div>
                            <i v-if="index === highlightedIndex" class="bx bx-chevron-right ms-2" style="font-size: 20px; color: #0d6efd;"></i>
                        </div>
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="searchQuery && filteredOptions.length === 0" class="search-no-results">
                    <i class="bx bx-search-alt text-muted" style="font-size: 32px;"></i>
                    <p class="mb-0 mt-2 text-muted">Tidak ada hasil untuk "{{ searchQuery }}"</p>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    id: { type: String, default: '' },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    placeholder: { type: String, default: 'Ketik untuk mencari...' },
    inputClass: { type: String, default: 'form-control' },
    disabled: { type: Boolean, default: false },
    ariaLabel: { type: String, default: 'Search input' },
    debounceMs: { type: Number, default: 150 }, // Debounce delay
    maxResults: { type: Number, default: 100 }, // Max results to show
});

const emit = defineEmits(['update:modelValue', 'change']);

const inputRef = ref(null);
const wrapperRef = ref(null);
const dropdownRef = ref(null);
const searchQuery = ref('');
const debouncedQuery = ref('');
const showResults = ref(false);
const highlightedIndex = ref(0);
const dropdownStyle = ref({});
const isSearching = ref(false);
let debounceTimer = null;

// Optimized filter with debouncing
const filteredOptions = computed(() => {
    const query = debouncedQuery.value.toLowerCase().trim();
    
    if (!query) {
        // Show first 30 when empty for better performance
        return props.options.slice(0, 30);
    }
    
    // Fast filtering with early exit
    const results = [];
    const maxResults = props.maxResults;
    
    for (let i = 0; i < props.options.length && results.length < maxResults; i++) {
        const option = props.options[i];
        const label = option.label.toLowerCase();
        const sublabel = option.sublabel ? 
            (typeof option.sublabel === 'function' ? option.sublabel() : option.sublabel).toLowerCase() : '';
        
        // Check if query matches (starts with has priority)
        if (label.startsWith(query) || sublabel.startsWith(query)) {
            results.unshift(option); // Priority results at top
        } else if (label.includes(query) || sublabel.includes(query)) {
            results.push(option);
        }
    }
    
    return results;
});

// Optimized highlight with memoization
const highlightCache = new Map();
function highlightText(text) {
    if (!debouncedQuery.value) return text;
    
    const cacheKey = `${text}_${debouncedQuery.value}`;
    if (highlightCache.has(cacheKey)) {
        return highlightCache.get(cacheKey);
    }
    
    const regex = new RegExp(`(${debouncedQuery.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    const result = text.replace(regex, '<mark class="search-highlight">$1</mark>');
    
    // Limit cache size
    if (highlightCache.size > 100) {
        const firstKey = highlightCache.keys().next().value;
        highlightCache.delete(firstKey);
    }
    
    highlightCache.set(cacheKey, result);
    return result;
}

// Debounced input handler
function handleInput(event) {
    searchQuery.value = event.target.value;
    showResults.value = true;
    highlightedIndex.value = 0;
    isSearching.value = true;
    
    // Clear previous timer
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    
    // Set new timer
    debounceTimer = setTimeout(() => {
        debouncedQuery.value = searchQuery.value;
        isSearching.value = false;
    }, props.debounceMs);
    
    // Clear selection if user is typing
    if (props.modelValue) {
        emit('update:modelValue', '');
    }
}

// Handle keyboard navigation
function handleKeydown(event) {
    if (!showResults.value || filteredOptions.value.length === 0) {
        if (event.key === 'ArrowDown') {
            showResults.value = true;
        }
        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            event.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            event.preventDefault();
            if (filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            event.preventDefault();
            showResults.value = false;
            inputRef.value?.blur();
            break;
        case 'Tab':
            showResults.value = false;
            break;
    }
}

// Select an option
function selectOption(option) {
    emit('update:modelValue', option.value);
    emit('change', option.value);
    searchQuery.value = option.label;
    debouncedQuery.value = option.label;
    showResults.value = false;
    highlightedIndex.value = 0;
    highlightCache.clear(); // Clear cache on selection
}

// Clear search
function clearSearch() {
    searchQuery.value = '';
    debouncedQuery.value = '';
    emit('update:modelValue', '');
    showResults.value = false;
    highlightCache.clear();
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    inputRef.value?.focus();
}

// Scroll to highlighted item
function scrollToHighlighted() {
    nextTick(() => {
        const dropdown = dropdownRef.value;
        if (!dropdown) return;

        const items = dropdown.querySelectorAll('.search-result-item');
        const highlighted = items[highlightedIndex.value];
        
        if (highlighted) {
            const dropdownRect = dropdown.getBoundingClientRect();
            const itemRect = highlighted.getBoundingClientRect();
            
            if (itemRect.bottom > dropdownRect.bottom) {
                highlighted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else if (itemRect.top < dropdownRect.top) {
                highlighted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    });
}

// Update dropdown position
function updateDropdownPosition() {
    if (!inputRef.value || !showResults.value) return;

    const inputRect = inputRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceBelow = viewportHeight - inputRect.bottom;
    const spaceAbove = inputRect.top;
    const dropdownMaxHeight = 400;

    // Position below or above based on available space
    if (spaceBelow >= dropdownMaxHeight || spaceBelow > spaceAbove) {
        dropdownStyle.value = {
            position: 'fixed',
            top: `${inputRect.bottom + 4}px`,
            left: `${inputRect.left}px`,
            width: `${inputRect.width}px`,
            maxHeight: `${Math.min(spaceBelow - 20, dropdownMaxHeight)}px`,
            zIndex: 9999,
        };
    } else {
        dropdownStyle.value = {
            position: 'fixed',
            bottom: `${viewportHeight - inputRect.top + 4}px`,
            left: `${inputRect.left}px`,
            width: `${inputRect.width}px`,
            maxHeight: `${Math.min(spaceAbove - 20, dropdownMaxHeight)}px`,
            zIndex: 9999,
        };
    }
}

// Close dropdown when clicking outside
function handleClickOutside(event) {
    if (wrapperRef.value && !wrapperRef.value.contains(event.target) &&
        dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showResults.value = false;
    }
}

// Watch for changes
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        const selected = props.options.find(opt => opt.value === newVal);
        if (selected) {
            searchQuery.value = selected.label;
            debouncedQuery.value = selected.label;
        }
    } else {
        searchQuery.value = '';
        debouncedQuery.value = '';
    }
});

watch(showResults, (show) => {
    if (show) {
        nextTick(() => {
            updateDropdownPosition();
        });
    }
});

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', updateDropdownPosition, true);
    window.addEventListener('resize', updateDropdownPosition);
    
    // Initialize search query from modelValue
    if (props.modelValue) {
        const selected = props.options.find(opt => opt.value === props.modelValue);
        if (selected) {
            searchQuery.value = selected.label;
            debouncedQuery.value = selected.label;
        }
    }
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', updateDropdownPosition, true);
    window.removeEventListener('resize', updateDropdownPosition);
    
    // Cleanup
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    highlightCache.clear();
});

// Expose focus method
defineExpose({
    focus: () => inputRef.value?.focus(),
});
</script>

<style scoped>
.search-input-wrapper {
    position: relative;
}

/* Override untuk membuat input kotak biasa seperti kolom filter (tanggal, gudang) */
/* Menghilangkan border-radius yang bulat */
.search-input-wrapper input.form-control {
    border-radius: 0.25rem !important; /* Kotak standar Bootstrap */
}

/* Pastikan tidak ada btn-rounded yang diterapkan */
.search-input-wrapper input.btn-rounded {
    border-radius: 0.25rem !important; /* Override btn-rounded jika ada */
}

.search-results-dropdown {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: slideDown 0.2s ease-out;
    display: flex;
    flex-direction: column;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-results-header {
    padding: 8px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.search-results-list {
    max-height: 280px;
    overflow-y: auto;
    overflow-x: hidden;
    flex-shrink: 0;
}

/* Custom Scrollbar */
.search-results-list::-webkit-scrollbar {
    width: 8px;
}

.search-results-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.search-results-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.search-results-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.search-result-item {
    padding: 12px 16px;
    cursor: pointer;
    transition: all 0.15s ease;
    border-bottom: 1px solid #f0f0f0;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover,
.search-result-item.highlighted {
    background: #e7f3ff;
    border-left: 3px solid #0d6efd;
    padding-left: 13px;
}

.result-label {
    font-weight: 500;
    color: #212529;
    margin-bottom: 2px;
}

.result-sublabel {
    font-size: 0.85rem;
    display: block;
}

.search-no-results {
    padding: 40px 20px;
    text-align: center;
}

.search-results-footer {
    padding: 8px 12px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: center;
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.search-results-footer small {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
}

.search-results-footer small > span {
    margin: 0 4px;
}

.search-results-footer kbd {
    padding: 3px 8px;
    font-size: 0.75rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    margin: 0 1px;
    font-family: monospace;
    display: inline-block;
}

:deep(.search-highlight) {
    background: #fff3cd;
    color: #856404;
    font-weight: 600;
    padding: 1px 2px;
    border-radius: 2px;
}

.btn-clear:hover i {
    color: #dc3545 !important;
}
</style>
