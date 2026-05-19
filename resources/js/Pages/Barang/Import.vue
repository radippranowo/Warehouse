<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    result: { type: Object, default: null },
});

defineOptions({ layout: AppLayout });

const form = useForm({ file: null });
const fileName = computed(() => form.file?.name ?? '');
const isUploading = ref(false);

function pickFile(e) {
    form.file = e.target.files[0] ?? null;
    form.clearErrors();
}

function submit() {
    if (!form.file) {
        window.toast?.error('Pilih file Excel dulu.');
        return;
    }
    isUploading.value = true;
    form.post('/barang/import', {
        forceFormData: true,
        onSuccess: (page) => {
            const r = page.props?.result;
            if (r?.status === 'success') {
                window.toast?.success(r.message);
            } else if (r?.status === 'partial') {
                window.toast?.info(r.message);
            } else if (r?.status === 'error') {
                window.toast?.error(r.message);
            }
            // Tetap di halaman import. Result panel stay visible.
            // flushAll → invalidate prefetch /barang & halaman lain supaya
            // saat user akhirnya pindah halaman, dapat data fresh.
            router.flushAll();
        },
        onFinish: () => { isUploading.value = false; },
    });
}

const resultClass = computed(() => {
    if (!props.result) return '';
    return {
        success: 'alert-success',
        partial: 'alert-warning',
        error:   'alert-danger',
    }[props.result.status] || '';
});

const resultIcon = computed(() => {
    if (!props.result) return '';
    return {
        success: 'bx-check-circle',
        partial: 'bx-error',
        error:   'bx-x-circle',
    }[props.result.status] || '';
});

// === RESET / CLEAR ALL ===
const showClearModal = ref(false);
const clearForm = useForm({ confirm: '', targets: [] });

const clearOptions = [
    { value: 'barang',       label: 'Barang',       hint: 'Otomatis ikut hapus stok per gudang & history mutasi' },
    { value: 'mutasi',       label: 'Riwayat Mutasi', hint: 'Hapus mutasi + lots FIFO + reset stok di semua gudang ke 0' },
    { value: 'category',     label: 'Master Kategori', hint: '' },
    { value: 'sub_category', label: 'Master Sub Kategori', hint: '' },
    { value: 'merk',         label: 'Master Merk', hint: '' },
    { value: 'group',        label: 'Master Group', hint: '' },
    { value: 'gudang',       label: 'Master Gudang', hint: 'Stok per gudang akan ikut hilang' },
];

const allChecked = computed(() =>
    clearOptions.every(opt => clearForm.targets.includes(opt.value))
);
const someChecked = computed(() =>
    clearForm.targets.length > 0 && !allChecked.value
);

function toggleAll(e) {
    clearForm.targets = e.target.checked
        ? clearOptions.map(o => o.value)
        : [];
}

function openClear() {
    clearForm.reset();
    clearForm.clearErrors();
    showClearModal.value = true;
}
function submitClear() {
    clearForm.post('/barang/clear', {
        preserveScroll: true,
        onSuccess: (page) => {
            showClearModal.value = false;
            const msg = page.props?.flash?.success
                ?? page.props?.result?.message
                ?? 'Data berhasil direset';
            window.toast?.success(msg);
            // Tetap di halaman import. Result panel akan tampil dari prop
            // result yg di-set lewat flash. Invalidate prefetch supaya saat
            // user akhirnya ke /barang, data fresh.
            router.flushAll();
        },
        onError: () => {
            window.toast?.error('Reset gagal — cek pilihan & konfirmasi');
        },
    });
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Import Barang dari Excel</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger btn-rounded" @click="openClear">
                            <i class="bx bx-trash me-1"></i>Reset Data
                        </button>
                        <Link href="/barang" class="btn btn-light btn-rounded">
                            <i class="bx bx-arrow-back me-1"></i>Kembali
                        </Link>
                    </div>
                </div>

                <div class="card-body">
                    <!-- HASIL IMPORT (ditampilkan setelah submit) -->
                    <div v-if="result" class="alert" :class="resultClass">
                        <h5 class="alert-heading mb-2">
                            <i class="bx" :class="resultIcon"></i>
                            {{ result.message }}
                        </h5>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Berhasil</small>
                                <strong class="text-success">{{ result.imported.toLocaleString('id-ID') }} baris</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Dilewati</small>
                                <strong class="text-warning">{{ result.skipped.toLocaleString('id-ID') }} baris</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Error</small>
                                <strong class="text-danger">{{ (result.errors?.length ?? 0).toLocaleString('id-ID') }} catatan</strong>
                            </div>
                        </div>

                        <div v-if="result.errors?.length" class="mt-3">
                            <strong class="d-block mb-1">Detail baris yg bermasalah:</strong>
                            <div class="bg-white border rounded p-2"
                                style="max-height: 280px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                                <div v-for="(err, i) in result.errors" :key="i" class="text-danger">
                                    {{ err }}
                                </div>
                            </div>
                            <small class="text-muted mt-1 d-block">
                                Perbaiki baris di atas di file Excel-mu, lalu upload ulang.
                                Yg sudah ter-import tidak akan duplikat (re-import = update by kode_barang).
                            </small>
                        </div>
                    </div>

                    <!-- VALIDATION ERROR FORM (mis. file invalid format) -->
                    <div v-if="form.errors.file" class="alert alert-danger">
                        <i class="bx bx-error-circle"></i> {{ form.errors.file }}
                    </div>

                    <div class="alert alert-info">
                        <strong>Format kolom Excel</strong> (baris 1 = header):
                        <table class="table table-sm mt-2 mb-0 bg-white">
                            <thead class="table-light">
                                <tr>
                                    <th>Kol</th><th>Nama</th><th>Wajib</th><th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>A</td><td>kode_barang</td><td>✓</td><td>Unik. Re-import = update.</td></tr>
                                <tr><td>B</td><td>part_number</td><td></td><td>Opsional</td></tr>
                                <tr><td>C</td><td>nama_barang</td><td>✓</td><td></td></tr>
                                <tr><td>D</td><td>kategori</td><td></td><td>Nama. Auto-buat kalau belum ada.</td></tr>
                                <tr><td>E</td><td>sub_kategori</td><td></td><td>Sama.</td></tr>
                                <tr><td>F</td><td>merk</td><td></td><td>Sama.</td></tr>
                                <tr><td>G</td><td>group</td><td></td><td>Sama.</td></tr>
                                <tr><td>H</td><td>satuan</td><td></td><td>Default 'pcs'</td></tr>
                                <tr><td>I</td><td>harga_beli</td><td></td><td>Angka. Default 0</td></tr>
                                <tr><td>J</td><td>harga_jual</td><td></td><td>Angka. Default 0</td></tr>
                                <tr><td>K</td><td>min_stok</td><td></td><td>Default 0</td></tr>
                                <tr><td>L</td><td>deskripsi</td><td></td><td>Opsional</td></tr>
                            </tbody>
                        </table>
                        <small class="text-muted d-block mt-2">
                            <i class="bx bx-info-circle"></i>
                            Master kategori/sub/merk/group otomatis dibuat dari nama di Excel.
                            Untuk 9000+ baris, proses 10-30 detik — <strong>jangan tutup tab</strong>.
                        </small>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-3">
                            <label class="form-label">File Excel (.xlsx / .xls / .csv) — max 20MB</label>
                            <input type="file" accept=".xlsx,.xls,.csv" class="form-control"
                                @change="pickFile"
                                :class="{ 'is-invalid': form.errors.file }"
                                :disabled="isUploading">
                            <small v-if="fileName" class="text-muted mt-1 d-block">
                                <i class="bx bx-file"></i> {{ fileName }}
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success" :disabled="!form.file || isUploading">
                            <i class="bx bx-upload me-1"></i>
                            {{ isUploading ? 'Memproses... (jangan tutup tab)' : 'Mulai Import' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="showClearModal" title="Reset Data" size="modal-md" @close="showClearModal = false">
        <div class="modal-body">
            <div class="alert alert-danger mb-3 py-2">
                <i class="bx bx-error-circle"></i>
                <strong>Aksi ini tidak bisa di-undo.</strong> Pilih data yg mau dihapus.
            </div>

            <!-- Toggle Hapus Semua -->
            <div class="form-check mb-2 pb-2 border-bottom">
                <input type="checkbox" class="form-check-input" id="clear-all"
                    :checked="allChecked"
                    :indeterminate.prop="someChecked"
                    @change="toggleAll">
                <label class="form-check-label fw-bold" for="clear-all">
                    Hapus Semua
                </label>
            </div>

            <!-- Per-target checkboxes -->
            <div v-for="opt in clearOptions" :key="opt.value" class="form-check mb-2">
                <input type="checkbox" class="form-check-input"
                    :id="`clear-${opt.value}`"
                    :value="opt.value"
                    v-model="clearForm.targets">
                <label class="form-check-label" :for="`clear-${opt.value}`">
                    {{ opt.label }}
                    <small v-if="opt.hint" class="text-muted d-block" style="font-size: 11px;">
                        <i class="bx bx-info-circle"></i> {{ opt.hint }}
                    </small>
                </label>
            </div>

            <div v-if="clearForm.errors.targets" class="text-danger small mt-2">
                {{ clearForm.errors.targets }}
            </div>

            <div class="mt-3">
                <label class="form-label">
                    Ketik <code class="text-danger">HAPUS</code> untuk konfirmasi
                </label>
                <input v-model="clearForm.confirm" type="text" class="form-control"
                    :class="{ 'is-invalid': clearForm.errors.confirm }"
                    placeholder="HAPUS" autocomplete="off">
                <div v-if="clearForm.errors.confirm" class="invalid-feedback">
                    {{ clearForm.errors.confirm }}
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" @click="showClearModal = false" :disabled="clearForm.processing">
                Batal
            </button>
            <button class="btn btn-danger"
                :disabled="clearForm.confirm !== 'HAPUS' || clearForm.targets.length === 0 || clearForm.processing"
                @click="submitClear">
                <i class="bx bx-trash me-1"></i>
                {{ clearForm.processing ? 'Menghapus...' : `Reset (${clearForm.targets.length})` }}
            </button>
        </div>
    </Modal>
</template>
