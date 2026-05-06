<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

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
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
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
                        <div v-if="gudangId" class="d-flex gap-2">
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
                    <div class="row g-2 mb-3 align-items-center">
                        <div class="col-md-4">
                            <input v-model="search" type="text" class="form-control btn-rounded"
                                placeholder="Cari kode / part / nama...">
                        </div>
                        <div class="col-md-3">
                            <select v-model="gudangId" class="form-select">
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">
                                    {{ g.nama_gudang }} ({{ g.kode_gudang }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" v-model="lowOnly" id="lowOnly">
                                <label class="form-check-label" for="lowOnly">Hanya stok ≤ min</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select :value="perPage" class="form-select" @change="changePerPage(+$event.target.value)">
                                <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }} / hal</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap">
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
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in rows.data" :key="row.id">
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
                                <tr v-if="!rows.data.length">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ rows.from ?? 0 }}–{{ rows.to ?? 0 }} dari {{ rows.total }}
                        </small>
                        <Pagination :links="rows.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
