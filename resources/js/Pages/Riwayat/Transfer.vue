<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { usePartialReloadLoading } from '@/composables/usePartialReloadLoading';

const { loading } = usePartialReloadLoading('/riwayat/transfer');

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
const skeletonRows = computed(() => Math.min(Number(perPage.value) || 10, 10));

let timer = null;

function reload() {
    router.get('/riwayat/transfer', {
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

watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
watch([perPage, gudangId, status, dateFrom, dateTo], reload);

function changePerPage(n) { perPage.value = n; }

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
                    <h5 class="mb-0 card-title">RIWAYAT TRANSFER BARANG</h5>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-2 mb-2">
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_transfer" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari nomor..." style="padding-left: 40px;" aria-label="Cari transaksi transfer">
                                    <i class="bx bx-search-alt search-icon" style="left: 13px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select id="gudang_filter_transfer" name="gudang_id" v-model="gudangId" class="form-select btn-rounded" aria-label="Filter gudang">
                                <option value="">Semua Gudang</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select id="status_filter_transfer" name="status" v-model="status" class="form-select btn-rounded" aria-label="Filter status">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input id="date_from_transfer" name="date_from" v-model="dateFrom" type="date" class="form-control" aria-label="Tanggal mulai">
                        </div>
                        <div class="col-md-2 mb-2">
                            <input id="date_to_transfer" name="date_to" v-model="dateTo" type="date" class="form-control" aria-label="Tanggal akhir">
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="dropdown">
                                <button class="btn btn-light btn-rounded shadow-sm border dropdown-toggle w-100"
                                    type="button" data-bs-toggle="dropdown">
                                    {{ perPage }}
                                </button>
                                <ul class="dropdown-menu shadow rounded-4 border-0 mt-2">
                                    <li v-for="n in [10, 25, 50, 100]" :key="n">
                                        <a class="dropdown-item rounded-3" href="javascript:void(0);"
                                            @click="changePerPage(n)">{{ n }}</a>
                                    </li>
                                </ul>
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
                                    <th>Dari Gudang</th>
                                    <th>Ke Gudang</th>
                                    <th>Items</th>
                                    <th>Total Qty</th>
                                    <th>Status</th>
                                    <th>Dibuat Oleh</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="loading">
                                    <tr v-for="n in skeletonRows" :key="`skel-${n}`" class="skeleton-row">
                                        <td><span class="skel skel-sm" style="width: 24px;"></span></td>
                                        <td><span class="skel" style="width: 80px;"></span></td>
                                        <td><span class="skel" style="width: 110px;"></span><br><span class="skel skel-sm mt-1" style="width: 70px;"></span></td>
                                        <td><span class="skel" style="width: 110px;"></span></td>
                                        <td><span class="skel" style="width: 110px;"></span></td>
                                        <td><span class="skel skel-pill" style="width: 30px;"></span></td>
                                        <td><span class="skel" style="width: 50px;"></span></td>
                                        <td><span class="skel skel-pill" style="width: 70px;"></span></td>
                                        <td><span class="skel" style="width: 100px;"></span></td>
                                        <td>
                                            <span class="skel skel-sm" style="width: 28px; height: 28px; border-radius: 4px;"></span>
                                            <span class="skel skel-sm ms-1" style="width: 28px; height: 28px; border-radius: 4px;"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else v-for="(item, i) in mutasis.data" :key="item.id">
                                    <td>{{ (mutasis.current_page - 1) * mutasis.per_page + i + 1 }}</td>
                                    <td>{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                                    <td>
                                        <strong>{{ item.nomor_mutasi }}</strong>
                                        <br><small class="text-muted">{{ item.referensi || '-' }}</small>
                                    </td>
                                    <td>{{ item.gudang?.nama_gudang || '-' }}</td>
                                    <td>{{ item.gudang_tujuan?.nama_gudang || '-' }}</td>
                                    <td><span class="badge bg-primary">{{ item.items_count }}</span></td>
                                    <td>{{ item.total_qty?.toLocaleString('id-ID') || 0 }}</td>
                                    <td>
                                        <span class="badge rounded-pill" :class="getStatusBadge(item.status)">
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td>{{ item.user?.name || '-' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-show font-size-16"
                                            @click="viewDetail(item)" title="Detail"></button>
                                        <button class="btn btn-sm btn-soft-secondary border-0 shadow-sm bx bx-printer font-size-16 ms-1"
                                            @click="printMutasi(item)" title="Print"></button>
                                    </td>
                                </tr>
                                <tr v-if="!loading && !mutasis.data.length">
                                    <td colspan="10" class="text-center text-muted py-4">Tidak ada data transfer</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ mutasis.from ?? 0 }}–{{ mutasis.to ?? 0 }} dari {{ mutasis.total }}
                        </small>
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
