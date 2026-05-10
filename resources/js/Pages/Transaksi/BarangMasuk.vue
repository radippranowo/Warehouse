<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { useTransaksiForm, useTransaksiKeyboard, handleFormErrors } from '@/Composables/useTransaksiForm';

const props = defineProps({
    barangs: { type: Array, required: true },
    gudangs: { type: Array, required: true },
    suppliers: { type: Array, required: true },
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
    tipe: 'in',
    supplier_id: '',
    gudang_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    referensi: '',
    keterangan: '',
    items: [emptyRow()],
});

function addRow() {
    form.items.push(emptyRow());
    setTimeout(() => {
        const newIndex = form.items.length - 1;
        document.querySelector(`#barang_masuk_${newIndex}`)?.focus();
    }, 100);
}

function removeRow(idx) {
    if (form.items.length > 1) {
        form.items.splice(idx, 1);
        window.toast?.info(`Baris ${idx + 1} dihapus`);
    }
}

// Auto-fill harga dari barang yang dipilih
function onBarangSelected(idx, barang) {
    if (barang && barang.harga && form.items[idx].harga_satuan === 0) {
        form.items[idx].harga_satuan = barang.harga;
        window.toast?.info(`Harga otomatis: ${formatCurrency(barang.harga)}`);
    }
}

const barangsRef = computed(() => props.barangs);

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

const { 
    rowErrors, 
    totalQty, 
    totalValue,
    formatCurrency,
    formatNumber 
} = useTransaksiForm(form, barangsRef);

const hasErrors = computed(() => {
    return !form.gudang_id || 
           !form.supplier_id || 
           form.items.length === 0 || 
           rowErrors.value.some(err => Object.keys(err).length > 0);
});

function resetForm() {
    if (form.items.length > 1 || form.items[0].barang_id) {
        if (!confirm('Reset form? Semua data yang belum disimpan akan hilang.')) {
            return;
        }
    }
    form.supplier_id = '';
    form.gudang_id = '';
    form.tanggal = new Date().toISOString().split('T')[0];
    form.referensi = '';
    form.keterangan = '';
    form.items = [emptyRow()];
    form.clearErrors();
    window.toast?.success('Form berhasil direset');
}

function submit() {
    if (hasErrors.value) {
        if (!form.gudang_id) window.toast?.error('Pilih gudang tujuan');
        else if (!form.supplier_id) window.toast?.error('Pilih supplier');
        else if (form.items.length === 0) window.toast?.error('Tambahkan minimal 1 barang');
        else window.toast?.error('Lengkapi data dengan benar');
        return;
    }

    form
        .transform((data) => ({
            ...data,
            items: data.items.map(({ _key, ...rest }) => rest),
        }))
        .post('/mutasi', {
            preserveScroll: false,
            preserveState: false,
            onSuccess: () => {
                window.toast?.success('Pemasukan berhasil disimpan');
                // Redirect langsung dengan replace untuk menghindari history stack
                router.replace('/riwayat/barang-masuk');
            },
            onError: handleFormErrors,
        });
}

// Setup keyboard shortcuts
const { handleKeyboard } = useTransaksiKeyboard({
    onSubmit: submit,
    onAddRow: addRow,
    onReset: resetForm,
    disabled: () => hasErrors.value || form.processing,
});

onMounted(() => {
    document.addEventListener('keydown', handleKeyboard);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboard);
});
</script>

<template>
    <!-- Header Card -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h4 class="mb-0 fw-bold text-success">PEMASUKAN BARANG</h4>
                    <small class="text-muted">
                        Form transaksi barang masuk • <kbd>Ctrl+N</kbd> tambah baris • <kbd>Ctrl+Enter</kbd> simpan
                    </small>
                </div>
                
            </div>
        </div>
    </div>

    <form @submit.prevent="submit" autocomplete="off">
        <div class="row">
            <!-- Left Panel - Form Input -->
            <div class="col-lg-8">
                <!-- Info Transaksi Card -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bx bx-info-circle me-1"></i>Informasi Transaksi
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="supplier_id_masuk" class="form-label fw-medium">
                                    Supplier <span class="text-danger">*</span>
                                </label>
                                <select id="supplier_id_masuk" name="supplier_id" v-model="form.supplier_id" 
                                    class="form-select" 
                                    :class="{ 'is-invalid': form.errors?.supplier_id }" 
                                    style="height: 42px;" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                        {{ s.kode_supplier }} - {{ s.nama_supplier }}
                                    </option>
                                </select>
                                <small v-if="suppliers.length === 0" class="text-warning">⚠️ Belum ada supplier aktif</small>
                                <div v-if="form.errors?.supplier_id" class="invalid-feedback">{{ form.errors.supplier_id }}</div>
                            </div>
                            <div class="col-md-6">
                                <label for="gudang_id_masuk" class="form-label fw-medium">
                                    Gudang Tujuan <span class="text-danger">*</span>
                                </label>
                                <select id="gudang_id_masuk" name="gudang_id" v-model="form.gudang_id" 
                                    class="form-select" 
                                    :class="{ 'is-invalid': form.errors?.gudang_id }" 
                                    style="height: 42px;" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                        {{ g.kode_gudang }} - {{ g.nama_gudang }}
                                    </option>
                                </select>
                                <div v-if="form.errors?.gudang_id" class="invalid-feedback">{{ form.errors.gudang_id }}</div>
                            </div>
                            <div class="col-md-4">
                                <label for="tanggal_masuk" class="form-label fw-medium">
                                    Tanggal <span class="text-danger">*</span>
                                </label>
                                <input id="tanggal_masuk" name="tanggal" v-model="form.tanggal" type="date" 
                                    class="form-control" 
                                    :class="{ 'is-invalid': form.errors?.tanggal }" 
                                    style="height: 42px;" required>
                                <div v-if="form.errors?.tanggal" class="invalid-feedback">{{ form.errors.tanggal }}</div>
                            </div>
                            <div class="col-md-4">
                                <label for="referensi_masuk" class="form-label fw-medium">Referensi</label>
                                <input id="referensi_masuk" name="referensi" v-model="form.referensi" type="text" 
                                    class="form-control" 
                                    placeholder="No. PO, Invoice" 
                                    style="height: 40px;">
                            </div>
                            <div class="col-md-4">
                                <label for="keterangan_masuk" class="form-label fw-medium">Keterangan</label>
                                <input id="keterangan_masuk" name="keterangan" v-model="form.keterangan" type="text" 
                                    class="form-control" 
                                    placeholder="Keterangan tambahan" 
                                    style="height: 40px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daftar Barang Card -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bx bx-list-ul me-1"></i>Daftar Barang
                        </h6>
                        <button type="button" @click="addRow" class="btn btn-success btn-sm" title="Tekan Ctrl+N">
                            <i class="bx bx-plus"></i> Tambah Baris
                        </button>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 3%;" class="text-center">#</th>
                                        <th style="width: 32%;">Barang</th>
                                        <th style="width: 8%;" class="text-center">Satuan</th>
                                        <th style="width: 13%;">Qty</th>
                                        <th style="width: 18%;">Harga Modal</th>
                                        <th style="width: 18%;" class="text-end">Subtotal</th>
                                        <th style="width: 5%;" class="text-center">
                                            <i class="bx bx-trash"></i>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in form.items" :key="row._key">
                                        <td class="text-center align-middle">
                                            <span class="badge bg-secondary">{{ idx + 1 }}</span>
                                        </td>
                                        <td class="py-2">
                                            <SearchInput
                                                :id="`barang_masuk_${idx}`"
                                                v-model="row.barang_id"
                                                :options="barangOptions"
                                                placeholder="Cari barang..."
                                                :invalid="!!rowErrors[idx]?.barang_id"
                                                @select="onBarangSelected(idx, $event)"
                                                :tabindex="idx * 10 + 1" />
                                            <div v-if="rowErrors[idx]?.barang_id" class="text-danger small mt-1">
                                                {{ rowErrors[idx].barang_id }}
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span v-if="row.barang_id" class="badge bg-info">
                                                {{ barangs.find(b => b.id === row.barang_id)?.satuan || '-' }}
                                            </span>
                                            <span v-else class="badge bg-secondary">-</span>
                                        </td>
                                        <td class="py-2">
                                            <input :id="`qty_masuk_${idx}`" :name="`items[${idx}][qty]`" 
                                                v-model.number="row.qty" type="number" step="0.01" min="0.01"
                                                class="form-control text-end fw-bold"
                                                :class="{ 'is-invalid': rowErrors[idx]?.qty }"
                                                style="font-size: 16px; height: 42px;"
                                                :aria-label="`Qty barang baris ${idx + 1}`">
                                            <div v-if="rowErrors[idx]?.qty" class="text-danger small mt-1">
                                                {{ rowErrors[idx].qty }}
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <div class="input-group">
                                                <span class="input-group-text" style="font-size: 14px;">Rp</span>
                                                <input :id="`harga_satuan_masuk_${idx}`" :name="`items[${idx}][harga_satuan]`" 
                                                    v-model.number="row.harga_satuan" type="number" step="0.01" min="0"
                                                    class="form-control text-end fw-bold"
                                                    :class="{ 'is-invalid': rowErrors[idx]?.harga_satuan }"
                                                    style="font-size: 15px; height: 42px;"
                                                    :aria-label="`Harga modal barang baris ${idx + 1}`">
                                            </div>
                                            <div v-if="rowErrors[idx]?.harga_satuan" class="text-danger small mt-1">
                                                {{ rowErrors[idx].harga_satuan }}
                                            </div>
                                        </td>
                                        <td class="text-end align-middle">
                                            <div class="fw-bold" :class="row.barang_id && row.qty > 0 ? 'text-success' : 'text-muted'" style="font-size: 16px;">
                                                {{ formatCurrency(row.qty * row.harga_satuan) }}
                                            </div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" v-if="form.items.length > 1" @click="removeRow(idx)"
                                                class="btn btn-danger btn-sm" title="Hapus baris">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Belum ada barang. Klik "Tambah Baris" untuk menambah.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Summary & Actions -->
            <div class="col-lg-4">
                <!-- Summary Card -->
                <div class="card shadow-sm mb-3 sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bx bx-calculator me-1"></i>Ringkasan Transaksi
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><i class="bx bx-list-ul me-1"></i>Total Item:</span>
                                <span class="badge bg-primary fs-6">{{ form.items.length }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted"><i class="bx bx-package me-1"></i>Total Qty:</span>
                                <span class="badge bg-success fs-6">{{ formatNumber(totalQty) }}</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted"><i class="bx bx-money me-1"></i>Total Nilai:</span>
                                <div class="text-end">
                                    <div class="fw-bold text-success" style="font-size: 24px;">
                                        {{ formatCurrency(totalValue) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-0" v-if="!form.gudang_id || !form.supplier_id">
                            <i class="bx bx-info-circle me-1"></i>
                            <small>Pilih supplier dan gudang terlebih dahulu</small>
                        </div>
                        <div class="alert alert-warning mb-0" v-else-if="hasErrors">
                            <i class="bx bx-error me-1"></i>
                            <small>Lengkapi data barang dengan benar</small>
                        </div>
                        <div class="alert alert-success mb-0" v-else>
                            <i class="bx bx-check-circle me-1"></i>
                            <small>Siap untuk disimpan</small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Card -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" :disabled="form.processing || hasErrors" 
                                class="btn btn-success btn-lg fw-bold" style="height: 60px; font-size: 18px;">
                                <span v-if="form.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Menyimpan...
                                </span>
                                <span v-else>
                                    <i class="bx bx-save me-2" style="font-size: 24px;"></i>
                                    SIMPAN TRANSAKSI
                                    <small class="d-block mt-1" style="font-size: 11px; opacity: 0.8;">Ctrl+Enter</small>
                                </span>
                            </button>
                            <button type="button" @click="resetForm" class="btn btn-outline-secondary btn-lg">
                                <i class="bx bx-rotate-left me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Keterangan Item Card -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bx bx-note me-1"></i>Keterangan Item
                        </h6>
                    </div>
                    <div class="card-body">
                        <div v-for="(row, idx) in form.items" :key="row._key" class="mb-2">
                            <label :for="`keterangan_item_masuk_${idx}`" class="form-label small mb-1">
                                Item {{ idx + 1 }}
                            </label>
                            <input :id="`keterangan_item_masuk_${idx}`" :name="`items[${idx}][keterangan]`" 
                                v-model="row.keterangan" type="text" 
                                class="form-control form-control-sm" 
                                placeholder="Keterangan (opsional)" 
                                :aria-label="`Keterangan barang baris ${idx + 1}`">
                        </div>
                        <div v-if="form.items.length === 0" class="text-muted small text-center py-2">
                            Belum ada item
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>

<style scoped>
/* Hilangkan icon tanda seru Bootstrap yang bertabrakan */
.form-control.is-invalid {
    background-image: none !important;
    padding-right: 0.75rem !important;
}
</style>
