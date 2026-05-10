<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    mutasis: { type: Object, required: true },
    filters: { type: Object, required: true },
    gudangs: { type: Array, default: () => [] },
});

defineOptions({ layout: AppLayout });

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
const gudangId = ref(props.filters.gudang_id ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

// Modal pembatalan
const showCancelModal = ref(false);
const cancelMutasi = ref(null);
const cancelReason = ref('');
const cancelReasonError = ref('');

let timer = null;

function reload() {
    router.get('/riwayat/barang-masuk', {
        search: search.value,
        perPage: perPage.value,
        gudang_id: gudangId.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['mutasis', 'filters'],
    });
}

watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
watch([perPage, gudangId, dateFrom, dateTo], reload);

function changePerPage(n) { perPage.value = n; }

function fmtRpNumber(v) {
    if (v === null || v === undefined || v === '') return '0';
    return Number(v).toLocaleString('id-ID');
}

function viewDetail(mutasi) {
    router.get(`/transaksi/${mutasi.id}`);
}

function printMutasi(mutasi) {
    window.open(`/transaksi/${mutasi.id}/print`, '_blank');
}

function printList() {
    const params = new URLSearchParams({
        tipe: 'in',
        gudang_id: gudangId.value || '',
        date_from: dateFrom.value || '',
        date_to: dateTo.value || '',
        search: search.value || '',
    });
    window.open(`/riwayat/print?${params.toString()}`, '_blank');
}

function resetFilters() {
    search.value = '';
    gudangId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

function openCancelModal(mutasi) {
    cancelMutasi.value = mutasi;
    cancelReason.value = '';
    cancelReasonError.value = '';
    showCancelModal.value = true;
}

function closeCancelModal() {
    showCancelModal.value = false;
    cancelMutasi.value = null;
    cancelReason.value = '';
    cancelReasonError.value = '';
}

function submitCancel() {
    // Validasi alasan
    const reason = cancelReason.value.trim();
    if (reason.length < 10) {
        cancelReasonError.value = 'Alasan pembatalan minimal 10 karakter';
        return;
    }

    const mutasi = cancelMutasi.value;
    router.delete(`/transaksi/${mutasi.id}`, {
        data: { cancellation_reason: reason },
        preserveScroll: true,
        onSuccess: () => {
            closeCancelModal();
            window.toast?.success('Transaksi berhasil dibatalkan');
        },
        onError: (errors) => {
            if (errors.cancellation_reason) {
                cancelReasonError.value = errors.cancellation_reason;
            } else {
                window.toast?.error('Gagal membatalkan transaksi');
            }
        },
    });
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <h5 class="mb-0 card-title">RIWAYAT BARANG MASUK</h5>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_pemasukan" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari nomor / supplier..." style="padding-left: 36px; height: 38px;" aria-label="Cari transaksi pemasukan">
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label mb-1 small fw-medium">Gudang</label>
                            <select id="gudang_filter_pemasukan" name="gudang_id" v-model="gudangId" class="form-select" style="height: 38px;" aria-label="Filter gudang">
                                <option value="">Semua</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Dari Tanggal</label>
                            <input id="date_from_pemasukan" name="date_from" v-model="dateFrom" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal dari">
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Sampai Tanggal</label>
                            <input id="date_to_pemasukan" name="date_to" v-model="dateTo" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal sampai">
                        </div>
                        <div class="col-lg-auto col-md-auto ms-auto">
                            <label class="form-label mb-1 small fw-medium d-block">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-warning" @click="resetFilters" title="Reset Filter" style="width: 38px; height: 38px; padding: 0;">
                                    <i class="bx bx-rotate-left" style="font-size: 18px;"></i>
                                </button>
                                <button class="btn btn-primary" @click="printList" title="Cetak Semua" style="width: 38px; height: 38px; padding: 0;">
                                    <i class="bx bx-printer" style="font-size: 18px;"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Tanggal</th>
                                    <th>Nomor</th>
                                    <th>Supplier</th>
                                    <th>Gudang</th>
                                    <th>Items</th>
                                    <th>Total Qty</th>
                                    <th>Total Harga Modal</th>
                                    <th>Dibuat Oleh</th>
                                    <th style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in mutasis.data" :key="item.id">
                                    <td>{{ (mutasis.current_page - 1) * mutasis.per_page + i + 1 }}</td>
                                    <td>{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                                    <td>
                                        <strong>{{ item.nomor_mutasi }}</strong>
                                        <br><small class="text-muted">{{ item.referensi || '-' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ item.supplier?.nama_supplier || '-' }}</strong>
                                        <br><small class="text-muted">{{ item.supplier?.kode_supplier || '' }}</small>
                                    </td>
                                    <td>{{ item.gudang?.nama_gudang || '-' }}</td>
                                    <td><span class="badge bg-primary">{{ item.items_count }}</span></td>
                                    <td>{{ item.total_qty?.toLocaleString('id-ID') || 0 }}</td>
                                    <td><strong><span class="rupiah-format"><span class="rp">Rp</span><span class="amount">{{ fmtRpNumber(item.total_value) }}</span></span></strong></td>
                                    <td>{{ item.user?.name || '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-show font-size-16"
                                            @click="viewDetail(item)" title="Detail"></button>
                                        <button class="btn btn-sm btn-soft-secondary border-0 shadow-sm bx bx-printer font-size-16 ms-1"
                                            @click="printMutasi(item)" title="Print"></button>
                                        <button class="btn btn-sm btn-soft-danger border-0 shadow-sm bx bx-x-circle font-size-16 ms-1"
                                            @click="openCancelModal(item)" title="Batalkan Transaksi"></button>
                                    </td>
                                </tr>
                                <tr v-if="!mutasis.data.length">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data pemasukan</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ mutasis.from ?? 0 }}–{{ mutasis.to ?? 0 }} dari {{ mutasis.total }}
                            </small>
                            <select id="per_page_pemasukan" name="per_page" v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        <Pagination :links="mutasis.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pembatalan -->
    <div v-if="showCancelModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Batalkan Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeCancelModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Perhatian:</strong> Transaksi akan dibatalkan dan stok akan disesuaikan kembali. 
                        Data tetap tersimpan untuk audit.
                    </div>
                    
                    <div v-if="cancelMutasi" class="mb-3">
                        <p class="mb-1"><strong>Nomor:</strong> {{ cancelMutasi.nomor_mutasi }}</p>
                        <p class="mb-1"><strong>Tanggal:</strong> {{ new Date(cancelMutasi.tanggal).toLocaleDateString('id-ID') }}</p>
                        <p class="mb-0"><strong>Supplier:</strong> {{ cancelMutasi.supplier?.nama_supplier || '-' }}</p>
                    </div>

                    <div class="mb-3">
                        <label for="cancel_reason_pemasukan" class="form-label">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea 
                            id="cancel_reason_pemasukan"
                            name="cancel_reason"
                            v-model="cancelReason" 
                            class="form-control" 
                            :class="{ 'is-invalid': cancelReasonError }"
                            rows="4" 
                            placeholder="Jelaskan alasan pembatalan transaksi ini (minimal 10 karakter)..."
                            @input="cancelReasonError = ''"
                        ></textarea>
                        <div v-if="cancelReasonError" class="invalid-feedback d-block">
                            {{ cancelReasonError }}
                        </div>
                        <small class="text-muted">{{ cancelReason.length }} karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeCancelModal">Batal</button>
                    <button type="button" class="btn btn-danger" @click="submitCancel">
                        <i class="bx bx-x-circle me-1"></i> Batalkan Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}

.rupiah-format {
    display: inline-flex;
    justify-content: space-between;
    width: 100%;
    gap: 0.5rem;
}

.rupiah-format .rp {
    text-align: left;
    flex-shrink: 0;
}

.rupiah-format .amount {
    text-align: right;
    flex-grow: 1;
}
</style>
