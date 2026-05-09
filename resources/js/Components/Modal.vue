<script setup>
import { watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: '' }, // '', 'modal-lg', 'modal-xl'
    maxWidth: { type: String, default: '' }, // 'sm', 'md', 'lg', 'xl'
});
const emit = defineEmits(['close']);

function close() { emit('close'); }

function onKey(e) { if (e.key === 'Escape' && props.show) close(); }

const sizeClass = props.maxWidth ? `modal-${props.maxWidth}` : props.size;

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
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" :class="sizeClass">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mb-0">
                            <slot name="title">{{ title }}</slot>
                        </h5>
                        <button type="button" class="btn-close" @click="close"></button>
                    </div>
                    <div class="modal-body">
                        <slot />
                    </div>
                    <div v-if="$slots.footer" class="modal-footer">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.modal-dialog-scrollable {
    max-height: calc(100vh - 3.5rem);
}

.modal-dialog-scrollable .modal-content {
    max-height: calc(100vh - 3.5rem);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-dialog-scrollable .modal-body {
    overflow-y: auto;
    flex: 1 1 auto;
}

.modal-footer {
    flex-shrink: 0;
}
</style>
