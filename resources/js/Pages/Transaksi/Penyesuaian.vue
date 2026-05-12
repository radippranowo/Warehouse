<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';

const props = defineProps({
    barangs: { type: Array, required: true },
    gudangs: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

// Ref untuk menyimpan stok saat ini dari setiap barang
const currentStocks = ref({});

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

const barangOptions = computed(() => {
    return props.barangs.map(b => ({
        value: b.id,
        label: `${b.kode_barang} - ${b.nama_barang}`,
        kode: b.kode_barang,
        nama: b.nama_barang,
        satuan: b.satuan,
        harga: b.harga || 0,
    }));
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
    return b ? { kode: b.kode_barang, nama: b.nama_barang, satuan: b.satuan, harga: b.harga || 0 } : null;
}

// Auto-fill harga dari master barang saat barang dipilih
function onBarangSelected(idx, barang) {
    console.log('Barang selected:', barang, 'Gudang ID:', form.gudang_id);
    
    if (barang && barang.harga) {
        form.items[idx].harga_satuan = barang.harga;
    }
    
    // Ambil stok saat ini untuk barang yang dipilih
    if (barang && barang.value && form.gudang_id) {
        console.log('Fetching stock for barang:', barang.value, 'gudang:', form.gudang_id);
        fetchCurrentStock(barang.value, form.gudang_id);
    } else if (!form.gudang_id) {
        window.toast?.warning('Pilih gudang terlebih dahulu untuk melihat stok');
    }
}

// Fungsi untuk mengambil stok saat ini
async function fetchCurrentStock(barangId, gudangId) {
    console.log('Fetching stock from API:', `/api/v1/stok/${barangId}/${gudangId}`);
    try {
        const response = await fetch(`/api/v1/stok/${barangId}/${gudangId}`);
        console.log('Response status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('Stock data received:', data);
            
            if (data.success) {
                currentStocks.value[barangId] = data.stok || 0;
                console.log('Updated currentStocks:', currentStocks.value);
            }
        } else {
            console.error('Failed to fetch stock:', response.status);
        }
    } catch (error) {
        console.error('Error fetching stock:', error);
    }
}

// Fungsi untuk mendapatkan stok saat ini
function getCurrentStock(barangId) {
    const stock = currentStocks.value[barangId];
    return stock !== undefined ? stock : '-';
}

// Fungsi untuk mengecek apakah stok sudah di-load
function isStockLoaded(barangId) {
    return currentStocks.value[barangId] !== undefined;
}

// Fungsi untuk mengecek apakah perubahan stok signifikan
function isSignificantChange(barangId, newQty) {
    if (!isStockLoaded(barangId)) return false;
    
    const currentStock = currentStocks.value[barangId];
    if (currentStock === 0 && newQty === 0) return false;
    if (newQty === '' || newQty === null || newQty === undefined) return false;
    
    const diff = Math.abs(newQty - currentStock);
    const percentChange = currentStock > 0 ? (diff / currentStock) * 100 : 100;
    
    // Perubahan dianggap signifikan jika > 50% atau selisih > 100 unit
    return percentChange > 50 || diff > 100;
}

// Watch perubahan gudang untuk refresh stok
function onGudangChange() {
    currentStocks.value = {};
    form.items.forEach((item, idx) => {
        if (item.barang_id && form.gudang_id) {
            fetchCurrentStock(item.barang_id, form.gudang_id);
        }
    });
}

function validateForm() {
    const errors = [];

    // Validasi header
    if (!form.gudang_id) {
        errors.push('Gudang harus dipilih');
    }

    if (!form.tanggal) {
        errors.push('Tanggal harus diisi');
    }

    // Validasi items
    if (form.items.length === 0) {
        errors.push('Minimal harus ada 1 barang');
        return errors;
    }

    // Cek barang duplikat
    const barangIds = form.items.map(item => item.barang_id).filter(id => id);
    const duplicates = barangIds.filter((id, index) => barangIds.indexOf(id) !== index);
    if (duplicates.length > 0) {
        errors.push('Ada barang yang duplikat dalam daftar');
    }

    // Validasi setiap item
    form.items.forEach((item, idx) => {
        if (!item.barang_id) {
            errors.push(`Baris ${idx + 1}: Barang harus dipilih`);
        }

        if (item.qty === '' || item.qty === null || item.qty === undefined) {
            errors.push(`Baris ${idx + 1}: Stok baru harus diisi`);
        } else if (isNaN(item.qty)) {
            errors.push(`Baris ${idx + 1}: Stok baru harus berupa angka`);
        } else if (parseFloat(item.qty) < 0) {
            errors.push(`Baris ${idx + 1}: Stok baru tidak boleh negatif`);
        }
    });

    return errors;
}

async function submit() {
    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
        validationErrors.forEach(error => window.toast?.error(error));
        return;
    }

    // Konfirmasi pakai modal app, bukan browser native confirm.
    const ok = window.confirmDialog
        ? await window.confirmDialog({
              title: 'Simpan penyesuaian stok?',
              text: 'Pastikan nilai stok baru sudah benar sesuai stock opname.',
              okText: 'Ya, simpan',
              okClass: 'btn-info',
          })
        : true;
    if (!ok) return;

    // Buang prefetch cache supaya halaman riwayat (target redirect dari server)
    // di-fetch fresh, bukan dari snapshot prefetch lama.
    router.flushAll();

    form
        .transform((data) => ({
            ...data,
            items: data.items.map(({ _key, ...rest }) => rest),
        }))
        .post('/mutasi', {
            preserveScroll: true,
            onSuccess: () => {
                // Server redirect ke /riwayat/penyesuaian (lihat MutasiController::store).
                window.toast?.success('Penyesuaian stok berhasil disimpan');
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
                        
                    </div>
                </div>

                <div class="card-body">
                    <form @submit.prevent="submit" autocomplete="off">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="gudang_id_penyesuaian" class="form-label">Gudang <span class="text-danger">*</span></label>
                                <select id="gudang_id_penyesuaian" name="gudang_id" v-model="form.gudang_id" @change="onGudangChange" class="form-select" :class="{ 'is-invalid': form.errors?.gudang_id }">
                                    <option value="">-- Pilih Gudang --</option>
                                    <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                        {{ g.kode_gudang }} - {{ g.nama_gudang }}
                                    </option>
                                </select>
                                <div v-if="form.errors?.gudang_id" class="invalid-feedback d-block">{{ form.errors.gudang_id }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="tanggal_penyesuaian" class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input id="tanggal_penyesuaian" name="tanggal" v-model="form.tanggal" type="date" class="form-control" :class="{ 'is-invalid': form.errors?.tanggal }">
                                <div v-if="form.errors?.tanggal" class="invalid-feedback d-block">{{ form.errors.tanggal }}</div>
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
                                        <th style="width: 4%;" class="text-center">No</th>
                                        <th style="width: 35%;" class="text-center">Barang</th>
                                        <th style="width: 8%;" class="text-center">Satuan</th>
                                        <th style="width: 5%;" class="text-center">Stok Sekarang</th>
                                        <th style="width: 10%;" class="text-center">Stok Baru</th>
                                        <th style="width: 20%;" class="text-center">Keterangan</th>
                                        <th style="width: 8%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in form.items" :key="row._key">
                                        <td class="text-center align-middle">
                                            <span class="badge bg-secondary">{{ idx + 1 }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="cell-wrapper">
                                                <SearchInput
                                                    :id="`barang_penyesuaian_${idx}`"
                                                    v-model="row.barang_id"
                                                    :options="barangOptions"
                                                    placeholder="Cari barang..."
                                                    :invalid="!!rowError(idx, 'barang_id')"
                                                    @select="onBarangSelected(idx, $event)"
                                                    :tabindex="idx * 10 + 1" />
                                                <div v-if="rowError(idx, 'barang_id')" class="error-message">
                                                    {{ rowError(idx, 'barang_id') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="row.barang_id" class="badge bg-info">
                                                {{ getBarangInfo(row.barang_id)?.satuan || '-' }}
                                            </span>
                                            <span v-else class="badge bg-secondary">-</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div v-if="row.barang_id && form.gudang_id">
                                                <span v-if="isStockLoaded(row.barang_id)" class="badge bg-secondary fs-6">
                                                    {{ getCurrentStock(row.barang_id) }}
                                                </span>
                                                <span v-else class="spinner-border spinner-border-sm text-muted"></span>
                                            </div>
                                            <span v-else-if="!form.gudang_id" class="text-muted small">Pilih gudang</span>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="cell-wrapper">
                                                <input :id="`qty_penyesuaian_${idx}`" :name="`items[${idx}][qty]`" v-model.number="row.qty" type="number" step="0.01"
                                                    class="form-control text-end"
                                                    :class="{ 
                                                        'is-invalid': rowError(idx, 'qty'),
                                                        'border-warning': row.barang_id && isSignificantChange(row.barang_id, row.qty)
                                                    }"
                                                    placeholder="0"
                                                    :aria-label="`Stok baru barang baris ${idx + 1}`">
                                                <div v-if="rowError(idx, 'qty')" class="error-message">
                                                    {{ rowError(idx, 'qty') }}
                                                </div>
                                                <div v-else-if="row.barang_id && isSignificantChange(row.barang_id, row.qty)" class="warning-message">
                                                    <i class="mdi mdi-alert"></i> Perubahan signifikan
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <input :id="`keterangan_item_penyesuaian_${idx}`" :name="`items[${idx}][keterangan]`" v-model="row.keterangan" type="text" class="form-control" placeholder="Alasan penyesuaian" :aria-label="`Keterangan barang baris ${idx + 1}`">
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                                class="btn btn-danger btn-sm" title="Hapus baris">
                                                <i class="bx bx-trash"></i>
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
/* 1. Reset Global Row - Tinggi minimum untuk stabilitas */
tbody tr {
    min-height: 70px;
}

tbody td {
    vertical-align: middle !important;
    padding: 8px !important;
    position: relative;
}

/* 2. Cell Wrapper - Container untuk input dan error */
.cell-wrapper {
    position: relative;
    min-height: 38px; /* Tinggi minimum untuk input */
}

/* 3. Error Message - Absolute positioning agar tidak menggeser layout */
.error-message {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 10;
    margin-top: 2px;
    padding: 5px 10px;
    background-color: #dc3545;
    color: white;
    font-size: 0.75rem;
    border-radius: 4px;
    box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
    white-space: normal;
    line-height: 1.3;
    min-width: 200px;
    max-width: 400px;
}

/* 3b. Warning Message - Absolute positioning seperti error message */
.warning-message {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 9;
    margin-top: 2px;
    padding: 0;
    color: #ff9800;
    font-size: 0.7rem;
    white-space: nowrap;
    line-height: 1.2;
    font-weight: 600;
}

/* 4. Input dengan error - border merah */
.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

/* 5. Warning border untuk perubahan signifikan */
.border-warning {
    border-color: #ffc107 !important;
    border-width: 2px !important;
}

/* 6. Pastikan table responsive tidak overflow */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* 7. Badge styling */
.badge {
    font-weight: 500;
}

/* 7b. Header tabel - font size lebih kecil */
thead th {
    font-size: 0.85rem;
    font-weight: 600;
    padding: 10px 8px !important;
}

/* 8. Spinner untuk loading state */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* 9. Hover effect untuk row */
tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* 10. Fix untuk SearchInput dalam cell */
.cell-wrapper :deep(.search-input-wrapper) {
    position: relative;
    z-index: 1;
}

/* 11. Dropdown dari SearchInput tidak terpotong */
/* 12. Dropdown dari SearchInput tidak terpotong */
.cell-wrapper :deep(.dropdown-menu) {
    position: absolute;
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}
</style>
