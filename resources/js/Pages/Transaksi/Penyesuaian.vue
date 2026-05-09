<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

const props = defineProps({
    barangs: { type: Array, required: true },
    gudangs: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

function emptyRow() {
    return {
        _key: Date.now() + Math.random(),
        barang_id: '',
        qty: 0,
        harga_satuan: 0,
        keterangan: '',
    };
}

const form = useForm({
    tipe: 'adjust',
    gudang_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    referensi: '',
    keterangan: '',
    items: [emptyRow()],
});

function addRow() {
    form.items.push(emptyRow());
}

function removeRow(idx) {
    if (form.items.length > 1) form.items.splice(idx, 1);
}

function rowError(idx, field) {
    return form.errors?.[`items.${idx}.${field}`];
}

function getBarangInfo(id) {
    const b = props.barangs.find(x => x.id === parseInt(id));
    return b ? { kode: b.kode_barang, nama: b.nama_barang, satuan: b.satuan } : null;
}

function submit() {
    if (!form.gudang_id) {
        window.toast?.error('Pilih gudang');
        return;
    }
    if (form.items.length === 0) {
        window.toast?.error('Tambahkan minimal 1 barang');
        return;
    }

    form
        .transform((data) => ({
            ...data,
            items: data.items.map(({ _key, ...rest }) => rest),
        }))
        .post('/mutasi', {
            preserveScroll: true,
            onSuccess: () => {
                window.toast?.success('Penyesuaian stok berhasil disimpan');
                router.visit('/riwayat/penyesuaian');
            },
            onError: () => {
                window.toast?.error('Gagal menyimpan penyesuaian');
            },
        });
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">
                            <i class="mdi mdi-tune text-info me-2"></i>
                            PENYESUAIAN STOK
                        </h5>
                        <div class="flex-shrink-0">
                            <a href="/riwayat/penyesuaian" class="btn btn-secondary btn-rounded">
                                <i class="mdi mdi-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form @submit.prevent="submit" autocomplete="off">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="gudang_id_penyesuaian" class="form-label">Gudang <span class="text-danger">*</span></label>
                                <select id="gudang_id_penyesuaian" name="gudang_id" v-model="form.gudang_id" class="form-select" :class="{ 'is-invalid': form.errors?.gudang_id }" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                        {{ g.kode_gudang }} - {{ g.nama_gudang }}
                                    </option>
                                </select>
                                <div v-if="form.errors?.gudang_id" class="invalid-feedback">{{ form.errors.gudang_id }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="tanggal_penyesuaian" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input id="tanggal_penyesuaian" name="tanggal" v-model="form.tanggal" type="date" class="form-control" :class="{ 'is-invalid': form.errors?.tanggal }" required>
                                <div v-if="form.errors?.tanggal" class="invalid-feedback">{{ form.errors.tanggal }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="referensi_penyesuaian" class="form-label">Referensi</label>
                                <input id="referensi_penyesuaian" name="referensi" v-model="form.referensi" type="text" class="form-control" placeholder="No. Dokumen">
                            </div>
                            <div class="col-md-3">
                                <label for="keterangan_penyesuaian" class="form-label">Keterangan</label>
                                <input id="keterangan_penyesuaian" name="keterangan" v-model="form.keterangan" type="text" class="form-control" placeholder="Alasan penyesuaian">
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">Daftar Barang</h6>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;" class="text-center">No</th>
                                        <th style="width: 10%;">Kode Barang</th>
                                        <th style="width: 25%;">Nama Barang</th>
                                        <th style="width: 8%;">Satuan</th>
                                        <th style="width: 10%;">Stok Baru</th>
                                        <th style="width: 12%;">Harga Satuan</th>
                                        <th style="width: 12%;">Total Nilai</th>
                                        <th style="width: 15%;">Keterangan</th>
                                        <th style="width: 3%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in form.items" :key="row._key">
                                        <td class="text-center">{{ idx + 1 }}</td>
                                        <td class="position-relative">
                                            <SearchSelect
                                                v-model="row.barang_id"
                                                :options="barangs"
                                                option-value="id"
                                                option-label="kode_barang"
                                                :option-sublabel="(b) => b.nama_barang"
                                                placeholder="Pilih"
                                                search-placeholder="Cari barang..."
                                                :invalid="!!rowError(idx, 'barang_id')" />
                                            <div v-if="rowError(idx, 'barang_id')" class="invalid-feedback-absolute">
                                                {{ rowError(idx, 'barang_id') }}
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ getBarangInfo(row.barang_id)?.nama || '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ getBarangInfo(row.barang_id)?.satuan || '-' }}</small>
                                        </td>
                                        <td class="position-relative">
                                            <input :id="`qty_penyesuaian_${idx}`" :name="`items[${idx}][qty]`" v-model.number="row.qty" type="number" step="0.01"
                                                class="form-control form-control-sm text-end"
                                                :class="{ 'is-invalid': rowError(idx, 'qty') }"
                                                placeholder="Stok setelah penyesuaian"
                                                :aria-label="`Stok baru barang baris ${idx + 1}`">
                                            <div v-if="rowError(idx, 'qty')" class="invalid-feedback-absolute">
                                                {{ rowError(idx, 'qty') }}
                                            </div>
                                        </td>
                                        <td class="position-relative">
                                            <input :id="`harga_satuan_penyesuaian_${idx}`" :name="`items[${idx}][harga_satuan]`" v-model.number="row.harga_satuan" type="number" step="0.01" min="0"
                                                class="form-control form-control-sm text-end"
                                                :aria-label="`Harga satuan barang baris ${idx + 1}`">
                                        </td>
                                        <td class="text-end">
                                            <small class="text-muted">{{ (row.qty * row.harga_satuan).toLocaleString('id-ID') }}</small>
                                        </td>
                                        <td class="position-relative">
                                            <input :id="`keterangan_item_penyesuaian_${idx}`" :name="`items[${idx}][keterangan]`" v-model="row.keterangan" type="text" class="form-control form-control-sm" placeholder="Alasan penyesuaian" :aria-label="`Keterangan barang baris ${idx + 1}`">
                                        </td>
                                        <td class="text-center position-relative">
                                            <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                                class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16">
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="mdi mdi-information me-2"></i>
                            <strong>Perhatian:</strong> Penyesuaian stok akan mengubah jumlah stok menjadi nilai yang Anda masukkan. 
                            Pastikan nilai yang dimasukkan sudah benar sesuai dengan hasil stock opname.
                        </div>

                        <div class="mt-3 d-flex justify-content-between">
                            <button type="button" @click="addRow" class="btn btn-success btn-rounded">
                                <i class="bx bx-plus label-icon"></i> Tambah Baris
                            </button>

                            <div>
                                <a href="/riwayat/penyesuaian" class="btn btn-secondary me-2">Batal</a>
                                <button type="submit" :disabled="form.processing" class="btn btn-info">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="mdi mdi-content-save me-1"></i>
                                    Simpan Penyesuaian
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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
    color: #dc3545;
}
</style>
