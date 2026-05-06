<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const cards = [
    { key: 'barang',   label: 'Total Barang',   icon: 'bx-package',     color: 'primary' },
    { key: 'category', label: 'Total Kategori', icon: 'bx-category',    color: 'success' },
    { key: 'merk',     label: 'Total Merk',     icon: 'bx-purchase-tag',color: 'warning' },
    { key: 'group',    label: 'Total Group',    icon: 'bx-collection',  color: 'info' },
];
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3" v-for="c in cards" :key="c.key">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">{{ c.label }}</p>
                            <h4 class="mb-0">{{ (stats[c.key] ?? 0).toLocaleString('id-ID') }}</h4>
                        </div>
                        <div :class="`avatar-sm rounded-circle bg-soft-${c.color}`">
                            <span :class="`avatar-title rounded-circle bg-soft-${c.color} text-${c.color} font-size-22`">
                                <i :class="`bx ${c.icon}`"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
