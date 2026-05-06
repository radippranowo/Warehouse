<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    mutasis: { type: Object, required: true },
    filters: { type: Object, required: true },
    gudangs: { type: Array, default: () => [] },
});

defineOptions({ layout: AppLayout });

const search    = ref(props.filters.search ?? '');
const perPage   = ref(props.filters.perPage ?? 25);
const tipe      = ref(props.filters.tipe ?? '');
const gudangId  = ref(props.filters.gudang_id ?? '');
let timer = null;

function reload(extra = {}) {
    router.get('/mutasi', {
        search: search.value,
        perPage: perPage.value,
        tipe: tipe.value,
        gudang_id: gudangId.value,
        ...extra,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['mutasis', 'filters'] });
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
watch([tipe, gudangId], () => reload());
function changePerPage(n) { perPage.value = n; reload(); }

const tipeBadge = {
    in:       { class: 'badge-soft-success',   label: 'Masuk' },
    out:      { class: 'badge-soft-danger',    label: 'Keluar' },
    transfer: { class: 'badge-soft-warning',   label: 'Transfer' },
    adjust:   { class: 'badge-soft-secondary', label: 'Adjust' },
};

function fmtRp(v) {
    if (v === null || v === undefined || v === '') return '-';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
}
function fmtDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}

// Detail modal — items sudah eager-loaded di index payload, jadi instant.
const showDetail = ref(false);
const detail = ref(null);

function openDetail(m) {
    detail.value = m;
    showDetail.value = true;
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">RIWAYAT MUTASI</h5>
                        <div class="flex-shrink-0">
                            <Link href="/mutasi/create" class="btn btn-success btn-rounded">
                                <i class="mdi mdi-plus me-1"></i>Tambah Mutasi
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input v-model="search" type="text" class="form-control btn-rounded"
                                placeholder="Cari nomor / referensi / barang...">
                        </div>
                        <div class="col-md-3">
                            <select v-model="tipe" class="form-select">
                                <option value="">Semua Tipe</option>
                                <option value="in">Masuk</option>
                                <option value="out">Keluar</option>
                                <option value="transfer">Transfer</option>
                                <option value="adjust">Adjust</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select v-model="gudangId" class="form-select">
                                <option value="">Semua Gudang</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">{{ g.nama_gudang }}</option>
                            </select>
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
                                    <th>No. Mutasi</th>
                                    <th>Tanggal</th>
                                    <th>Tipe</th>
                                    <th>Gudang</th>
                                    <th>Tujuan</th>
                                    <th class="text-end">Item</th>
                                    <th class="text-end">Total Qty</th>
                                    <th class="text-end">Total Nilai</th>
                                    <th>Referensi</th>
                                    <th>User</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in mutasis.data" :key="m.id">
                                    <td><code>{{ m.nomor_mutasi }}</code></td>
                                    <td>{{ fmtDate(m.tanggal) }}</td>
                                    <td>
                                        <span class="badge rounded-pill" :class="tipeBadge[m.tipe]?.class">
                                            {{ tipeBadge[m.tipe]?.label ?? m.tipe }}
                                        </span>
                                    </td>
                                    <td>{{ m.gudang?.nama_gudang ?? '-' }}</td>
                                    <td>{{ m.gudang_tujuan?.nama_gudang ?? '-' }}</td>
                                    <td class="text-end">{{ m.items_count }}</td>
                                    <td class="text-end">{{ m.total_qty?.toLocaleString('id-ID') }}</td>
                                    <td class="text-end">{{ fmtRp(m.total_value) }}</td>
                                    <td>{{ m.referensi || '-' }}</td>
                                    <td>{{ m.user?.name ?? '-' }}</td>
                                    <td class="text-nowrap">
                                        <button class="btn btn-sm btn-soft-primary border-0 shadow-sm bx bx-show font-size-16"
                                            @click="openDetail(m)" title="Detail"></button>
                                        <a :href="`/mutasi/${m.id}/print`" target="_blank"
                                            class="btn btn-sm btn-soft-secondary border-0 shadow-sm bx bx-printer font-size-16"
                                            title="Cetak Surat Jalan"></a>
                                    </td>
                                </tr>
                                <tr v-if="!mutasis.data.length">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data</td>
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

    <Modal :show="showDetail" :title="detail ? `Detail ${detail.nomor_mutasi}` : 'Detail Mutasi'" size="modal-lg" @close="showDetail = false">
        <div class="modal-body">
            <div v-if="detail">
                <div class="row mb-3">
                    <div class="col-md-3"><small class="text-muted">Tanggal</small><div>{{ fmtDate(detail.tanggal) }}</div></div>
                    <div class="col-md-2"><small class="text-muted">Tipe</small>
                        <div><span class="badge rounded-pill" :class="tipeBadge[detail.tipe]?.class">
                            {{ tipeBadge[detail.tipe]?.label }}
                        </span></div>
                    </div>
                    <div class="col-md-3"><small class="text-muted">Gudang</small><div>{{ detail.gudang?.nama_gudang }}</div></div>
                    <div class="col-md-3"><small class="text-muted">Tujuan</small><div>{{ detail.gudang_tujuan?.nama_gudang ?? '-' }}</div></div>
                    <div class="col-md-3 mt-2"><small class="text-muted">Referensi</small><div>{{ detail.referensi ?? '-' }}</div></div>
                    <div class="col-md-3 mt-2"><small class="text-muted">User</small><div>{{ detail.user?.name ?? '-' }}</div></div>
                    <div class="col-md-6 mt-2" v-if="detail.keterangan"><small class="text-muted">Keterangan</small><div>{{ detail.keterangan }}</div></div>
                </div>

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Barang</th>
                            <th class="text-end">Qty</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                            <th>Stok (Sebelum → Sesudah)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(it, i) in detail.items" :key="it.id">
                            <td>{{ i + 1 }}</td>
                            <td>
                                <small class="text-muted">{{ it.barang?.kode_barang }}</small><br>
                                {{ it.barang?.nama_barang }}
                            </td>
                            <td class="text-end">{{ it.qty?.toLocaleString('id-ID') }}</td>
                            <td>{{ it.barang?.satuan }}</td>
                            <td class="text-end">{{ fmtRp(it.harga_satuan) }}</td>
                            <td class="text-end">{{ fmtRp((it.qty || 0) * (it.harga_satuan || 0)) }}</td>
                            <td><small>{{ it.stok_sebelum }} → <strong>{{ it.stok_sesudah }}</strong></small></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Total</th>
                            <th class="text-end">{{ detail.total_qty?.toLocaleString('id-ID') }}</th>
                            <th colspan="2"></th>
                            <th class="text-end">{{ fmtRp(detail.total_value) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <a v-if="detail" :href="`/mutasi/${detail.id}/print`" target="_blank" class="btn btn-primary">
                <i class="bx bx-printer me-1"></i>Cetak Surat Jalan
            </a>
            <button class="btn btn-light" @click="showDetail = false">Tutup</button>
        </div>
    </Modal>
</template>
