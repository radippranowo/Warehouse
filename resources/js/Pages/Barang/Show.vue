<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    barang:   { type: Object, required: true },
    mutasis:  { type: Array,  default: () => [] },
    back_url: { type: String, default: '/barang' },
});

const totalStok = computed(() =>
    (props.barang.stoks ?? []).reduce((s, x) => s + (Number(x.stok) || 0), 0)
);

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
</script>

<template>
    <div class="card shadow-sm mb-3">
        <div class="card-body border-bottom d-flex justify-content-between">
            <h5 class="mb-0">Detail Barang</h5>
            <Link :href="back_url" class="btn btn-primary btn-rounded mb-2">
                <i class="mdi mdi-arrow-left me-1"></i>Kembali
            </Link>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted">Kode</small><div><strong>{{ barang.kode_barang }}</strong></div></div>
                <div class="col-md-3"><small class="text-muted">Part Number</small><div>{{ barang.part_number || '-' }}</div></div>
                <div class="col-md-6"><small class="text-muted">Nama</small><div>{{ barang.nama_barang }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Kategori</small><div>{{ barang.kategori?.nama_category }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Sub Kategori</small><div>{{ barang.sub_kategori?.nama_sub_category ?? '-' }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Merk</small><div>{{ barang.merk?.nama_merk }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Group</small><div>{{ barang.group?.nama_group }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Satuan</small><div>{{ barang.satuan }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Harga Beli</small><div>{{ fmtRp(barang.harga_beli) }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Harga Jual</small><div>{{ fmtRp(barang.harga_jual) }}</div></div>
                <div class="col-md-3 mt-3"><small class="text-muted">Min Stok (default)</small><div>{{ barang.min_stok ?? 0 }}</div></div>
                <div class="col-md-3 mt-3">
                    <small class="text-muted">Status</small>
                    <div>
                        <span class="badge rounded-pill"
                            :class="barang.is_active ? 'badge-soft-success' : 'badge-soft-secondary'">
                            {{ barang.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
                <div class="col-md-12 mt-3" v-if="barang.deskripsi">
                    <small class="text-muted">Deskripsi</small>
                    <div>{{ barang.deskripsi }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stok per Gudang</h5>
            <div>
                Total: <strong class="text-primary">{{ totalStok.toLocaleString('id-ID') }}</strong>
                <small class="text-muted">{{ barang.satuan }}</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Gudang</th>
                            <th>Nama Gudang</th>
                            <th class="text-end">Stok</th>
                            <th class="text-end">Min Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in barang.stoks" :key="s.id">
                            <td><code>{{ s.gudang?.kode_gudang }}</code></td>
                            <td>{{ s.gudang?.nama_gudang }}</td>
                            <td class="text-end"><strong>{{ s.stok?.toLocaleString('id-ID') }}</strong></td>
                            <td class="text-end">{{ (s.min_stok ?? barang.min_stok ?? 0).toLocaleString('id-ID') }}</td>
                            <td>
                                <span v-if="s.stok <= (s.min_stok ?? barang.min_stok ?? 0)"
                                    class="badge badge-soft-danger rounded-pill">Di bawah min</span>
                                <span v-else class="badge badge-soft-success rounded-pill">Aman</span>
                            </td>
                        </tr>
                        <tr v-if="!barang.stoks?.length">
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada stok di gudang manapun. Buat mutasi <Link href="/mutasi/create">Masuk</Link>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body border-bottom">
            <h5 class="mb-0">Riwayat Mutasi (50 terakhir)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle table-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>No. Mutasi</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Gudang</th>
                            <th>Tujuan</th>
                            <th class="text-end">Qty</th>
                            <th>Referensi</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="it in mutasis" :key="it.id">
                            <td><code>{{ it.mutasi?.nomor_mutasi }}</code></td>
                            <td>{{ fmtDate(it.mutasi?.tanggal) }}</td>
                            <td>
                                <span class="badge rounded-pill" :class="tipeBadge[it.mutasi?.tipe]?.class">
                                    {{ tipeBadge[it.mutasi?.tipe]?.label ?? it.mutasi?.tipe }}
                                </span>
                            </td>
                            <td>{{ it.mutasi?.gudang?.nama_gudang ?? '-' }}</td>
                            <td>{{ it.mutasi?.gudang_tujuan?.nama_gudang ?? '-' }}</td>
                            <td class="text-end">
                                {{ it.qty?.toLocaleString('id-ID') }}
                                <small class="text-muted d-block">{{ it.stok_sebelum }} → {{ it.stok_sesudah }}</small>
                            </td>
                            <td>{{ it.mutasi?.referensi || '-' }}</td>
                            <td>{{ it.mutasi?.user?.name ?? '-' }}</td>
                        </tr>
                        <tr v-if="!mutasis.length">
                            <td colspan="8" class="text-center text-muted py-4">Belum ada mutasi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
