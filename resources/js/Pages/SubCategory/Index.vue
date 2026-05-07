<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import MasterCrud from '@/Components/MasterCrud.vue';

const props = defineProps({
    subCategories: { type: Object, required: true },
    filters:       { type: Object, required: true },
});

defineOptions({ layout: AppLayout });
</script>

<template>
    <MasterCrud
        title="Sub Kategori"
        base-url="/sub-category"
        page-prop="subCategories"
        :items="subCategories"
        :filters="filters"
        field-kode="kode_sub_category"
        field-nama="nama_sub_category"
    />
</template>
