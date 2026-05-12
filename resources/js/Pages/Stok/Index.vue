<script setup>
import { ref, watch, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { usePartialReloadLoading } from '@/composables/usePartialReloadLoading';

const { loading } = usePartialReloadLoading('/stok');

const props = defineProps({
    rows: { type: Object, required: true },
    gudangs: { type: Array, default: () => [] },
    gudang: { type: Object, default: null },
    summary: { type: Object, required: true },
    filters: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
const skeletonRows = computed(() => Math.min(Number(perPage.value) || 10, 10));
const gudangId = ref(props.filters.gudang_id ?? '');
const lowOnly = ref(props.filters.low_only ?? false);
let timer = null;

function reload(extra = {}) {
    router.get('/stok', {
        search: search.value,
        perPage: perPage.value,
        gudang_id: gudangId.value,
        low_only: lowOnly.value ? 1 : 0,
        ...extra,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['rows', 'summary', 'gudang', 'filters'] });
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
watch([gudangId, lowOnly], () => reload());
function changePerPage(n) { perPage.value = n; reload(); }

function fmtRp(v) {
    if (v === null || v === undefined || v === '') return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
}
function statusOf(row) {
    const stok = Number(row.stok_gudang) || 0;
    const min = Number(row.min_stok_efektif) || 0;
    if (stok === 0) return { class: 'badge-soft-danger', label: 'Kosong' };
    if (stok <= min) return { class: 'badge-soft-warning', label: 'Di bawah min' };
    return { class: 'badge-soft-success', label: 'Aman' };
}

// === IMPORT EXCEL (kontekstual ke gudang yg sedang difilter) ===
const showImportModal = ref(false);
const importForm = useForm({ gudang_id: '', file: null });
const importResult = ref(null);

const filteredGudang = computed(() =>
    props.gudangs.find(g => g.id === Number(gudangId.value))
);

function openImport() {
    if (!gudangId.value) {
        window.toast?.error('Pilih gudang dulu di filter atas — import butuh tau gudang tujuan.');
        return;
    }
    importForm.reset();
    importForm.gudang_id = gudangId.value;
    importForm.clearErrors();
    importResult.value = null;
    showImportModal.value = true;
}
function pickImportFile(e) {
    importForm.file = e.target.files[0] ?? null;
    importForm.clearErrors('file');
}
function submitImport() {
    if (!importForm.file) { window.toast?.error('Pilih file Excel dulu.'); return; }
    importForm.post('/stok/import', {
        forceFormData: true,
        preserveScroll: true,
        // Server return back() ke /stok → row & summary auto-refresh dari response.
        // Tidak perlu reload() manual.
        onSuccess: (page) => {
            const r = page.props?.flash?.import_result;
            importResult.value = r ?? null;
            const status = r?.status;
            if (status === 'success') {
                window.toast?.success(r.message);
                showImportModal.value = false;
                router.flushAll();
            } else if (status === 'partial') {
                window.toast?.info(r.message);
                router.flushAll();
            } else if (status === 'error') {
                window.toast?.error(r.message);
            }
        },
    });
}

// === RESET STOK (per gudang yg lagi difilter) ===
const showClearModal = ref(false);
const clearForm = useForm({ confirm: '', gudang_id: '' });

function openClear() {
    clearForm.reset();
    clearForm.gudang_id = gudangId.value || '';
    clearForm.clearErrors();
    showClearModal.value = true;
}
function submitClear() {
    clearForm.post('/stok/clear', {
        preserveScroll: true,
        onSuccess: (page) => {
            window.toast?.success(page.props?.flash?.import_result?.message ?? 'Stok direset');
            showClearModal.value = false;
            router.flushAll();
        },
        onError: () => window.toast?.error('Reset gagal — cek konfirmasi'),
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
                            STOK PER GUDANG
                            <small v-if="gudang" class="text-muted ms-2">— {{ gudang.nama_gudang }}</small>
                        </h5>
                        <div v-if="gudangId" class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-info btn-rounded" @click="openImport">
                                <i class="bx bx-upload me-1"></i>Import Stok
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-rounded" @click="openClear">
                                <i class="bx bx-trash me-1"></i>Reset
                            </button>
                            <a :href="`/stok?print=1&gudang_id=${gudangId}&search=${encodeURIComponent(search)}&low_only=${lowOnly ? 1 : 0}`"
                                target="_blank"
                                class="btn btn-outline-primary btn-rounded">
                                <i class="bx bx-printer me-1"></i>Cetak / PDF
                            </a>
                            <a :href="`/stok?export=1&gudang_id=${gudangId}&search=${encodeURIComponent(search)}&low_only=${lowOnly ? 1 : 0}`"
                                class="btn btn-outline-success btn-rounded">
                                <i class="bx bx-download me-1"></i>Export Excel
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light border-0 mb-0">
                                <div class="card-body py-2">
                                    <small class="text-muted">Total SKU</small>
                                    <h4 class="mb-0">{{ summary.total_sku.toLocaleString('id-ID') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 mb-0">
                                <div class="card-body py-2">
                                    <small class="text-muted">Stok Kosong</small>
                                    <h4 class="mb-0 text-danger">{{ summary.zero_count.toLocaleString('id-ID') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 mb-0">
                                <div class="card-body py-2">
                                    <small class="text-muted">Di Bawah Min</small>
                                    <h4 class="mb-0 text-warning">{{ summary.low_count.toLocaleString('id-ID') }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0 mb-0">
                                <div class="card-body py-2">
                                    <small class="text-muted">Nilai Stok (harga jual)</small>
                                    <h4 class="mb-0 text-primary">{{ fmtRp(summary.total_value) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row g-2 mb-3">
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_stok" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari kode / part / nama..." style="padding-left: 36px; height: 38px;" aria-label="Cari stok barang">
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3">
                            <label class="form-label mb-1 small fw-medium">Gudang</label>
                            <select id="gudang_filter_stok" name="gudang_id" v-model="gudangId" class="form-select" style="height: 38px;" aria-label="Filter gudang">
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }} ({{ g.kode_gudang }})
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-auto col-md-auto ms-auto">
                            <label class="form-label mb-1 small fw-medium d-block">&nbsp;</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" v-model="lowOnly" id="lowOnly">
                                <label class="form-check-label" for="lowOnly">Hanya stok ≤ min</label>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Part Number</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Merk</th>
                                    <th>Satuan</th>
                                    <th class="text-end">Stok</th>
                                    <th class="text-end">Min</th>
                                    <th>Status</th>
                                    <th class="text-start">Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="loading">
                                    <tr v-for="n in skeletonRows" :key="`skel-${n}`" class="skeleton-row">
                                        <td><span class="skel" style="width: 70px;"></span></td>
                                        <td><span class="skel" style="width: 80px;"></span></td>
                                        <td><span class="skel" style="width: 160px;"></span></td>
                                        <td><span class="skel" style="width: 90px;"></span></td>
                                        <td><span class="skel" style="width: 80px;"></span></td>
                                        <td><span class="skel skel-sm" style="width: 40px;"></span></td>
                                        <td class="text-end"><span class="skel" style="width: 40px;"></span></td>
                                        <td class="text-end"><span class="skel skel-sm" style="width: 30px;"></span></td>
                                        <td><span class="skel skel-pill" style="width: 70px;"></span></td>
                                        <td><span class="skel" style="width: 90px;"></span></td>
                                        <td><span class="skel skel-sm" style="width: 28px; height: 28px; border-radius: 4px;"></span></td>
                                    </tr>
                                </template>
                                <tr v-else v-for="row in rows.data" :key="row.id">
                                    <td><code>{{ row.kode_barang }}</code></td>
                                    <td>{{ row.part_number || '-' }}</td>
                                    <td>{{ row.nama_barang }}</td>
                                    <td>{{ row.kategori?.nama_category }}</td>
                                    <td>{{ row.merk?.nama_merk }}</td>
                                    <td>{{ row.satuan }}</td>
                                    <td class="text-end">
                                        <strong>{{ Number(row.stok_gudang).toLocaleString('id-ID') }}</strong>
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ Number(row.min_stok_efektif).toLocaleString('id-ID') }}
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill" :class="statusOf(row).class">
                                            {{ statusOf(row).label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <span>Rp</span>
                                            <span class="ms-2">
                                                {{ (row.stok_gudang * row.harga_jual).toLocaleString('id-ID') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <Link :href="`/stok/${row.id}`"
                                            class="btn btn-sm btn-soft-primary border-0 shadow-sm bx bx-show font-size-16"
                                            title="Detail Barang"></Link>
                                    </td>
                                </tr>
                                <tr v-if="!loading && !rows.data.length">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ rows.from ?? 0 }}–{{ rows.to ?? 0 }} dari {{ rows.total }}
                            </small>
                            <select id="per_page_stok" name="per_page" :value="perPage" @change="changePerPage(+$event.target.value)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
                                <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }}</option>
                            </select>
                        </div>
                        <Pagination :links="rows.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="showImportModal" :title="`Import Stok ke ${filteredGudang?.nama_gudang ?? ''}`" size="modal-md" @close="showImportModal = false">
        <div class="modal-body">
            <div class="alert alert-info py-2 mb-3">
                <i class="bx bx-info-circle"></i>
                Stok di Excel akan <strong>ditambahkan</strong> ke stok existing di gudang
                <strong>{{ filteredGudang?.nama_gudang }}</strong>.
                Re-import file yg sama = stok akan dobel.
            </div>

            <div class="alert alert-light border py-2 mb-3" style="font-size: 12px;">
                <strong>Format Excel</strong> (baris 1 = header):
                <table class="table table-sm mb-0 mt-1">
                    <tbody>
                        <tr><td>A</td><td><code>kode_barang</code></td><td>Harus sudah ada di master</td></tr>
                        <tr><td>B</td><td><code>stok</code></td><td>Angka ≥ 0</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label">File Excel (.xlsx / .xls / .csv)</label>
                <input type="file" accept=".xlsx,.xls,.csv" class="form-control"
                    @change="pickImportFile"
                    :class="{ 'is-invalid': importForm.errors.file }"
                    :disabled="importForm.processing">
                <div v-if="importForm.errors.file" class="invalid-feedback">
                    {{ importForm.errors.file }}
                </div>
            </div>

            <div v-if="importResult?.errors?.length" class="alert alert-warning py-2 mt-2">
                <strong>Detail issue:</strong>
                <div class="bg-white border rounded p-2 mt-1"
                    style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 11px;">
                    <div v-for="(err, i) in importResult.errors" :key="i" class="text-danger">
                        {{ err }}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" @click="showImportModal = false" :disabled="importForm.processing">Batal</button>
            <button class="btn btn-success" :disabled="!importForm.file || importForm.processing" @click="submitImport">
                <i class="bx bx-upload me-1"></i>
                {{ importForm.processing ? 'Memproses...' : 'Mulai Import' }}
            </button>
        </div>
    </Modal>

    <Modal :show="showClearModal" title="Reset Stok" size="modal-md" @close="showClearModal = false">
        <div class="modal-body">
            <div class="alert alert-danger py-2 mb-3">
                <i class="bx bx-error-circle"></i>
                Aksi ini tidak bisa di-undo. Akan menghapus stok di:
                <strong>{{ filteredGudang?.nama_gudang ?? 'SEMUA gudang' }}</strong>
            </div>
            <div class="mb-2">
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
            <button class="btn btn-light" @click="showClearModal = false" :disabled="clearForm.processing">Batal</button>
            <button class="btn btn-danger" :disabled="clearForm.confirm !== 'HAPUS' || clearForm.processing" @click="submitClear">
                <i class="bx bx-trash me-1"></i>
                {{ clearForm.processing ? 'Menghapus...' : 'Reset Sekarang' }}
            </button>
        </div>
    </Modal>
</template>

<style scoped>
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}
</style>
