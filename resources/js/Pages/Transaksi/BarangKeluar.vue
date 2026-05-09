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
        qty: 1,
        harga_satuan: 0,
        keterangan: '',
    };
}

const form = useForm({
    tipe: 'out',
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

const totalQty = computed(() => {
    return form.items.reduce((sum, row) => sum + (parseFloat(row.qty) || 0), 0);
});

const totalValue = computed(() => {
    return form.items.reduce((sum, row) => {
        const qty = parseFloat(row.qty) || 0;
        const harga = parseFloat(row.harga_satuan) || 0;
        return sum + (qty * harga);
    }, 0);
});

function rowError(idx, field) {
    return form.errors?.[`items.${idx}.${field}`];
}

function getBarangInfo(id) {
    const b = props.barangs.find(x => x.id === parseInt(id));
    return b ? { kode: b.kode_barang, nama: b.nama_barang, satuan: b.satuan } : null;
}

// Validasi live per row
const rowErrors = computed(() => {
    return form.items.map((row, idx) => {
        const errors = {};
        if (!row.barang_id) errors.barang_id = 'Pilih barang';
        if (!row.qty || row.qty <= 0) errors.qty = 'Qty harus > 0';
        if (row.harga_satuan < 0) errors.harga_satuan = 'Harga tidak boleh negatif';
        
        // Cek duplikat barang
        const duplicate = form.items.findIndex((r, i) => i !== idx && r.barang_id && r.barang_id === row.barang_id);
        if (duplicate !== -1) errors.barang_id = 'Barang sudah dipilih di baris ' + (duplicate + 1);
        
        return errors;
    });
});

const hasErrors = computed(() => {
    if (!form.gudang_id) return true;
    if (form.items.length === 0) return true;
    return rowErrors.value.some(err => Object.keys(err).length > 0);
});

function submit() {
    if (!form.gudang_id) {
        window.toast?.error('Pilih gudang asal');
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
                window.toast?.success('Pengeluaran berhasil disimpan');
                router.visit('/riwayat/barang-keluar');
            },
            onError: () => {
                window.toast?.error('Gagal menyimpan pengeluaran');
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
                            <i class="mdi mdi-arrow-up-bold text-danger me-2"></i>
                            PENGELUARAN BARANG
                        </h5>
                        <div class="flex-shrink-0">
                            <a href="/riwayat/barang-keluar" class="btn btn-secondary btn-rounded">
                                <i class="mdi mdi-arrow-left me-1"></i>Kembali
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form @submit.prevent="submit" autocomplete="off">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="gudang_id_keluar" class="form-label">Gudang Asal <span class="text-danger">*</span></label>
                                <select id="gudang_id_keluar" name="gudang_id" v-model="form.gudang_id" class="form-select" :class="{ 'is-invalid': form.errors?.gudang_id }" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                        {{ g.kode_gudang }} - {{ g.nama_gudang }}
                                    </option>
                                </select>
                                <div v-if="form.errors?.gudang_id" class="invalid-feedback">{{ form.errors.gudang_id }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="tanggal_keluar" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input id="tanggal_keluar" name="tanggal" v-model="form.tanggal" type="date" class="form-control" :class="{ 'is-invalid': form.errors?.tanggal }" required>
                                <div v-if="form.errors?.tanggal" class="invalid-feedback">{{ form.errors.tanggal }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="referensi_keluar" class="form-label">Referensi</label>
                                <input id="referensi_keluar" name="referensi" v-model="form.referensi" type="text" class="form-control" placeholder="No. DO, Surat Jalan">
                            </div>
                            <div class="col-md-3">
                                <label for="keterangan_keluar" class="form-label">Keterangan</label>
                                <input id="keterangan_keluar" name="keterangan" v-model="form.keterangan" type="text" class="form-control" placeholder="Keterangan tambahan">
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
                                        <th style="width: 10%;">Qty</th>
                                        <th style="width: 12%;">Harga Jual</th>
                                        <th style="width: 12%;">Subtotal</th>
                                        <th style="width: 15%;">Keterangan</th>
                                        <th style="width: 3%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in form.items" :key="row._key">
                                        <td class="text-center">{{ idx + 1 }}</td>
                                        <td style="vertical-align: top;">
                                            <SearchSelect
                                                v-model="row.barang_id"
                                                :options="barangs"
                                                option-value="id"
                                                option-label="kode_barang"
                                                :option-sublabel="(b) => b.nama_barang"
                                                placeholder="Pilih"
                                                search-placeholder="Cari barang..."
                                                :invalid="!!rowErrors[idx]?.barang_id" />
                                            <div v-if="rowErrors[idx]?.barang_id" style="font-size: 11px; color: #dc3545; margin-top: 2px; line-height: 1.2;">
                                                {{ rowErrors[idx].barang_id }}
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ getBarangInfo(row.barang_id)?.nama || '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">{{ getBarangInfo(row.barang_id)?.satuan || '-' }}</small>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <input :id="`qty_keluar_${idx}`" :name="`items[${idx}][qty]`" v-model.number="row.qty" type="number" step="0.01" min="0.01"
                                                class="form-control form-control-sm text-end"
                                                :class="{ 'is-invalid': rowErrors[idx]?.qty }"
                                                :aria-label="`Qty barang baris ${idx + 1}`">
                                            <div v-if="rowErrors[idx]?.qty" style="font-size: 11px; color: #dc3545; margin-top: 2px; line-height: 1.2;">
                                                {{ rowErrors[idx].qty }}
                                            </div>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <input :id="`harga_satuan_keluar_${idx}`" :name="`items[${idx}][harga_satuan]`" v-model.number="row.harga_satuan" type="number" step="0.01" min="0"
                                                class="form-control form-control-sm text-end"
                                                :class="{ 'is-invalid': rowErrors[idx]?.harga_satuan }"
                                                :aria-label="`Harga jual barang baris ${idx + 1}`">
                                            <div v-if="rowErrors[idx]?.harga_satuan" style="font-size: 11px; color: #dc3545; margin-top: 2px; line-height: 1.2;">
                                                {{ rowErrors[idx].harga_satuan }}
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <small class="text-muted">{{ (row.qty * row.harga_satuan).toLocaleString('id-ID') }}</small>
                                        </td>
                                        <td class="position-relative">
                                            <input :id="`keterangan_item_keluar_${idx}`" :name="`items[${idx}][keterangan]`" v-model="row.keterangan" type="text" class="form-control form-control-sm" placeholder="Opsional" :aria-label="`Keterangan barang baris ${idx + 1}`">
                                        </td>
                                        <td class="text-center position-relative">
                                            <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                                class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16">
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">TOTAL:</th>
                                        <th class="text-end">{{ totalQty.toLocaleString('id-ID') }}</th>
                                        <th></th>
                                        <th class="text-end">Rp {{ totalValue.toLocaleString('id-ID') }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-3 d-flex justify-content-between">
                            <button type="button" @click="addRow" class="btn btn-success btn-rounded">
                                <i class="bx bx-plus label-icon"></i> Tambah Baris
                            </button>

                            <div>
                                <a href="/riwayat/barang-keluar" class="btn btn-secondary me-2">Batal</a>
                                <button type="submit" :disabled="form.processing || hasErrors" class="btn btn-success">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="mdi mdi-content-save me-1"></i>
                                    Simpan Pengeluaran
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
/* Hilangkan icon tanda seru Bootstrap yang bertabrakan */
.form-control.is-invalid {
    background-image: none !important;
    padding-right: 0.75rem !important;
}
</style>
