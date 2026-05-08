<script setup>
import { ref, computed, onBeforeUnmount, nextTick, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options:    { type: Array,   required: true },
    optionValue:{ type: String,  default: 'value' },
    optionLabel:{ type: String,  default: 'label' },
    placeholder:{ type: String,  default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    invalid:    { type: Boolean, default: false },
    disabled:   { type: Boolean, default: false },
    loading:    { type: Boolean, default: false },
    maxResults: { type: Number,  default: 100 },
});
const emit = defineEmits(['update:modelValue']);

const open      = ref(false);
const search    = ref('');
const root      = ref(null);
const menuRef   = ref(null);
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
    return props.options.filter(o =>
        String(o[props.optionLabel]).toLowerCase().includes(q) ||
        String(o[props.optionValue]).toLowerCase().includes(q)
    );
});
const filtered = computed(() => filteredAll.value.slice(0, props.maxResults));
const overflowCount = computed(() => Math.max(0, filteredAll.value.length - filtered.value.length));

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
        await nextTick();
        calcMenuPosition();
    }
}

function pick(o) {
    emit('update:modelValue', o[props.optionValue]);
    open.value = false;
}

function clear(e) {
    e.stopPropagation();
    emit('update:modelValue', null);
    open.value = false;
}

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
            :class="{ 'is-invalid': invalid }"
            :title="selectedLabel"
            :disabled="disabled" @click="toggle">
            <span class="ss-label" :class="{ 'text-muted': !selected }">
                <template v-if="loading">Memuat...</template>
                <template v-else-if="selected">{{ selectedLabel }}</template>
                <template v-else>{{ placeholder }}</template>
            </span>
            <span class="ss-icons">
                <i v-if="selected && !disabled" class="bx bx-x ss-clear" @click="clear"></i>
                <i class="mdi mdi-chevron-down"></i>
            </span>
        </button>

        <Teleport to="body">
            <div v-if="open" ref="menuRef" class="ss-menu shadow rounded border bg-white" :style="menuStyle">
                <div class="p-2 border-bottom">
                    <input v-model="search" type="text" class="form-control form-control-sm"
                        :placeholder="searchPlaceholder" autofocus
                        @keydown.esc.stop.prevent="open = false">
                </div>
                <ul class="list-unstyled m-0 ss-list">
                    <li v-for="o in filtered" :key="o[optionValue]"
                        class="ss-item"
                        :class="{ active: o[optionValue] === modelValue }"
                        :title="o[optionLabel]"
                        @click="pick(o)">
                        {{ o[optionLabel] }}
                    </li>
                    <li v-if="!filtered.length" class="text-center text-muted py-3 small">Tidak ada hasil</li>
                    <li v-else-if="overflowCount > 0" class="text-center text-muted py-2 small ss-overflow">
                        +{{ overflowCount.toLocaleString('id-ID') }} lainnya — ketik untuk mempersempit
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
}
.ss-btn:disabled { cursor: not-allowed; background-color: #eff2f7; }

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
</style>

<style>
/* Global (tidak scoped) supaya class menu di body tetap ter-style. */
.ss-menu {
    z-index: 1080;
    max-height: 320px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.ss-list {
    overflow-y: auto;
    flex: 1 1 auto;
    max-height: 260px;
}
.ss-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: .875rem;
    line-height: 1.35;
    white-space: normal;
    word-break: break-word;
}
.ss-item:hover { background: #f5f7fb; }
.ss-item.active { background: #556ee6; color: #fff; }
.ss-overflow { border-top: 1px solid #eef0f3; background: #fafbfc; font-style: italic; }
</style>
