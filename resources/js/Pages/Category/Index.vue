<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MasterCrud from '@/Components/MasterCrud.vue';

const props = defineProps({
    categories: { type: Object, required: true },
    filters:    { type: Object, required: true },
});

defineOptions({ layout: AppLayout });
</script>

<template>
    <MasterCrud
        title="Kategori"
        base-url="/category"
        page-prop="categories"
        :items="categories"
        :filters="filters"
        field-kode="kode_category"
        field-nama="nama_category"
    />
</template>
