<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    stats: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name || 'User');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 11) return 'Selamat Pagi';
    if (h < 15) return 'Selamat Siang';
    if (h < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const stokKritisCount = computed(() => (props.stats.stok_kosong || 0) + (props.stats.stok_rendah || 0));

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
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1 fw-bold">{{ greeting }}, {{ userName }} 👋</h4>
                        <p class="text-muted small mb-0">Ringkasan aktivitas gudang Anda hari ini</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Stok Kritis -->
        <div v-if="stokKritisCount > 0" class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                    <div class="d-flex align-items-center">
                        <i class="bx bxs-error-circle fs-3 text-warning me-3"></i>
                        <div>
                            <strong>Perhatian!</strong>
                            Terdapat <span class="fw-bold text-danger">{{ fmtNumber(stats.stok_kosong) }}</span> SKU stok kosong
                            dan <span class="fw-bold text-warning">{{ fmtNumber(stats.stok_rendah) }}</span> SKU stok rendah.
                        </div>
                    </div>
                    <Link :href="route('stok.index', { low_only: 1 })" class="btn btn-warning btn-sm fw-semibold">
                        <i class="bx bx-right-arrow-alt me-1"></i> Lihat Daftar
                    </Link>
                </div>
            </div>
        </div>


        <!-- Stats Cards Row 1 - Master Data -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('barang.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-package text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Total Barang</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.barang) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('category.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-grid-alt text-success fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Kategori</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.category) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('merk.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-bookmark-star text-warning fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Merk</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.merk) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('group.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-collection text-info fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Group</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.group) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('gudang.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-store-alt text-danger fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Gudang</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.gudang) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-2 col-md-4 col-sm-6">
                <Link :href="route('supplier.index')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bxs-user-circle text-secondary fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Supplier</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.supplier) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Stok Overview - Single Visual Bar -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-bold">
                                    <i class="bx bx-box text-primary me-2"></i>Ringkasan Stok
                                </h6>
                                <small class="text-muted">
                                    Total <strong>{{ fmtNumber(stats.stok_total_sku) }}</strong> SKU
                                    ({{ fmtNumber(stats.stok_total) }} unit)
                                </small>
                            </div>
                            <Link :href="route('stok.index')" class="btn btn-sm btn-outline-primary">
                                Detail Stok <i class="bx bx-right-arrow-alt"></i>
                            </Link>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress stok-progress mb-3" style="height: 24px; border-radius: 12px; overflow: hidden;">
                            <div class="progress-bar bg-success" role="progressbar"
                                :style="{ width: stokPercentage.aman + '%' }"
                                :title="`Aman: ${fmtNumber(stats.stok_total_sku - stats.stok_kosong - stats.stok_rendah)} SKU`">
                                <span v-if="stokPercentage.aman > 8" class="fw-semibold">{{ stokPercentage.aman.toFixed(0) }}%</span>
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar"
                                :style="{ width: stokPercentage.rendah + '%' }"
                                :title="`Rendah: ${fmtNumber(stats.stok_rendah)} SKU`">
                                <span v-if="stokPercentage.rendah > 8" class="fw-semibold">{{ stokPercentage.rendah.toFixed(0) }}%</span>
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar"
                                :style="{ width: stokPercentage.kosong + '%' }"
                                :title="`Kosong: ${fmtNumber(stats.stok_kosong)} SKU`">
                                <span v-if="stokPercentage.kosong > 8" class="fw-semibold">{{ stokPercentage.kosong.toFixed(0) }}%</span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="row g-2 text-center">
                            <div class="col-md-4">
                                <Link :href="route('stok.index')" class="text-decoration-none">
                                    <div class="d-flex align-items-center justify-content-center gap-2 p-2 rounded stok-legend">
                                        <span class="legend-dot bg-success"></span>
                                        <span class="text-muted small">Aman:</span>
                                        <strong class="text-success">{{ fmtNumber(stats.stok_total_sku - stats.stok_kosong - stats.stok_rendah) }}</strong>
                                        <small class="text-muted">SKU</small>
                                    </div>
                                </Link>
                            </div>
                            <div class="col-md-4">
                                <Link :href="route('stok.index', { low_only: 1 })" class="text-decoration-none">
                                    <div class="d-flex align-items-center justify-content-center gap-2 p-2 rounded stok-legend">
                                        <span class="legend-dot bg-warning"></span>
                                        <span class="text-muted small">Rendah:</span>
                                        <strong class="text-warning">{{ fmtNumber(stats.stok_rendah) }}</strong>
                                        <small class="text-muted">SKU</small>
                                    </div>
                                </Link>
                            </div>
                            <div class="col-md-4">
                                <Link :href="route('stok.index', { low_only: 1 })" class="text-decoration-none">
                                    <div class="d-flex align-items-center justify-content-center gap-2 p-2 rounded stok-legend">
                                        <span class="legend-dot bg-danger"></span>
                                        <span class="text-muted small">Kosong:</span>
                                        <strong class="text-danger">{{ fmtNumber(stats.stok_kosong) }}</strong>
                                        <small class="text-muted">SKU</small>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row 3 - Transaksi Bulan Ini -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <Link :href="route('riwayat.semua')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bx-calendar-event text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Transaksi Hari Ini</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.transaksi_hari_ini) }}</h4>
                                    <small class="text-muted">Semua tipe transaksi</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-3 col-md-6">
                <Link :href="route('riwayat.barang-masuk')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bx-import text-success fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Barang Masuk (Bulan Ini)</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.transaksi_masuk_bulan_ini) }}</h4>
                                    <small class="text-muted">
                                        <span class="rupiah-inline"><span class="rp">Rp</span> {{ fmtRpNumber(stats.nilai_masuk_bulan_ini) }}</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-3 col-md-6">
                <Link :href="route('riwayat.barang-keluar')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bx-export text-danger fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Barang Keluar (Bulan Ini)</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.transaksi_keluar_bulan_ini) }}</h4>
                                    <small class="text-muted">
                                        <span class="rupiah-inline"><span class="rp">Rp</span> {{ fmtRpNumber(stats.nilai_keluar_bulan_ini) }}</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
            <div class="col-xl-3 col-md-6">
                <Link :href="route('riwayat.transfer')" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 card-hover stat-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="bx bx-transfer text-info fs-4"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="text-muted mb-1 small">Transfer (Bulan Ini)</p>
                                    <h4 class="mb-0 fw-bold text-dark">{{ fmtNumber(stats.transaksi_transfer_bulan_ini) }}</h4>
                                    <small class="text-muted">Antar gudang</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>
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
                            <i class="bx bx-package fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-2">Belum ada data barang keluar minggu ini</p>
                            <Link :href="route('barang-keluar.form')" class="btn btn-sm btn-outline-danger">
                                <i class="bx bx-plus me-1"></i>Buat Barang Keluar
                            </Link>
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
                            <i class="bx bx-time-five fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-2">Belum ada aktivitas tercatat</p>
                            <Link :href="route('barang-masuk.form')" class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-plus me-1"></i>Mulai Transaksi
                            </Link>
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

/* Quick Action buttons */
.quick-action {
    padding: 0.85rem 0.5rem;
    border-radius: 10px;
    transition: all 0.2s ease;
    border-width: 1.5px;
}

.quick-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.quick-action i {
    transition: transform 0.2s ease;
}

.quick-action:hover i {
    transform: scale(1.15);
}

/* Stat card link reset */
a.text-decoration-none .stat-card {
    cursor: pointer;
}

a.text-decoration-none:hover .stat-card {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Stok progress bar */
.stok-progress {
    background-color: #f1f3f5;
}

.stok-progress .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: width 0.6s ease;
}

.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.stok-legend {
    transition: background-color 0.2s;
}

.stok-legend:hover {
    background-color: #f8f9fa;
}

/* Alert styling */
.alert-warning {
    background-color: #fff8e1;
    color: #856404;
    border-left: 4px solid #ffc107 !important;
    border-radius: 10px;
}
</style>
