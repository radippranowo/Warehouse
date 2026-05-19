<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: [Number, String, null], default: 0 },
    /** Saat true: tampilkan inline (Rp prefix langsung di samping nilai, tanpa flex align). */
    inline: { type: Boolean, default: false },
    /** Override font-weight, mis. bold buat total. */
    bold: { type: Boolean, default: false },
});

const formatted = computed(() => {
    const n = Number(props.value);
    if (!isFinite(n) || n === 0) return '0';
    return n.toLocaleString('id-ID');
});
</script>

<template>
    <span :class="['rupiah-format', { 'rupiah-inline': inline, 'fw-bold': bold }]">
        <span class="rp">Rp</span>
        <span class="amount">{{ formatted }}</span>
    </span>
</template>
