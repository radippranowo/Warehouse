<script setup>
import { ref, computed, onBeforeUnmount, nextTick, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options:    { type: Array,   required: true },
    optionValue:{ type: String,  default: 'value' },
    optionLabel:{ type: String,  default: 'label' },
    optionSublabel: { type: [String, Function], default: null }, // NEW: sublabel support
    placeholder:{ type: String,  default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    invalid:    { type: Boolean, default: false },
    disabled:   { type: Boolean, default: false },
    loading:    { type: Boolean, default: false },
    maxResults: { type: Number,  default: 100 },
    id:         { type: String,  default: null }, // NEW: for accessibility
});
const emit = defineEmits(['update:modelValue']);

const open      = ref(false);
const search    = ref('');
const root      = ref(null);
const menuRef   = ref(null);
const searchInput = ref(null); // NEW: for focus management
const highlightedIndex = ref(0); // NEW: for keyboard navigation
const menuStyle = ref({ position: 'fixed', top: '0px', left: '0px', width: '0px' });

const selected = computed(() =>
    props.options.find(o => o[props.optionValue] === props.modelValue)
);
const selectedLabel = computed(() =>
    selected.value ? selected.value[props.optionLabel] : ''
);

// Cap render size — kalau options ribuan, render semua <li> bikin dropdown lag.
// User wajib ketik buat narrow down kalau di atas batas.
const filteredAll = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter(o => {
        const label = String(o[props.optionLabel]).toLowerCase();
        const value = String(o[props.optionValue]).toLowerCase();
        const sublabel = props.optionSublabel 
            ? String(getOptionSublabel(o)).toLowerCase() 
            : '';
        return label.includes(q) || value.includes(q) || sublabel.includes(q);
    });
});
const filtered = computed(() => filteredAll.value.slice(0, props.maxResults));
const overflowCount = computed(() => Math.max(0, filteredAll.value.length - filtered.value.length));

// NEW: Get sublabel for option
function getOptionSublabel(option) {
    if (!props.optionSublabel) return '';
    if (typeof props.optionSublabel === 'function') {
        return props.optionSublabel(option);
    }
    return option[props.optionSublabel] || '';
}

// NEW: Highlight search term in text
function highlightText(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark class="ss-highlight">$1</mark>');
}

function calcMenuPosition() {
    if (!root.value) return;
    const rect = root.value.getBoundingClientRect();
    const vh   = window.innerHeight;
    const spaceBelow = vh - rect.bottom;
    const menuMaxH   = 320;
    const openUp     = spaceBelow < menuMaxH && rect.top > spaceBelow;

    menuStyle.value = {
        position: 'fixed',
        left:  `${rect.left}px`,
        width: `${Math.max(rect.width, 240)}px`,
        ...(openUp
            ? { bottom: `${vh - rect.top + 4}px`, top: 'auto' }
            : { top:    `${rect.bottom + 4}px`,   bottom: 'auto' }),
    };
}

async function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) {
        search.value = '';
        highlightedIndex.value = 0;
        await nextTick();
        calcMenuPosition();
        // Auto-focus search input
        if (searchInput.value) {
            searchInput.value.focus();
        }
    }
}

function pick(o) {
    emit('update:modelValue', o[props.optionValue]);
    open.value = false;
    search.value = '';
    highlightedIndex.value = 0;
}

function clear(e) {
    e.stopPropagation();
    emit('update:modelValue', null);
    open.value = false;
    search.value = '';
}

// NEW: Keyboard navigation
function onKeyDown(e) {
    if (!open.value) return;
    
    switch(e.key) {
        case 'ArrowDown':
            e.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, filtered.value.length - 1);
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            e.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            e.preventDefault();
            if (filtered.value[highlightedIndex.value]) {
                pick(filtered.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            e.preventDefault();
            open.value = false;
            break;
        case 'Tab':
            // Allow tab to close and move to next field
            open.value = false;
            break;
    }
}

// NEW: Scroll highlighted item into view
function scrollToHighlighted() {
    nextTick(() => {
        const list = menuRef.value?.querySelector('.ss-list');
        const item = list?.querySelector(`.ss-item:nth-child(${highlightedIndex.value + 1})`);
        if (item && list) {
            const itemTop = item.offsetTop;
            const itemBottom = itemTop + item.offsetHeight;
            const listScrollTop = list.scrollTop;
            const listHeight = list.clientHeight;
            
            if (itemBottom > listScrollTop + listHeight) {
                list.scrollTop = itemBottom - listHeight;
            } else if (itemTop < listScrollTop) {
                list.scrollTop = itemTop;
            }
        }
    });
}

// NEW: Reset highlight when search changes
watch(search, () => {
    highlightedIndex.value = 0;
});

function onDocMouseDown(e) {
    if (!open.value) return;
    const inRoot = root.value && root.value.contains(e.target);
    const inMenu = menuRef.value && menuRef.value.contains(e.target);
    if (!inRoot && !inMenu) open.value = false;
}

function onWinResize() { if (open.value) calcMenuPosition(); }
function onWinScroll() { if (open.value) calcMenuPosition(); }

document.addEventListener('mousedown', onDocMouseDown);
window.addEventListener('resize', onWinResize);
window.addEventListener('scroll', onWinScroll, true);

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocMouseDown);
    window.removeEventListener('resize', onWinResize);
    window.removeEventListener('scroll', onWinScroll, true);
});

// Tutup dropdown kalau options/modelValue berubah secara eksternal (mis. reset form)
watch(() => props.modelValue, () => { /* no-op, biar tidak menutup saat user pilih */ });
</script>

<template>
    <div ref="root" class="ss-root" :class="{ 'ss-disabled': disabled }">
        <button type="button"
            class="form-control ss-btn"
            :class="{ 'is-invalid': invalid, 'ss-open': open }"
            :title="selectedLabel"
            :disabled="disabled" 
            :aria-label="placeholder"
            :aria-expanded="open"
            @click="toggle"
            @keydown="onKeyDown">
            <span class="ss-label" :class="{ 'text-muted': !selected }">
                <template v-if="loading">Memuat...</template>
                <template v-else-if="selected">{{ selectedLabel }}</template>
                <template v-else>{{ placeholder }}</template>
            </span>
            <span class="ss-icons">
                <i v-if="selected && !disabled" class="bx bx-x ss-clear" @click="clear" title="Hapus pilihan"></i>
                <i class="mdi mdi-chevron-down" :class="{ 'ss-chevron-up': open }"></i>
            </span>
        </button>

        <Teleport to="body">
            <div v-if="open" ref="menuRef" class="ss-menu shadow rounded border bg-white" :style="menuStyle">
                <div class="p-2 border-bottom ss-search-wrapper">
                    <div class="position-relative">
                        <i class="bx bx-search ss-search-icon"></i>
                        <input 
                            ref="searchInput"
                            v-model="search" 
                            type="text" 
                            class="form-control form-control-sm ss-search-input"
                            :placeholder="searchPlaceholder" 
                            @keydown="onKeyDown">
                        <small v-if="filtered.length > 0" class="ss-result-count">
                            {{ filtered.length }} hasil
                        </small>
                    </div>
                </div>
                <ul class="list-unstyled m-0 ss-list">
                    <li v-for="(o, idx) in filtered" :key="o[optionValue]"
                        class="ss-item"
                        :class="{ 
                            'active': o[optionValue] === modelValue,
                            'highlighted': idx === highlightedIndex 
                        }"
                        :title="o[optionLabel]"
                        @click="pick(o)"
                        @mouseenter="highlightedIndex = idx">
                        <div class="ss-item-label" v-html="highlightText(o[optionLabel], search)"></div>
                        <div v-if="optionSublabel" class="ss-item-sublabel" v-html="highlightText(getOptionSublabel(o), search)"></div>
                    </li>
                    <li v-if="!filtered.length" class="text-center text-muted py-3 small">
                        <i class="bx bx-search-alt-2 me-1"></i>Tidak ada hasil untuk "{{ search }}"
                    </li>
                    <li v-else-if="overflowCount > 0" class="text-center text-muted py-2 small ss-overflow">
                        <i class="bx bx-info-circle me-1"></i>+{{ overflowCount.toLocaleString('id-ID') }} lainnya — ketik untuk mempersempit
                    </li>
                </ul>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.ss-root { width: 100%; position: relative; }

/* Tombol harus 100% form-control look + truncate label */
.ss-btn {
    width: 100%;
    display: flex;
    align-items: center;
    text-align: left;
    cursor: pointer;
    padding-right: .5rem;
    background-image: none !important; /* Hilangkan tanda seru Bootstrap */
}
.ss-btn:disabled { cursor: not-allowed; background-color: #eff2f7; }
.ss-btn.ss-open { border-color: #556ee6; box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.15); }

.ss-label {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ss-icons {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    gap: .25rem;
}
.ss-clear {
    cursor: pointer;
    color: #adb5bd;
    font-size: 1.1rem;
    line-height: 1;
}
.ss-clear:hover { color: #f46a6a; }
.ss-chevron-up { transform: rotate(180deg); transition: transform 0.2s; }
</style>

<style>
/* Global (tidak scoped) supaya class menu di body tetap ter-style. */
.ss-menu {
    z-index: 1080;
    max-height: 360px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideDown 0.15s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.ss-search-wrapper {
    background: #f8f9fa;
}

.ss-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #adb5bd;
    font-size: 1rem;
    pointer-events: none;
}

.ss-search-input {
    padding-left: 32px !important;
    padding-right: 60px !important;
}

.ss-result-count {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #74788d;
    font-size: 0.75rem;
    background: white;
    padding: 2px 6px;
    border-radius: 10px;
    pointer-events: none;
}

.ss-list {
    overflow-y: auto;
    flex: 1 1 auto;
    max-height: 280px;
}
.ss-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: .875rem;
    line-height: 1.4;
    transition: all 0.1s ease;
    border-left: 3px solid transparent;
}
.ss-item:hover, .ss-item.highlighted { 
    background: #f5f7fb; 
    border-left-color: #556ee6;
}
.ss-item.active { 
    background: #556ee6; 
    color: #fff; 
    border-left-color: #3b4ec4;
}
.ss-item.active:hover { 
    background: #4a5fc7; 
}

.ss-item-label {
    font-weight: 500;
    margin-bottom: 2px;
}

.ss-item-sublabel {
    font-size: 0.75rem;
    color: #74788d;
    margin-top: 2px;
}

.ss-item.active .ss-item-sublabel {
    color: rgba(255, 255, 255, 0.8);
}

.ss-overflow { 
    border-top: 1px solid #eef0f3; 
    background: #fafbfc; 
    font-style: italic; 
    padding: 8px 12px;
}

/* Highlight search term */
.ss-highlight {
    background-color: #fff3cd;
    color: #856404;
    font-weight: 600;
    padding: 1px 2px;
    border-radius: 2px;
}

.ss-item.active .ss-highlight {
    background-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

/* Smooth scrollbar */
.ss-list::-webkit-scrollbar {
    width: 6px;
}

.ss-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.ss-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.ss-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
