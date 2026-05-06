<script setup>
import { computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    barangs: { type: Array, default: () => [] },
    gudangs: { type: Array, default: () => [] },
});

function emptyRow() {
    return {
        _key: Date.now() + Math.random(),
        barang_id: '',
        qty: 0,
        harga_satuan: null,
        keterangan: '',
    };
}

const form = useForm({
    tanggal: new Date().toISOString().slice(0, 16),
    tipe: 'in',
    gudang_id: '',
    gudang_tujuan_id: '',
    referensi: '',
    keterangan: '',
    items: [emptyRow()],
});

const isTransfer = computed(() => form.tipe === 'transfer');
const isAdjust   = computed(() => form.tipe === 'adjust');

const qtyLabel = computed(() => isAdjust.value ? 'Stok Akhir' : 'Qty');
const qtyHelp = computed(() => {
    if (isAdjust.value)   return 'Isi stok aktual hasil opname per barang.';
    if (isTransfer.value) return 'Jumlah barang yang dipindahkan dari gudang asal ke gudang tujuan.';
    if (form.tipe === 'in')  return 'Jumlah barang masuk ke gudang.';
    if (form.tipe === 'out') return 'Jumlah barang keluar dari gudang.';
    return '';
});

function addRow() { form.items.push(emptyRow()); }
function removeRow(idx) { if (form.items.length > 1) form.items.splice(idx, 1); }

function rowError(idx, field) {
    return form.errors[`items.${idx}.${field}`];
}

const totalQty = computed(() =>
    form.items.reduce((s, r) => s + (Number(r.qty) || 0), 0)
);
const totalValue = computed(() =>
    form.items.reduce((s, r) => s + (Number(r.qty) || 0) * (Number(r.harga_satuan) || 0), 0)
);

function fmtRp(v) {
    return 'Rp ' + Number(v || 0).toLocaleString('id-ID');
}

function barangLabel(b) {
    return `${b.kode_barang} — ${b.nama_barang}`;
}

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.map(({ _key, ...rest }) => rest),
    })).post('/mutasi', {
        preserveScroll: true,
        onSuccess: () => {
            router.flushAll();
            window.toast?.success('Mutasi tersimpan');
        },
        onError: () => window.toast?.error('Gagal Simpan, periksa form'),
    });
}
</script>

<template>
    <div class="card shadow-sm">
        <div class="card-body border-bottom d-flex justify-content-between">
            <h5 class="mb-0">Tambah Mutasi Stok</h5>
            <Link href="/mutasi" class="btn btn-primary btn-rounded mb-2">
                <i class="mdi mdi-arrow-left me-1"></i>Kembali
            </Link>
        </div>

        <div class="card-body">
            <form @submit.prevent="submit" autocomplete="off">
                <!-- HEADER -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="datetime-local" v-model="form.tanggal" class="form-control"
                            :class="{ 'is-invalid': form.errors.tanggal }">
                        <small class="text-danger" v-if="form.errors.tanggal">{{ form.errors.tanggal }}</small>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tipe Mutasi</label>
                        <select v-model="form.tipe" class="form-select">
                            <option value="in">Masuk (Penerimaan)</option>
                            <option value="out">Keluar (Pengeluaran)</option>
                            <option value="transfer">Transfer Antar Gudang</option>
                            <option value="adjust">Penyesuaian (Stock Opname)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ isTransfer ? 'Gudang Asal' : 'Gudang' }}</label>
                        <select v-model="form.gudang_id" class="form-select"
                            :class="{ 'is-invalid': form.errors.gudang_id }">
                            <option value="">Pilih Gudang</option>
                            <option v-for="g in gudangs" :key="g.id" :value="g.id">{{ g.nama_gudang }}</option>
                        </select>
                        <small class="text-danger" v-if="form.errors.gudang_id">{{ form.errors.gudang_id }}</small>
                    </div>
                    <div v-if="isTransfer" class="col-md-3 mb-3">
                        <label class="form-label">Gudang Tujuan</label>
                        <select v-model="form.gudang_tujuan_id" class="form-select"
                            :class="{ 'is-invalid': form.errors.gudang_tujuan_id }">
                            <option value="">Pilih Gudang Tujuan</option>
                            <option v-for="g in gudangs" :key="g.id" :value="g.id"
                                :disabled="g.id === form.gudang_id">{{ g.nama_gudang }}</option>
                        </select>
                        <small class="text-danger" v-if="form.errors.gudang_tujuan_id">{{ form.errors.gudang_tujuan_id }}</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">No. Referensi</label>
                        <input v-model="form.referensi" class="form-control" placeholder="PO / Invoice / DO / dst">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Keterangan (header)</label>
                        <input v-model="form.keterangan" class="form-control">
                    </div>
                </div>

                <!-- ITEMS REPEATER -->
                <div class="d-flex justify-content-between align-items-center mb-2 mt-2">
                    <h6 class="mb-0">Daftar Barang</h6>
                    <small class="text-muted">{{ qtyHelp }}</small>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Barang</th>
                                <th style="width: 120px;" class="text-end">{{ qtyLabel }}</th>
                                <th style="width: 80px;">Satuan</th>
                                <th style="width: 150px;" v-if="!isAdjust">Harga Satuan</th>
                                <th style="width: 150px;" v-if="!isAdjust" class="text-end">Subtotal</th>
                                <th>Keterangan</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in form.items" :key="row._key">
                                <td>{{ idx + 1 }}</td>
                                <td class="position-relative">
                                    <select v-model="row.barang_id" class="form-select form-select-sm"
                                        :class="{ 'is-invalid': rowError(idx, 'barang_id') }">
                                        <option value="">Pilih barang</option>
                                        <option v-for="b in barangs" :key="b.id" :value="b.id">
                                            {{ barangLabel(b) }}
                                        </option>
                                    </select>
                                    <small v-if="rowError(idx, 'barang_id')" class="text-danger">
                                        {{ rowError(idx, 'barang_id') }}
                                    </small>
                                </td>
                                <td>
                                    <input type="number" v-model.number="row.qty" min="0"
                                        class="form-control form-control-sm text-end"
                                        :class="{ 'is-invalid': rowError(idx, 'qty') }">
                                    <small v-if="rowError(idx, 'qty')" class="text-danger">
                                        {{ rowError(idx, 'qty') }}
                                    </small>
                                </td>
                                <td class="text-muted">
                                    {{ barangs.find(b => b.id === row.barang_id)?.satuan || '-' }}
                                </td>
                                <td v-if="!isAdjust">
                                    <input type="number" v-model.number="row.harga_satuan" min="0"
                                        class="form-control form-control-sm text-end" placeholder="0">
                                </td>
                                <td v-if="!isAdjust" class="text-end text-muted">
                                    {{ fmtRp((row.qty || 0) * (row.harga_satuan || 0)) }}
                                </td>
                                <td>
                                    <input v-model="row.keterangan" class="form-control form-control-sm">
                                </td>
                                <td class="text-center">
                                    <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                        class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16">
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="!isAdjust">
                            <tr class="table-light">
                                <th colspan="2" class="text-end">Total</th>
                                <th class="text-end">{{ totalQty.toLocaleString('id-ID') }}</th>
                                <th colspan="2"></th>
                                <th class="text-end">{{ fmtRp(totalValue) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-2">
                    <button type="button" @click="addRow" class="btn btn-success btn-rounded">
                        <i class="bx bx-plus me-1"></i>Tambah Baris
                    </button>
                    <button type="submit" class="btn btn-success btn-rounded" :disabled="form.processing">
                        <i class="bx bx-save me-1"></i>Simpan Mutasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.is-invalid { border-color: #f46a6a !important; }
</style>
