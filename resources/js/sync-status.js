/**
 * Global sync indicator state.
 *
 * Dipakai untuk kasih feedback ke user kalau request lambat / nyangkut /
 * gagal — penting di lokasi dgn jaringan susah, supaya user tau status
 * sebenarnya bukan cuma hasil optimistic UI.
 *
 * Hook ke Inertia router events (start/finish/success/exception). Semua
 * router.get/post/put/delete otomatis ke-track tanpa perlu modifikasi
 * controller / komponen.
 */
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const STUCK_AFTER_MS = 8000;   // request dianggap lemot setelah 8 detik
const FRESH_OK_MS    = 2500;   // tampilkan checkmark hijau 2.5 detik setelah sukses

const state = reactive({
    pending: 0,           // jumlah request yang sedang berjalan
    stuck: false,         // ada request yg > STUCK_AFTER_MS
    lastSuccessAt: 0,
    lastErrorAt: 0,
    lastErrorKind: null,  // 'validation' | 'network' | 'server'
});

let stuckTimer = null;

router.on('start', () => {
    state.pending++;
    state.stuck = false;
    clearTimeout(stuckTimer);
    stuckTimer = setTimeout(() => {
        if (state.pending > 0) state.stuck = true;
    }, STUCK_AFTER_MS);
});

router.on('finish', () => {
    state.pending = Math.max(0, state.pending - 1);
    if (state.pending === 0) {
        clearTimeout(stuckTimer);
        state.stuck = false;
    }
});

router.on('success', () => {
    state.lastSuccessAt = Date.now();
    state.lastErrorAt = 0;
    state.lastErrorKind = null;
});

router.on('error', () => {
    // Inertia event 'error' = validation errors (422). Bukan masalah jaringan.
    state.lastErrorAt = Date.now();
    state.lastErrorKind = 'validation';
});

router.on('exception', () => {
    // Network drop / server crash / non-Inertia response.
    state.lastErrorAt = Date.now();
    state.lastErrorKind = 'network';
    window.toast?.error('Koneksi bermasalah — coba lagi atau refresh.');
});

router.on('invalid', () => {
    state.lastErrorAt = Date.now();
    state.lastErrorKind = 'server';
});

export const syncStatus = state;
export const SYNC_FRESH_OK_MS = FRESH_OK_MS;
