<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Rupiah from '@/Components/Rupiah.vue';

const props = defineProps({
    barang:        { type: Object, required: true },
    stokPerGudang: { type: Array,  default: () => [] },
    totalStok:     { type: Number, default: 0 },
    lots:          { type: Array,  default: () => [] },
});

defineOptions({ layout: AppLayout });

function fmtDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}
function fmtNum(n) { return Number(n ?? 0).toLocaleString('id-ID'); }

const totalNilaiModal = computed(() => props.totalStok * Number(props.barang.harga_beli || 0));
</script>

<template>
    <!-- Header singkat — info detail barang ada di Master Barang -->
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">
                    <i class="bx bx-buildings text-warning me-2"></i>Stok Barang
                </h5>
                <small class="text-muted">
                    <Link :href="`/barang/${barang.id}`" class="text-primary text-decoration-none">
                        <strong>{{ barang.kode_barang }}</strong> — {{ barang.nama_barang }}
                    </Link>
                </small>
            </div>
            <div>
                <strong class="fs-4 text-primary">{{ fmtNum(totalStok) }}</strong>
                <small class="text-muted">{{ barang.satuan }}</small>
                <span class="text-muted mx-2">·</span>
                <Rupiah :value="totalNilaiModal" inline bold />
                <Link href="/stok" class="btn btn-secondary btn-sm ms-3">
                    <i class="bx bx-arrow-back me-1"></i>Kembali
                </Link>
            </div>
        </div>
    </div>

    <!-- Stok per Gudang -->
    <div class="card shadow-sm mb-3">
        <div class="card-body border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bx bx-buildings me-1"></i>Distribusi Per Gudang</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Gudang</th>
                            <th class="text-end">Stok</th>
                            <th class="text-end">Min Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="g in stokPerGudang" :key="g.gudang_id">
                            <td><strong>{{ g.nama_gudang }}</strong></td>
                            <td class="text-end"><strong>{{ fmtNum(g.stok) }}</strong></td>
                            <td class="text-end text-muted">{{ fmtNum(g.min_stok) }}</td>
                            <td>
                                <span v-if="g.stok === 0" class="badge bg-danger">Habis</span>
                                <span v-else-if="g.min_stok > 0 && g.stok <= g.min_stok" class="badge bg-warning">Rendah</span>
                                <span v-else class="badge bg-success">Aman</span>
                            </td>
                        </tr>
                        <tr v-if="!stokPerGudang.length">
                            <td colspan="4" class="text-center text-muted py-3">Tidak ada gudang aktif</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- FIFO Lots — ciri khas halaman ini, tidak ada di tempat lain -->
    <div class="card shadow-sm" v-if="lots.length">
        <div class="card-body border-bottom">
            <h6 class="mb-0 fw-bold">
                <i class="bx bx-layer me-1"></i>Lot Aktif (FIFO)
                <small class="text-muted fw-normal ms-1">— urutan keluar saat barang dijual</small>
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Gudang</th>
                            <th>Supplier</th>
                            <th class="text-end">Sisa</th>
                            <th class="text-end">Harga Beli</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lot in lots" :key="lot.id">
                            <td>{{ fmtDate(lot.tanggal) }}</td>
                            <td>{{ lot.nama_gudang || '-' }}</td>
                            <td>{{ lot.nama_supplier || '-' }}</td>
                            <td class="text-end"><strong>{{ fmtNum(lot.qty_sisa) }}</strong> / {{ fmtNum(lot.qty_in) }}</td>
                            <td class="text-end"><Rupiah :value="lot.harga_beli" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
