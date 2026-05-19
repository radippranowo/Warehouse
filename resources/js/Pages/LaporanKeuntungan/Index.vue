<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Rupiah from '@/Components/Rupiah.vue';

const props = defineProps({
    items: { type: Object, required: true },
    summary: { type: Object, required: true },
    gudangs: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});


defineOptions({ layout: AppLayout });

const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 10);
const gudangId = ref(props.filters.gudang_id ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');
let timer = null;

function reload(extra = {}) {
    router.get('/laporan-keuntungan', {
        search: search.value,
        per_page: perPage.value,
        gudang_id: gudangId.value,
        start_date: startDate.value,
        end_date: endDate.value,
        ...extra,
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true, 
        only: ['items', 'summary', 'filters'] 
    });
}

watch(search, () => { 
    clearTimeout(timer); 
    timer = setTimeout(reload, 400);
});

watch([gudangId, startDate, endDate], () => reload());

function changePerPage(n) { 
    perPage.value = n; 
    reload(); 
}

function fmtNumber(v) {
    if (v === null || v === undefined || v === '') return '0';
    return Number(v).toLocaleString('id-ID');
}

function fmtDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
}

function getMarginClass(keuntungan, modal) {
    if (modal === 0) return 'text-muted';
    const margin = (keuntungan / modal) * 100;
    if (margin < 10) return 'text-danger';
    if (margin < 20) return 'text-warning';
    return 'text-success';
}
</script>

<template>
    <div class="container-fluid py-3">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1 fw-bold">Laporan Keuntungan Penjualan</h4>
                <p class="text-muted small mb-0">Analisis keuntungan dari barang keluar</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-package text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total Qty Terjual</p>
                                <h5 class="mb-0 fw-bold">{{ fmtNumber(summary.total_qty) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-money text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total Pembelian</p>
                                <h5 class="mb-0 fw-bold">
                                    <Rupiah :value="summary.total_modal" bold />
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-cart text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total Penjualan</p>
                                <h5 class="mb-0 fw-bold">
                                    <Rupiah :value="summary.total_penjualan" bold />
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-trending-up text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total Keuntungan</p>
                                <h5 class="mb-0 fw-bold text-success">
                                    <Rupiah :value="summary.total_keuntungan" bold />
                                </h5>
                                <small class="text-muted">Margin: {{ summary.margin_persen.toFixed(1) }}%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label mb-1 small fw-medium">Cari</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            placeholder="Cari kode/nama barang, nomor mutasi..."
                            v-model="search"
                            style="border-radius: 0.25rem !important;"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small fw-medium">Gudang</label>
                        <select 
                            class="form-select" 
                            v-model="gudangId"
                            style="border-radius: 0.25rem !important;"
                        >
                            <option value="">Semua Gudang</option>
                            <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                {{ g.nama_gudang }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small fw-medium">Dari Tanggal</label>
                        <input 
                            type="date" 
                            class="form-control" 
                            v-model="startDate"
                            style="border-radius: 0.25rem !important;"
                        />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small fw-medium">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            class="form-control" 
                            v-model="endDate"
                            style="border-radius: 0.25rem !important;"
                        />
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button 
                            class="btn btn-outline-secondary w-100"
                            @click="search = ''; gudangId = ''; startDate = ''; endDate = ''; reload();"
                            style="border-radius: 0.25rem !important;"
                        >
                            <i class="bx bx-reset me-1"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-2 small fw-semibold">Tanggal</th>
                                <th class="px-3 py-2 small fw-semibold">No. Mutasi</th>
                                <th class="px-3 py-2 small fw-semibold">Kode Barang</th>
                                <th class="px-3 py-2 small fw-semibold">Nama Barang</th>
                                <th class="px-3 py-2 small fw-semibold">Gudang</th>
                                <th class="px-3 py-2 small fw-semibold text-end">Qty</th>
                                <th class="px-3 py-2 small fw-semibold text-end">Harga Beli</th>
                                <th class="px-3 py-2 small fw-semibold text-end">Harga Jual</th>
                                <th class="px-3 py-2 small fw-semibold text-end">Keuntungan/Unit</th>
                                <th class="px-3 py-2 small fw-semibold text-end">Total Keuntungan</th>
                                <th class="px-3 py-2 small fw-semibold text-center">Margin %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="items.data.length === 0">
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="bx bx-info-circle fs-4 d-block mb-2"></i>
                                    Tidak ada data
                                </td>
                            </tr>
                            <tr v-for="item in items.data" :key="item.id">
                                <td class="px-3 py-2 small">{{ fmtDate(item.tanggal) }}</td>
                                <td class="px-3 py-2 small">
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        {{ item.nomor_mutasi }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 small">{{ item.kode_barang }}</td>
                                <td class="px-3 py-2 small fw-medium">{{ item.nama_barang }}</td>
                                <td class="px-3 py-2 small">{{ item.nama_gudang }}</td>
                                <td class="px-3 py-2 small text-end">{{ fmtNumber(item.qty) }} {{ item.satuan }}</td>
                                <td class="px-3 py-2 small text-end">
                                    <Rupiah :value="item.harga_beli" />
                                </td>
                                <td class="px-3 py-2 small text-end fw-medium">
                                    <Rupiah :value="item.harga_jual_aktual" />
                                </td>
                                <td class="px-3 py-2 small text-end" :class="getMarginClass(item.keuntungan_per_unit, item.harga_beli)">
                                    <Rupiah :value="item.keuntungan_per_unit" />
                                </td>
                                <td class="px-3 py-2 small text-end fw-bold" :class="getMarginClass(item.total_keuntungan, item.total_modal)">
                                    <Rupiah :value="item.total_keuntungan" />
                                </td>
                                <td class="px-3 py-2 small text-center">
                                    <span 
                                        class="badge"
                                        :class="{
                                            'bg-danger': item.harga_beli > 0 && ((item.keuntungan_per_unit / item.harga_beli) * 100) < 10,
                                            'bg-warning': item.harga_beli > 0 && ((item.keuntungan_per_unit / item.harga_beli) * 100) >= 10 && ((item.keuntungan_per_unit / item.harga_beli) * 100) < 20,
                                            'bg-success': item.harga_beli > 0 && ((item.keuntungan_per_unit / item.harga_beli) * 100) >= 20,
                                            'bg-secondary': item.harga_beli === 0
                                        }"
                                    >
                                        {{ item.harga_beli > 0 ? ((item.keuntungan_per_unit / item.harga_beli) * 100).toFixed(1) : '0' }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Per Halaman:</label>
                    <select 
                        class="form-select form-select-sm" 
                        style="width: auto; border-radius: 0.25rem !important;"
                        :value="perPage"
                        @change="changePerPage($event.target.value)"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
                <Pagination :links="items.links" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.avatar-sm {
    width: 3rem;
    height: 3rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}


.badge-soft-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.badge-soft-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.badge-soft-success {
    background-color: rgba(25, 135, 84, 0.1);
    color: #198754;
}
</style>
