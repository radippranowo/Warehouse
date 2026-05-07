<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue';
import { syncStatus, SYNC_FRESH_OK_MS } from '@/sync-status.js';

// Tick setiap 500ms supaya `lastSuccessAt`/`lastErrorAt` re-evaluasi dgn benar
// (computed yg bergantung pada Date.now() butuh trigger reactive eksternal).
const now = ref(Date.now());
let tick = null;
onMounted(() => { tick = setInterval(() => { now.value = Date.now(); }, 500); });
onBeforeUnmount(() => clearInterval(tick));

const display = computed(() => {
    if (syncStatus.stuck) {
        return { color: 'warning', icon: 'bx-loader-alt bx-spin', label: 'Jaringan lambat...', show: true };
    }
    if (syncStatus.pending > 0) {
        return { color: 'info', icon: 'bx-loader-alt bx-spin', label: 'Menyimpan...', show: true };
    }
    if (syncStatus.lastErrorAt && (now.value - syncStatus.lastErrorAt) < 4000) {
        const txt = syncStatus.lastErrorKind === 'network'
            ? 'Koneksi bermasalah'
            : syncStatus.lastErrorKind === 'validation'
                ? 'Cek form'
                : 'Gagal sync';
        return { color: 'danger', icon: 'bx-error-circle', label: txt, show: true };
    }
    if (syncStatus.lastSuccessAt && (now.value - syncStatus.lastSuccessAt) < SYNC_FRESH_OK_MS) {
        return { color: 'success', icon: 'bx-check', label: 'Tersimpan', show: true };
    }
    return { color: 'success', icon: 'bx-check', label: '', show: false };
});
</script>

<template>
    <Transition name="sync-fade">
        <div
            v-if="display.show"
            class="sync-indicator"
            :class="`sync-${display.color}`"
            :title="display.label"
        >
            <i class="bx" :class="display.icon"></i>
            <span class="sync-label">{{ display.label }}</span>
        </div>
    </Transition>
</template>

<style scoped>
.sync-indicator {
    position: fixed;
    bottom: 16px;
    right: 16px;
    z-index: 1080;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(6px);
    pointer-events: none;
}
.sync-indicator i { font-size: 16px; line-height: 1; }
.sync-info    { background: rgba(80, 165, 241, 0.95); color: #fff; }
.sync-success { background: rgba(52, 195, 143, 0.95); color: #fff; }
.sync-warning { background: rgba(241, 180, 76, 0.95);  color: #fff; }
.sync-danger  { background: rgba(244, 106, 106, 0.95); color: #fff; }

.sync-fade-enter-active, .sync-fade-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}
.sync-fade-enter-from, .sync-fade-leave-to {
    opacity: 0;
    transform: translateY(8px);
}
</style>
