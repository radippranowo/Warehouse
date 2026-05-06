<script setup>
import { watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: '' }, // '', 'modal-lg', 'modal-xl'
});
const emit = defineEmits(['close']);

function close() { emit('close'); }

function onKey(e) { if (e.key === 'Escape' && props.show) close(); }

watch(() => props.show, (val) => {
    if (typeof window === 'undefined') return;
    document.body.style.overflow = val ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="modal fade show d-block modal-blur"
            tabindex="-1"
            style="z-index: 1060;"
            @click.self="close"
        >
            <div class="modal-dialog modal-dialog-centered" :class="size">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mb-0">{{ title }}</h5>
                        <button type="button" class="btn-close" @click="close"></button>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </Teleport>
</template>
