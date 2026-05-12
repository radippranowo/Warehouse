<script setup>
import { ref, onMounted } from 'vue';

const toasts = ref([]);
let id = 0;

function push(type, message) {
    const t = { id: ++id, type, message };
    toasts.value.push(t);
    setTimeout(() => {
        toasts.value = toasts.value.filter(x => x.id !== t.id);
    }, 2200);
}

const confirmState = ref({ open: false, title: '', text: '', okText: 'Ya', okClass: 'btn-danger', resolve: null });

function ask({ title, text, okText = 'Ya, hapus', okClass = 'btn-danger' }) {
    return new Promise((resolve) => {
        confirmState.value = { open: true, title, text, okText, okClass, resolve };
    });
}

function answer(ok) {
    const r = confirmState.value.resolve;
    confirmState.value = { open: false, title: '', text: '', okText: 'Ya', okClass: 'btn-danger', resolve: null };
    if (r) r(ok);
}

onMounted(() => {
    window.toast = {
        success: (m) => push('success', m),
        error:   (m) => push('error',   m),
        info:    (m) => push('info',    m),
        warning: (m) => push('info',    m),
    };
    window.confirmDialog = ask;
});
</script>

<template>
    <div class="toast-host">
        <transition-group name="toast" tag="div" class="toast-stack">
            <div v-for="t in toasts" :key="t.id" class="app-toast" :class="`app-toast-${t.type}`">
                <i v-if="t.type==='success'" class="bx bx-check-circle"></i>
                <i v-else-if="t.type==='error'" class="bx bx-x-circle"></i>
                <i v-else class="bx bx-info-circle"></i>
                <span>{{ t.message }}</span>
            </div>
        </transition-group>

        <div v-if="confirmState.open" class="confirm-overlay" @click.self="answer(false)">
            <div class="confirm-box">
                <div class="confirm-title">{{ confirmState.title }}</div>
                <div class="confirm-text" v-if="confirmState.text">{{ confirmState.text }}</div>
                <div class="confirm-actions">
                    <button class="btn btn-light btn-rounded" @click="answer(false)">Batal</button>
                    <button class="btn btn-rounded" :class="confirmState.okClass" @click="answer(true)">{{ confirmState.okText }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.toast-host .toast-stack {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none;
}
.app-toast {
    pointer-events: auto;
    min-width: 240px;
    max-width: 360px;
    padding: 10px 14px;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: .9rem;
    border-left: 4px solid #adb5bd;
}
.app-toast i { font-size: 1.2rem; }
.app-toast-success { border-left-color: #34c38f; color: #1f7a55; }
.app-toast-error   { border-left-color: #f46a6a; color: #a93c3c; }
.app-toast-info    { border-left-color: #50a5f1; color: #2a6fb0; }

.toast-enter-from { opacity: 0; transform: translateX(20px); }
.toast-leave-to   { opacity: 0; transform: translateX(20px); }
.toast-enter-active, .toast-leave-active { transition: all 180ms ease; }

.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    backdrop-filter: blur(2px);
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
}
.confirm-box {
    background: #fff;
    border-radius: 10px;
    padding: 22px 24px;
    min-width: 320px;
    max-width: 90vw;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
.confirm-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 6px; }
.confirm-text  { font-size: .9rem; color: #6c757d; margin-bottom: 16px; }
.confirm-actions { display: flex; justify-content: flex-end; gap: 8px; }
</style>
