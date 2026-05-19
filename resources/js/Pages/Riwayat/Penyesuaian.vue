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
const dateFrom = ref(props.filters.date_from ?? '');
const dateTo = ref(props.filters.date_to ?? '');

let timer = null;

function reload() {
    router.get('/riwayat/penyesuaian', {
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

watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
watch([perPage, gudangId, dateFrom, dateTo], reload);

function resetFilters() {
    search.value = '';
    gudangId.value = '';
    dateFrom.value = '';
    dateTo.value = '';
}

function printList() {
    const params = new URLSearchParams({
        tipe: 'adjust',
        gudang_id: gudangId.value || '',
        date_from: dateFrom.value || '',
        date_to: dateTo.value || '',
        search: search.value || '',
    });
    window.open(`/riwayat/print?${params.toString()}`, '_blank');
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
                    <h5 class="mb-0 card-title">RIWAYAT PENYESUAIAN STOK</h5>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_penyesuaian" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari nomor / referensi..." style="padding-left: 36px; height: 38px;" aria-label="Cari transaksi penyesuaian">
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <label class="form-label mb-1 small fw-medium">Gudang</label>
                            <select id="gudang_filter_penyesuaian" name="gudang_id" v-model="gudangId" class="form-select" style="height: 38px;" aria-label="Filter gudang">
                                <option value="">Semua</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }}
                                </option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Dari Tanggal</label>
                            <input id="date_from_penyesuaian" name="date_from" v-model="dateFrom" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal dari">
                        </div>
                        <div class="col-lg-2 col-md-2">
                            <label class="form-label mb-1 small fw-medium">Sampai Tanggal</label>
                            <input id="date_to_penyesuaian" name="date_to" v-model="dateTo" type="date" class="form-control" style="height: 38px;" aria-label="Tanggal sampai">
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
                                    <th style="width: 3%;" class="text-center">No</th>
                                    <th style="width: 10%;">Tanggal</th>
                                    <th style="width: 15%;">Nomor</th>
                                    <th style="width: 15%;">Gudang</th>
                                    <th style="width: 8%;" class="text-center">Items</th>
                                    <th style="width: 10%;" class="text-end">Total Qty</th>
                                    <th style="width: 20%;">Keterangan</th>
                                    <th style="width: 12%;">Dibuat Oleh</th>
                                    <th style="width: 7%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in mutasis.data" :key="item.id">
                                    <td class="text-center">{{ (mutasis.current_page - 1) * mutasis.per_page + i + 1 }}</td>
                                    <td>{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                                    <td>
                                        <strong>{{ item.nomor_mutasi }}</strong>
                                        <br><small class="text-muted">{{ item.referensi || '-' }}</small>
                                    </td>
                                    <td>{{ item.gudang?.nama_gudang || '-' }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ item.items_count }}</span></td>
                                    <td class="text-end">{{ item.total_qty?.toLocaleString('id-ID') || 0 }}</td>
                                    <td class="text-truncate" style="max-width: 200px;">{{ item.keterangan || '-' }}</td>
                                    <td>{{ item.user?.name || '-' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-show font-size-16"
                                            @click="viewDetail(item)" title="Detail"></button>
                                        <button class="btn btn-sm btn-soft-secondary border-0 shadow-sm bx bx-printer font-size-16 ms-1"
                                            @click="printMutasi(item)" title="Print"></button>
                                    </td>
                                </tr>
                                <tr v-if="!mutasis.data.length">
                                    <td colspan="9" class="text-center text-muted py-4">Tidak ada data penyesuaian</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ mutasis.from ?? 0 }}–{{ mutasis.to ?? 0 }} dari {{ mutasis.total }}
                            </small>
                            <select id="per_page_penyesuaian" name="per_page" v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
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

<style scoped>
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}
</style>
