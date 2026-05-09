<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    mutasi: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

function goBack() {
    window.history.back();
}

function getTipeBadge(tipe) {
    const badges = {
        in: 'bg-success',
        out: 'bg-danger',
        transfer: 'bg-info',
        adjust: 'bg-warning',
    };
    return badges[tipe] || 'bg-secondary';
}

function getTipeLabel(tipe) {
    const labels = {
        in: 'PEMASUKAN',
        out: 'PENGELUARAN',
        transfer: 'TRANSFER',
        adjust: 'PENYESUAIAN',
    };
    return labels[tipe] || tipe.toUpperCase();
}

function getStatusBadge(status) {
    const badges = {
        pending: 'bg-warning',
        approved: 'bg-success',
        rejected: 'bg-danger',
    };
    return badges[status] || 'bg-secondary';
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 card-title">DETAIL MUTASI STOK</h5>
                        <button class="btn btn-secondary btn-sm" @click="goBack">
                            <i class="bx bx-arrow-back me-1"></i> Kembali
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <td width="150"><strong>Nomor Mutasi</strong></td>
                                        <td>: {{ mutasi.nomor_mutasi }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td>: {{ new Date(mutasi.tanggal).toLocaleDateString('id-ID', { 
                                            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
                                        }) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipe</strong></td>
                                        <td>: <span class="badge rounded-pill" :class="getTipeBadge(mutasi.tipe)">
                                            {{ getTipeLabel(mutasi.tipe) }}
                                        </span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td>: 
                                            <span v-if="mutasi.cancelled_at" class="badge bg-danger">DIBATALKAN</span>
                                            <span v-else class="badge rounded-pill" :class="getStatusBadge(mutasi.status)">
                                                {{ mutasi.status.toUpperCase() }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="mutasi.referensi">
                                        <td><strong>Referensi</strong></td>
                                        <td>: {{ mutasi.referensi }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr v-if="mutasi.tipe === 'transfer'">
                                        <td width="150"><strong>Gudang Asal</strong></td>
                                        <td>: {{ mutasi.gudang?.nama_gudang || '-' }}</td>
                                    </tr>
                                    <tr v-if="mutasi.tipe === 'transfer'">
                                        <td><strong>Gudang Tujuan</strong></td>
                                        <td>: {{ mutasi.gudang_tujuan?.nama_gudang || '-' }}</td>
                                    </tr>
                                    <tr v-if="mutasi.tipe !== 'transfer'">
                                        <td width="150"><strong>Gudang</strong></td>
                                        <td>: {{ mutasi.gudang?.nama_gudang || '-' }}</td>
                                    </tr>
                                    <tr v-if="mutasi.supplier">
                                        <td><strong>Supplier</strong></td>
                                        <td>: {{ mutasi.supplier?.nama_supplier || '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat Oleh</strong></td>
                                        <td>: {{ mutasi.user?.name || '-' }}</td>
                                    </tr>
                                    <tr v-if="mutasi.approver">
                                        <td><strong>Disetujui Oleh</strong></td>
                                        <td>: {{ mutasi.approver?.name || '-' }}</td>
                                    </tr>
                                    <tr v-if="mutasi.approved_at">
                                        <td><strong>Tanggal Disetujui</strong></td>
                                        <td>: {{ new Date(mutasi.approved_at).toLocaleString('id-ID') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Cancellation Info -->
                    <div v-if="mutasi.cancelled_at" class="alert alert-danger mb-4">
                        <h6 class="alert-heading"><i class="bx bx-x-circle me-2"></i>Transaksi Dibatalkan</h6>
                        <hr>
                        <p class="mb-1"><strong>Dibatalkan oleh:</strong> {{ mutasi.canceller?.name || '-' }}</p>
                        <p class="mb-1"><strong>Tanggal pembatalan:</strong> {{ new Date(mutasi.cancelled_at).toLocaleString('id-ID') }}</p>
                        <p class="mb-0"><strong>Alasan:</strong> {{ mutasi.cancellation_reason || '-' }}</p>
                    </div>

                    <!-- Keterangan -->
                    <div v-if="mutasi.keterangan" class="alert alert-info mb-4">
                        <strong>Keterangan:</strong> {{ mutasi.keterangan }}
                    </div>

                    <!-- Items Table -->
                    <h6 class="mb-3">Detail Barang</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th>Satuan</th>
                                    <th v-if="mutasi.tipe === 'in' || mutasi.tipe === 'out'">Harga Satuan</th>
                                    <th v-if="mutasi.tipe === 'in' || mutasi.tipe === 'out'">Subtotal</th>
                                    <th v-if="mutasi.tipe === 'adjust'">Stok Sebelum</th>
                                    <th v-if="mutasi.tipe === 'adjust'">Stok Sesudah</th>
                                    <th v-if="mutasi.items[0]?.keterangan">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in mutasi.items" :key="item.id">
                                    <td>{{ i + 1 }}</td>
                                    <td>{{ item.barang?.kode_barang || '-' }}</td>
                                    <td>{{ item.barang?.nama_barang || '-' }}</td>
                                    <td><strong>{{ item.qty?.toLocaleString('id-ID') || 0 }}</strong></td>
                                    <td>{{ item.barang?.satuan || '-' }}</td>
                                    <td v-if="mutasi.tipe === 'in' || mutasi.tipe === 'out'">
                                        Rp {{ (item.harga_satuan || 0).toLocaleString('id-ID') }}
                                    </td>
                                    <td v-if="mutasi.tipe === 'in' || mutasi.tipe === 'out'">
                                        <strong>Rp {{ ((item.qty || 0) * (item.harga_satuan || 0)).toLocaleString('id-ID') }}</strong>
                                    </td>
                                    <td v-if="mutasi.tipe === 'adjust'">{{ item.stok_sebelum?.toLocaleString('id-ID') || 0 }}</td>
                                    <td v-if="mutasi.tipe === 'adjust'">{{ item.stok_sesudah?.toLocaleString('id-ID') || 0 }}</td>
                                    <td v-if="mutasi.items[0]?.keterangan">{{ item.keterangan || '-' }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="mutasi.tipe === 'in' || mutasi.tipe === 'out'" class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th>{{ mutasi.total_qty?.toLocaleString('id-ID') || 0 }}</th>
                                    <th></th>
                                    <th></th>
                                    <th><strong>Rp {{ (mutasi.total_value || 0).toLocaleString('id-ID') }}</strong></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Timestamps -->
                    <div class="mt-4 text-muted small">
                        <p class="mb-1">Dibuat: {{ new Date(mutasi.created_at).toLocaleString('id-ID') }}</p>
                        <p class="mb-0">Terakhir diupdate: {{ new Date(mutasi.updated_at).toLocaleString('id-ID') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
