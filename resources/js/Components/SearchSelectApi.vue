<script setup>
import { ref, computed, onBeforeUnmount, nextTick, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    apiEndpoint: { type: String, default: '/api/v1/barang/search' },
    placeholder: { type: String, default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Ketik untuk mencari...' },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    minChars: { type: Number, default: 2 },
    debounceMs: { type: Number, default: 300 },
    id: { type: String, default: null },
});
const emit = defineEmits(['update:modelValue', 'selected']);

const open = ref(false);
const search = ref('');
const root = ref(null);
const menuRef = ref(null);
const searchInput = ref(null);
const highlightedIndex = ref(0);
const menuStyle = ref({ position: 'fixed', top: '0px', left: '0px', width: '0px' });

// API state
const options = ref([]);
const loading = ref(false);
const selectedItem = ref(null);
let searchTimeout = null;

// Selected label untuk display
const selectedLabel = computed(() => {
    if (selectedItem.value) {
        return selectedItem.value.label || selectedItem.value.nama || '';
    }
    return '';
});

// Filtered options (dari hasil API)
const filtered = computed(() => options.value);

// Debounced search via API
watch(search, (newVal) => {
    if (newVal.length < props.minChars) {
        options.value = [];
        return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        await fetchOptions(newVal);
    }, props.debounceMs);
});

// Fetch options dari API
async function fetchOptions(query) {
    loading.value = true;
    try {
        const url = `${props.apiEndpoint}?q=${encodeURIComponent(query)}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && Array.isArray(data.data)) {
            options.value = data.data;
        } else {
            options.value = [];
        }
        
        highlightedIndex.value = 0;
    } catch (error) {
        console.error('Failed to fetch options:', error);
        options.value = [];
        window.toast?.error('Gagal memuat data. Silakan coba lagi.');
    } finally {
        loading.value = false;
    }
}

// Load initial selected item jika ada modelValue
watch(() => props.modelValue, async (newVal) => {
    if (newVal && !selectedItem.value) {
        // Load detail barang untuk display
        try {
            const response = await fetch(`/api/v1/barang/${newVal}`);
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    selectedItem.value = {
                        id: data.data.id,
                        label: `${data.data.kode_barang} - ${data.data.nama_barang}`,
                        kode: data.data.kode_barang,
                        nama: data.data.nama_barang,
                        satuan: data.data.satuan,
                        harga: data.data.harga_jual,
                    };
                }
            }
        } catch (error) {
            console.error('Failed to load selected item:', error);
        }
    }
}, { immediate: true });

function calcMenuPosition() {
    if (!root.value) return;
    const rect = root.value.getBoundingClientRect();
    const vh = window.innerHeight;
    const spaceBelow = vh - rect.bottom;
    const menuMaxH = 320;
    const openUp = spaceBelow < menuMaxH && rect.top > spaceBelow;

    menuStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${Math.max(rect.width, 240)}px`,
        ...(openUp
            ? { bottom: `${vh - rect.top + 4}px`, top: 'auto' }
            : { top: `${rect.bottom + 4}px`, bottom: 'auto' }),
    };
}

async function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        options.value = [];
        highlightedIndex.value = 0;
        await nextTick();
        calcMenuPosition();
        if (searchInput.value) {
            searchInput.value.focus();
        }
    }
}

function selectOption(option) {
    selectedItem.value = option;
    emit('update:modelValue', option.id);
    emit('selected', option);
    open.value = false;
    search.value = '';
}

function clear() {
    selectedItem.value = null;
    emit('update:modelValue', null);
    emit('selected', null);
    search.value = '';
    options.value = [];
}

function handleKeydown(e) {
    if (!open.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            toggle();
        }
        return;
    }

    switch (e.key) {
        case 'Escape':
            e.preventDefault();
            open.value = false;
            break;
        case 'ArrowDown':
            e.preventDefault();
            if (highlightedIndex.value < filtered.value.length - 1) {
                highlightedIndex.value++;
                scrollToHighlighted();
            }
            break;
        case 'ArrowUp':
            e.preventDefault();
            if (highlightedIndex.value > 0) {
                highlightedIndex.value--;
                scrollToHighlighted();
            }
            break;
        case 'Enter':
            e.preventDefault();
            if (filtered.value[highlightedIndex.value]) {
                selectOption(filtered.value[highlightedIndex.value]);
            }
            break;
        case 'Tab':
            open.value = false;
            break;
    }
}

function scrollToHighlighted() {
    nextTick(() => {
        const menu = menuRef.value;
        const highlighted = menu?.querySelector('.ss-option.highlighted');
        if (highlighted && menu) {
            const menuRect = menu.getBoundingClientRect();
            const itemRect = highlighted.getBoundingClientRect();
            if (itemRect.bottom > menuRect.bottom) {
                highlighted.scrollIntoView({ block: 'nearest' });
            } else if (itemRect.top < menuRect.top) {
                highlighted.scrollIntoView({ block: 'nearest' });
            }
        }
    });
}

function handleClickOutside(e) {
    if (root.value && !root.value.contains(e.target) && menuRef.value && !menuRef.value.contains(e.target)) {
        open.value = false;
    }
}

// Lifecycle
if (typeof window !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', calcMenuPosition);
    window.addEventListener('scroll', calcMenuPosition, true);
}

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        document.removeEventListener('click', handleClickOutside);
        window.removeEventListener('resize', calcMenuPosition);
        window.removeEventListener('scroll', calcMenuPosition, true);
    }
    clearTimeout(searchTimeout);
});
</script>

<template>
    <div ref="root" class="search-select-api" :class="{ 'is-invalid': invalid, 'is-disabled': disabled }">
        <!-- Toggle Button -->
        <button
            type="button"
            class="ss-toggle form-control d-flex align-items-center justify-content-between"
            :class="{ 'is-invalid': invalid }"
            :disabled="disabled"
            :aria-expanded="open"
            :aria-haspopup="true"
            :id="id"
            @click="toggle"
            @keydown="handleKeydown"
        >
            <span class="ss-label" :class="{ 'text-muted': !selectedLabel }">
                {{ selectedLabel || placeholder }}
            </span>
            <div class="ss-icons d-flex align-items-center gap-1">
                <i
                    v-if="selectedLabel && !disabled"
                    class="bx bx-x ss-clear"
                    @click.stop="clear"
                    title="Clear"
                ></i>
                <i class="bx bx-chevron-down ss-chevron" :class="{ 'rotate': open }"></i>
            </div>
        </button>

        <!-- Dropdown Menu (Portal) -->
        <Teleport to="body">
            <div
                v-show="open"
                ref="menuRef"
                class="ss-menu"
                :style="menuStyle"
                role="listbox"
            >
                <!-- Search Input -->
                <div class="ss-search-wrapper">
                    <i class="bx bx-search ss-search-icon"></i>
                    <input
                        ref="searchInput"
                        v-model="search"
                        type="text"
                        class="ss-search-input"
                        :placeholder="searchPlaceholder"
                        @keydown="handleKeydown"
                    />
                    <i v-if="loading" class="bx bx-loader-alt bx-spin ss-loading-icon"></i>
                </div>

                <!-- Options List -->
                <div class="ss-options">
                    <!-- Loading State -->
                    <div v-if="loading" class="ss-empty">
                        <i class="bx bx-loader-alt bx-spin"></i>
                        <span>Memuat data...</span>
                    </div>

                    <!-- Empty State - Belum Ketik -->
                    <div v-else-if="search.length < minChars" class="ss-empty">
                        <i class="bx bx-search"></i>
                        <span>Ketik minimal {{ minChars }} karakter untuk mencari</span>
                    </div>

                    <!-- Empty State - Tidak Ada Hasil -->
                    <div v-else-if="filtered.length === 0" class="ss-empty">
                        <i class="bx bx-info-circle"></i>
                        <span>Tidak ada hasil untuk "{{ search }}"</span>
                    </div>

                    <!-- Options -->
                    <button
                        v-for="(option, idx) in filtered"
                        :key="option.id"
                        type="button"
                        class="ss-option"
                        :class="{ 
                            'highlighted': idx === highlightedIndex,
                            'selected': option.id === modelValue 
                        }"
                        role="option"
                        :aria-selected="option.id === modelValue"
                        @click="selectOption(option)"
                        @mouseenter="highlightedIndex = idx"
                    >
                        <div class="ss-option-content">
                            <div class="ss-option-label">{{ option.label || option.nama }}</div>
                            <div v-if="option.kode" class="ss-option-sublabel">
                                Kode: {{ option.kode }}
                                <span v-if="option.satuan"> • {{ option.satuan }}</span>
                                <span v-if="option.harga" class="text-success"> • Rp {{ option.harga.toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                        <i v-if="option.id === modelValue" class="bx bx-check ss-check"></i>
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.search-select-api {
    position: relative;
    width: 100%;
}

.ss-toggle {
    width: 100%;
    text-align: left;
    padding: 0.375rem 0.75rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}

.ss-toggle:hover:not(:disabled) {
    border-color: #86b7fe;
}

.ss-toggle:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.ss-toggle:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.6;
}

.ss-toggle.is-invalid {
    border-color: #dc3545;
}

.ss-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ss-icons {
    flex-shrink: 0;
    margin-left: 0.5rem;
}

.ss-clear {
    font-size: 1.25rem;
    color: #6c757d;
    cursor: pointer;
    transition: color 0.15s;
}

.ss-clear:hover {
    color: #dc3545;
}

.ss-chevron {
    font-size: 1.25rem;
    color: #6c757d;
    transition: transform 0.2s;
}

.ss-chevron.rotate {
    transform: rotate(180deg);
}

.ss-menu {
    z-index: 9999;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 320px;
    display: flex;
    flex-direction: column;
}

.ss-search-wrapper {
    position: relative;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    flex-shrink: 0;
}

.ss-search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.125rem;
    pointer-events: none;
}

.ss-loading-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #0d6efd;
    font-size: 1.125rem;
}

.ss-search-input {
    width: 100%;
    padding: 0.375rem 2.5rem 0.375rem 2.25rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    transition: border-color 0.15s;
}

.ss-search-input:focus {
    outline: none;
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.ss-options {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

.ss-option {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: none;
    background: white;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.ss-option:hover,
.ss-option.highlighted {
    background-color: #f8f9fa;
}

.ss-option.selected {
    background-color: #e7f1ff;
}

.ss-option-content {
    flex: 1;
    min-width: 0;
}

.ss-option-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #212529;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ss-option-sublabel {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.125rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ss-check {
    font-size: 1.25rem;
    color: #0d6efd;
    flex-shrink: 0;
}

.ss-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: #6c757d;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.ss-empty i {
    font-size: 2rem;
    opacity: 0.5;
}

.ss-empty span {
    font-size: 0.875rem;
}

/* Scrollbar styling */
.ss-options::-webkit-scrollbar {
    width: 8px;
}

.ss-options::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.ss-options::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.ss-options::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
