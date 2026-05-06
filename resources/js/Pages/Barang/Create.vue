<script setup>
import { watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    masters: { type: Object, default: () => ({ categories: [], merks: [], groups: [] }) },
});

function emptyRow() {
    return {
        _key: Date.now() + Math.random(),
        kode_barang: '',
        part_number: '',
        nama_barang: '',
        category_code: '',
        merk_code: '',
        group_code: '',
        stok: 0,
        harga: 0,
    };
}

const form = useForm({ items: [emptyRow()] });

function addRow() {
    form.items.push(emptyRow());
}

function removeRow(idx) {
    if (form.items.length > 1) form.items.splice(idx, 1);
}

function rowError(idx, field) {
    return form.errors[`items.${idx}.${field}`];
}

let validateTimer = null;
let validateController = null;

function liveValidate() {
    clearTimeout(validateTimer);
    validateTimer = setTimeout(async () => {
        if (validateController) validateController.abort();
        validateController = new AbortController();

        const items = form.items.map(({ _key, ...rest }) => rest);
        try {
            const { data } = await axios.post('/barang/validate',
                { items },
                { signal: validateController.signal }
            );
            const flat = {};
            for (const key in data.errors) flat[key] = data.errors[key][0];
            form.clearErrors();
            form.setError(flat);
        } catch (e) {
            if (axios.isCancel(e)) return;
        }
    }, 400);
}

watch(() => form.items, liveValidate, { deep: true });

function submit() {
    const count = form.items.length;
    form
        .transform((data) => ({
            items: data.items.map(({ _key, ...rest }) => rest),
        }))
        .post('/barang', {
            preserveScroll: true,
            preserveState: (page) => Object.keys(page.props.errors ?? {}).length > 0,
            onSuccess: () => {
                router.flushAll();    // invalidate cache /barang & master agar fresh
                window.toast?.success(`${count} barang ditambah`);
            },
            onError:   () => window.toast?.error('Gagal Simpan'),
        });
}
</script>

<template>
    <div class="card shadow-sm">
        <div class="card-body border-bottom d-flex justify-content-between">
            <h5 class="mb-0">Barang</h5>

            <Link href="/barang" class="btn btn-primary btn-rounded waves-effect waves-light mb-2">
                <i class="mdi mdi-arrow-left me-1"></i>Kembali
            </Link>
        </div>

        <div class="card-body">
            <form @submit.prevent="submit" autocomplete="off">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 10%;">Kode</th>
                                <th style="width: 15%;">Part Number</th>
                                <th style="width: 15%;">Nama</th>
                                <th style="width: 15%;">Kategori</th>
                                <th style="width: 15%;">Merk</th>
                                <th style="width: 15%;">Group</th>
                                <th style="width: 8%;">Stok</th>
                                <th style="width: 15%;">Harga</th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in form.items" :key="row._key">
                                <td class="position-relative">
                                    <input type="text" v-model="row.kode_barang"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'kode_barang') }">
                                    <div v-if="rowError(idx, 'kode_barang')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'kode_barang') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="text" v-model="row.part_number"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'part_number') }">
                                    <div v-if="rowError(idx, 'part_number')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'part_number') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="text" v-model="row.nama_barang"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'nama_barang') }">
                                    <div v-if="rowError(idx, 'nama_barang')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'nama_barang') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <select v-model="row.category_code"
                                        class="form-select form-select-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'category_code') }">
                                        <option value="">Pilih</option>
                                        <option v-for="cat in props.masters.categories" :key="cat.kode_category"
                                            :value="cat.kode_category">{{ cat.nama_category }}</option>
                                    </select>
                                    <div v-if="rowError(idx, 'category_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'category_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <select v-model="row.merk_code"
                                        class="form-select form-select-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'merk_code') }">
                                        <option value="">Pilih</option>
                                        <option v-for="m in props.masters.merks" :key="m.kode_merk"
                                            :value="m.kode_merk">{{ m.nama_merk }}</option>
                                    </select>
                                    <div v-if="rowError(idx, 'merk_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'merk_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <select v-model="row.group_code"
                                        class="form-select form-select-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'group_code') }">
                                        <option value="">Pilih</option>
                                        <option v-for="g in props.masters.groups" :key="g.kode_group"
                                            :value="g.kode_group">{{ g.nama_group }}</option>
                                    </select>
                                    <div v-if="rowError(idx, 'group_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'group_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="number" v-model.number="row.stok"
                                        class="form-control form-control-sm text-center">
                                </td>
                                <td class="position-relative">
                                    <input type="number" v-model.number="row.harga"
                                        class="form-control form-control-sm text-center">
                                </td>
                                <td class="position-relative">
                                    <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                        class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16">
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-between">
                    <button type="button" @click="addRow"
                        class="btn btn-success btn-rounded waves-effect waves-light mb-2">
                        <i class="bx bx-plus label-icon"></i> Baris
                    </button>

                    <button type="submit" class="btn btn-success btn-rounded waves-effect waves-light mb-2"
                        :disabled="form.processing">
                        <i class="bx bx-save label-icon"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
td.position-relative {
    padding-bottom: 18px !important;
}

.invalid-feedback-absolute {
    position: absolute;
    bottom: 2px;
    left: 8px;
    font-size: 10px;
    color: red;
}

.is-invalid {
    border-color: red !important;
}
</style>
