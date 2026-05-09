<script setup>
import { ref, watch, computed } from 'vue';
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
const status = ref(props.filters.status ?? '');
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

let timer = null;

function reload() {
    router.get('/riwayat/semua', {
        search: search.value,
        perPage: perPage.value,
        gudang_id: gudangId.value,
        status: status.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['mutasis', 'filters'],
    });
}

watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
watch([perPage, gudangId, status, dateFrom, dateTo], reload);

function changePerPage(n) { perPage.value = n; }

function resetFilters() {
    search.value = '';
    gudangId.value = '';
    status.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

function printList() {
    const params = new URLSearchParams({
        tipe: 'all',
        gudang_id: gudangId.value || '',
        status: status.value || '',
        date_from: dateFrom.value || '',
        date_to: dateTo.value || '',
        search: search.value || '',
    });
    window.open(`/riwayat/print?${params.toString()}`, '_blank');
}

function getTipeBadge(tipe) {
    const badges = {
        in: 'badge-soft-success',
        out: 'badge-soft-danger',
        transfer: 'badge-soft-info',
        adjust: 'badge-soft-warning',
    };
    return badges[tipe] || 'badge-soft-secondary';
}

function getTipeLabel(tipe) {
    const labels = {
        in: 'Pemasukan',
        out: 'Pengeluaran',
        transfer: 'Transfer',
        adjust: 'Penyesuaian',
    };
    return labels[tipe] || tipe;
}

function getStatusBadge(status) {
    const badges = {
        pending: 'badge-soft-warning',
        approved: 'badge-soft-success',
        rejected: 'badge-soft-danger',
    };
    return badges[status] || 'badge-soft-secondary';
}

function viewDetail(mutasi) {
    router.get(`/transaksi/${mutasi.id}`);
}

function printMutasi(mutasi) {
    window.open(`/transaksi/${mutasi.id}/print`, '_blank');
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <h5 class="mb-0 card-title">RIWAYAT SEMUA MUTASI</h5>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_semua" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari nomor..." style="padding-left: 36px; height: 38px;" aria-label="Cari semua transaksi">
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Gudang</label>
                            <select id="gudang_filter_semua" name="gudang_id" v-model="gudangId" class="form-select" style="height: 38px;" aria-label="Filter gudang">
                                <option value="">Semua</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Status</label>
                            <select id="status_filter_semua" name="status" v-model="status" class="form-select" style="height: 38px;" aria-label="Filter status">
                                <option value="">Semua</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Dari Tanggal</label>
                            <input id="date_from_semua" name="date_from" v-model="dateFrom" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal dari">
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Sampai Tanggal</label>
                            <input id="date_to_semua" name="date_to" v-model="dateTo" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal sampai">
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
                                    <th>Tipe</th>
                                    <th>Gudang</th>
                                    <th>Supplier</th>
                                    <th>Items</th>
                                    <th>Total Qty</th>
                                    <th>Total Value</th>
                                    <th>Status</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in mutasis.data" :key="item.id" 
                                    :class="{ 'table-secondary': item.cancelled_at }">
                                    <td>{{ (mutasis.current_page - 1) * mutasis.per_page + i + 1 }}</td>
                                    <td>{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                                    <td>
                                        <strong>{{ item.nomor_mutasi }}</strong>
                                        <br><small class="text-muted">{{ item.referensi || '-' }}</small>
                                        <div v-if="item.cancelled_at" class="mt-1">
                                            <span class="badge bg-danger">DIBATALKAN</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill" :class="getTipeBadge(item.tipe)">
                                            {{ getTipeLabel(item.tipe) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div v-if="item.tipe === 'transfer'">
                                            <small class="text-muted">Dari:</small> {{ item.gudang?.nama_gudang || '-' }}<br>
                                            <small class="text-muted">Ke:</small> {{ item.gudang_tujuan?.nama_gudang || '-' }}
                                        </div>
                                        <div v-else>
                                            {{ item.gudang?.nama_gudang || '-' }}
                                        </div>
                                    </td>
                                    <td>{{ item.supplier?.nama_supplier || '-' }}</td>
                                    <td>{{ item.items_count }}</td>
                                    <td>{{ item.total_qty?.toLocaleString('id-ID') || 0 }}</td>
                                    <td>Rp {{ (item.total_value || 0).toLocaleString('id-ID') }}</td>
                                    <td>
                                        <span v-if="!item.cancelled_at" class="badge rounded-pill" :class="getStatusBadge(item.status)">
                                            {{ item.status }}
                                        </span>
                                        <span v-else class="badge bg-secondary">cancelled</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-show font-size-16"
                                            @click="viewDetail(item)" title="Detail"></button>
                                        <button class="btn btn-sm btn-soft-secondary border-0 shadow-sm bx bx-printer font-size-16 ms-1"
                                            @click="printMutasi(item)" title="Print"></button>
                                    </td>
                                </tr>
                                <tr v-if="!mutasis.data.length">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ mutasis.from ?? 0 }}–{{ mutasis.to ?? 0 }} dari {{ mutasis.total }}
                            </small>
                            <select id="per_page_semua" name="per_page" v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
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
</template>
