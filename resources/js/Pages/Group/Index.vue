<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MasterCrud from '@/Components/MasterCrud.vue';

const props = defineProps({
    groups:  { type: Object, required: true },
    filters: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });
</script>

<template>
    <MasterCrud
        title="Group"
        base-url="/group"
        page-prop="groups"
        :items="groups"
        :filters="filters"
        field-kode="kode_group"
        field-nama="nama_group"
    />
</template>
