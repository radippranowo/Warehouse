<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Rupiah from '@/Components/Rupiah.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    barang:   { type: Object, required: true },
    back_url: { type: String, default: '/barang' },
});

function fmtDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleString('id-ID', { dateStyle: 'medium' });
}
</script>

<template>
    <div class="card shadow-sm">
        <div class="card-body border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">
                    <i class="bx bx-package text-primary me-2"></i>{{ barang.nama_barang }}
                </h5>
                <small class="text-muted"><code>{{ barang.kode_barang }}</code></small>
                <span class="badge rounded-pill ms-2"
                    :class="barang.is_active ? 'badge-soft-success' : 'badge-soft-secondary'">
                    {{ barang.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                <Link :href="`/barang/${barang.id}/edit`" class="btn btn-primary btn-sm">
                    <i class="bx bx-pencil me-1"></i>Edit
                </Link>
                <Link :href="`/stok/${barang.id}`" class="btn btn-info btn-sm">
                    <i class="bx bx-buildings me-1"></i>Cek Stok
                </Link>
                <Link :href="back_url" class="btn btn-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>Kembali
                </Link>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <small class="text-muted d-block">Part Number</small>
                    <strong>{{ barang.part_number || '-' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Satuan</small>
                    <strong>{{ barang.satuan || '-' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Harga Beli</small>
                    <Rupiah :value="barang.harga_beli" inline />
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Harga Jual</small>
                    <Rupiah :value="barang.harga_jual" inline bold />
                </div>

                <div class="col-md-3">
                    <small class="text-muted d-block">Kategori</small>
                    {{ barang.kategori?.nama_category || '-' }}
                    <small v-if="barang.sub_kategori" class="text-muted d-block">
                        / {{ barang.sub_kategori.nama_sub_category }}
                    </small>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Merk</small>
                    {{ barang.merk?.nama_merk || '-' }}
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Group</small>
                    {{ barang.group?.nama_group || '-' }}
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Min Stok</small>
                    {{ (barang.min_stok ?? 0).toLocaleString('id-ID') }}
                </div>

                <div class="col-12" v-if="barang.deskripsi">
                    <small class="text-muted d-block mb-1">Deskripsi</small>
                    <div class="border rounded p-2 bg-light">{{ barang.deskripsi }}</div>
                </div>

                <div class="col-12 pt-2 border-top">
                    <small class="text-muted">
                        Dibuat {{ fmtDate(barang.created_at) }} · Diupdate {{ fmtDate(barang.updated_at) }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</template>
