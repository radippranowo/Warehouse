<script setup>
import { watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    masters: { type: Object, default: () => ({ categories: [], subCategories: [], merks: [], groups: [], gudangs: [] }) },
});

function emptyRow() {
    return {
        _key: Date.now() + Math.random(),
        kode_barang: '',
        part_number: '',
        nama_barang: '',
        category_code: '',
        sub_category_code: '',
        merk_code: '',
        group_code: '',
        satuan: 'pcs',
        harga_beli: 0,
        harga_jual: 0,
        min_stok: 0,
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
                router.flushAll();
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
            <div class="alert alert-info py-2 small mb-3">
                <i class="bx bx-info-circle me-1"></i>
                Stok awal diinput melalui modul <strong>Penerimaan Barang / Mutasi Stok</strong> setelah master barang tersimpan.
            </div>

            <form @submit.prevent="submit" autocomplete="off">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%;">Kode</th>
                                <th style="width: 11%;">Part Number</th>
                                <th style="width: 14%;">Nama</th>
                                <th style="width: 10%;">Kategori</th>
                                <th style="width: 10%;">Sub Kategori</th>
                                <th style="width: 9%;">Merk</th>
                                <th style="width: 9%;">Group</th>
                                <th style="width: 6%;">Satuan</th>
                                <th style="width: 9%;">Harga Beli</th>
                                <th style="width: 9%;">Harga Jual</th>
                                <th style="width: 5%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in form.items" :key="row._key">
                                <td class="position-relative">
                                    <input type="text" v-model="row.kode_barang"
                                        :id="`row_${idx}_kode_barang`"
                                        :name="`items[${idx}][kode_barang]`"
                                        autocomplete="off"
                                        aria-label="Kode barang"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'kode_barang') }">
                                    <div v-if="rowError(idx, 'kode_barang')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'kode_barang') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="text" v-model="row.part_number"
                                        :id="`row_${idx}_part_number`"
                                        :name="`items[${idx}][part_number]`"
                                        autocomplete="off"
                                        aria-label="Part number"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'part_number') }">
                                    <div v-if="rowError(idx, 'part_number')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'part_number') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="text" v-model="row.nama_barang"
                                        :id="`row_${idx}_nama_barang`"
                                        :name="`items[${idx}][nama_barang]`"
                                        autocomplete="off"
                                        aria-label="Nama barang"
                                        class="form-control form-control-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'nama_barang') }">
                                    <div v-if="rowError(idx, 'nama_barang')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'nama_barang') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <SearchSelect
                                        v-model="row.category_code"
                                        :options="props.masters.categories"
                                        option-value="kode_category"
                                        option-label="nama_category"
                                        placeholder="Pilih"
                                        search-placeholder="Cari kategori..."
                                        :invalid="!!rowError(idx, 'category_code')" />
                                    <div v-if="rowError(idx, 'category_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'category_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <SearchSelect
                                        v-model="row.sub_category_code"
                                        :options="props.masters.subCategories"
                                        option-value="kode_sub_category"
                                        option-label="nama_sub_category"
                                        placeholder="-"
                                        search-placeholder="Cari sub kategori..."
                                        :invalid="!!rowError(idx, 'sub_category_code')" />
                                    <div v-if="rowError(idx, 'sub_category_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'sub_category_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <SearchSelect
                                        v-model="row.merk_code"
                                        :options="props.masters.merks"
                                        option-value="kode_merk"
                                        option-label="nama_merk"
                                        placeholder="Pilih"
                                        search-placeholder="Cari merk..."
                                        :invalid="!!rowError(idx, 'merk_code')" />
                                    <div v-if="rowError(idx, 'merk_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'merk_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <SearchSelect
                                        v-model="row.group_code"
                                        :options="props.masters.groups"
                                        option-value="kode_group"
                                        option-label="nama_group"
                                        placeholder="Pilih"
                                        search-placeholder="Cari group..."
                                        :invalid="!!rowError(idx, 'group_code')" />
                                    <div v-if="rowError(idx, 'group_code')" class="invalid-feedback-absolute">
                                        {{ rowError(idx, 'group_code') }}
                                    </div>
                                </td>
                                <td class="position-relative">
                                    <input type="text" v-model="row.satuan"
                                        :id="`row_${idx}_satuan`"
                                        :name="`items[${idx}][satuan]`"
                                        autocomplete="off"
                                        aria-label="Satuan"
                                        class="form-control form-control-sm text-center">
                                </td>
                                <td class="position-relative">
                                    <input type="number" v-model.number="row.harga_beli"
                                        :id="`row_${idx}_harga_beli`"
                                        :name="`items[${idx}][harga_beli]`"
                                        autocomplete="off"
                                        aria-label="Harga beli"
                                        class="form-control form-control-sm text-center">
                                </td>
                                <td class="position-relative">
                                    <input type="number" v-model.number="row.harga_jual"
                                        :id="`row_${idx}_harga_jual`"
                                        :name="`items[${idx}][harga_jual]`"
                                        autocomplete="off"
                                        aria-label="Harga jual"
                                        class="form-control form-control-sm text-center">
                                </td>
                                <td class="position-relative text-center">
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
