<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MasterCrud from '@/Components/MasterCrud.vue';

const props = defineProps({
    merks:   { type: Object, required: true },
    filters: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });
</script>

<template>
    <MasterCrud
        title="Merk"
        base-url="/merk"
        page-prop="merks"
        :items="merks"
        :filters="filters"
        field-kode="kode_merk"
        field-nama="nama_merk"
    />
</template>
