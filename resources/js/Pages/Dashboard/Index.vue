<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

function fmtRpNumber(v) {
    if (v === null || v === undefined || v === '') return '0';
    return Number(v).toLocaleString('id-ID');
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
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
        in: 'Masuk',
        out: 'Keluar',
        transfer: 'Transfer',
        adjust: 'Adjust',
    };
    return labels[tipe] || tipe;
}

const stokPercentage = computed(() => {
    const total = props.stats.stok_total_sku || 0;
    if (total === 0) return { kosong: 0, rendah: 0, aman: 0 };
    
    const kosong = ((props.stats.stok_kosong || 0) / total) * 100;
    const rendah = ((props.stats.stok_rendah || 0) / total) * 100;
    const aman = 100 - kosong - rendah;
    
    return { kosong, rendah, aman };
});
</script>

<template>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold">Dashboard</h4>
                        <p class="text-muted small mb-0">Selamat datang di Sistem Manajemen Gudang</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 1 - Master Data -->
        <div class="row g-4 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-package text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total Barang</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.barang) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-grid-alt text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Kategori</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.category) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-bookmark-star text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Merk</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.merk) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-collection text-info fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Group</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.group) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-store-alt text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Gudang</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.gudang) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bxs-user-circle text-secondary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Supplier</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.supplier) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 2 - Stok -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-box text-primary fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Total SKU</p>
                                <h4 class="mb-0 fw-bold">{{ fmtNumber(stats.stok_total_sku) }}</h4>
                                <small class="text-muted">{{ fmtNumber(stats.stok_total) }} unit</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-error-circle text-danger fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Stok Kosong</p>
                                <h4 class="mb-0 fw-bold text-danger">{{ fmtNumber(stats.stok_kosong) }}</h4>
                                <small class="text-muted">{{ stokPercentage.kosong.toFixed(1) }}% dari total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-error text-warning fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Stok Rendah</p>
                                <h4 class="mb-0 fw-bold text-warning">{{ fmtNumber(stats.stok_rendah) }}</h4>
                                <small class="text-muted">{{ stokPercentage.rendah.toFixed(1) }}% dari total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center">
                                    <i class="bx bx-check-circle text-success fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Stok Aman</p>
                                <h4 class="mb-0 fw-bold text-success">{{ fmtNumber(stats.stok_total_sku - stats.stok_kosong - stats.stok_rendah) }}</h4>
                                <small class="text-muted">{{ stokPercentage.aman.toFixed(1) }}% dari total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 3 - Transaksi Bulan Ini -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100 card-hover border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-muted">Transaksi Hari Ini</h6>
                            <i class="bx bx-calendar-event text-primary fs-4"></i>
                        </div>
                        <h3 class="mb-0 fw-bold">{{ fmtNumber(stats.transaksi_hari_ini) }}</h3>
                        <small class="text-muted">Semua tipe transaksi</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100 card-hover border-start border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-muted">Barang Masuk (Bulan Ini)</h6>
                            <i class="bx bx-import text-success fs-4"></i>
                        </div>
                        <h3 class="mb-0 fw-bold text-success">{{ fmtNumber(stats.transaksi_masuk_bulan_ini) }}</h3>
                        <small class="text-muted">
                            <span class="rupiah-inline"><span class="rp">Rp</span> {{ fmtRpNumber(stats.nilai_masuk_bulan_ini) }}</span>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100 card-hover border-start border-4 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-muted">Barang Keluar (Bulan Ini)</h6>
                            <i class="bx bx-export text-danger fs-4"></i>
                        </div>
                        <h3 class="mb-0 fw-bold text-danger">{{ fmtNumber(stats.transaksi_keluar_bulan_ini) }}</h3>
                        <small class="text-muted">
                            <span class="rupiah-inline"><span class="rp">Rp</span> {{ fmtRpNumber(stats.nilai_keluar_bulan_ini) }}</span>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-sm h-100 card-hover border-start border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-muted">Transfer (Bulan Ini)</h6>
                            <i class="bx bx-transfer text-info fs-4"></i>
                        </div>
                        <h3 class="mb-0 fw-bold text-info">{{ fmtNumber(stats.transaksi_transfer_bulan_ini) }}</h3>
                        <small class="text-muted">Antar gudang</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row g-4">
            <!-- Top Barang Keluar -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 fw-bold">
                                <i class="bx bx-trending-up text-danger me-2"></i>
                                Top 5 Barang Keluar (7 Hari Terakhir)
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="stats.top_barang_keluar && stats.top_barang_keluar.length > 0" class="list-group list-group-flush">
                            <div v-for="(item, index) in stats.top_barang_keluar" :key="index" 
                                class="list-group-item border-0 px-0 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center">
                                            <span class="fw-bold text-danger">{{ index + 1 }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0">{{ item.nama_barang }}</h6>
                                        <small class="text-muted">{{ item.kode_barang }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2">
                                            {{ fmtNumber(item.total_qty) }} unit
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                            <p class="mb-0">Belum ada data barang keluar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Terakhir -->
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 fw-bold">
                                <i class="bx bx-time-five text-primary me-2"></i>
                                Aktivitas Terakhir
                            </h5>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div v-if="stats.aktivitas_terakhir && stats.aktivitas_terakhir.length > 0" class="timeline">
                            <div v-for="(item, index) in stats.aktivitas_terakhir" :key="index" 
                                class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-xs rounded-circle d-flex align-items-center justify-content-center"
                                            :class="getTipeBadge(item.tipe)">
                                            <i class="bx bx-transfer text-white" style="font-size: 12px;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div>
                                                <h6 class="mb-1">{{ item.nomor_mutasi }}</h6>
                                                <p class="text-muted small mb-1">
                                                    <span :class="`badge ${getTipeBadge(item.tipe)} badge-sm me-1`">
                                                        {{ getTipeLabel(item.tipe) }}
                                                    </span>
                                                    {{ item.nama_gudang }} • {{ item.total_qty }} item
                                                    <span v-if="item.total_value > 0">
                                                        • <span class="rupiah-inline"><span class="rp">Rp</span> {{ fmtRpNumber(item.total_value) }}</span>
                                                    </span>
                                                </p>
                                                <small class="text-muted">{{ item.user_name }} • {{ fmtDate(item.tanggal) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-5 text-muted">
                            <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                            <p class="mb-0">Belum ada aktivitas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.avatar-sm {
    width: 3rem;
    height: 3rem;
}

.avatar-xs {
    width: 2rem;
    height: 2rem;
}

.card {
    border-radius: 12px;
}

.card-body {
    padding: 1.5rem;
}

.card-hover {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.text-purple {
    color: #6f42c1 !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

/* Ensure icons are visible */
.avatar-sm i {
    display: inline-block;
    font-size: 1.5rem;
    line-height: 1;
}

.rupiah-inline {
    display: inline;
    white-space: nowrap;
}

.rupiah-inline .rp {
    margin-right: 2px;
}

.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 2rem;
    bottom: -1rem;
    width: 2px;
    background: #e9ecef;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

/* Better spacing for small text */
small.text-muted {
    line-height: 1.6;
}

/* Card header styling */
.card-header {
    padding: 1.25rem 1.5rem;
    border-radius: 12px 12px 0 0 !important;
}
</style>
