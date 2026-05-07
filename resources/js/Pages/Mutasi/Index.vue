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
const status    = ref(props.filters.status ?? '');
let timer = null;

function reload(extra = {}) {
    router.get('/mutasi', {
        search: search.value,
        perPage: perPage.value,
        tipe: tipe.value,
        gudang_id: gudangId.value,
        status: status.value,
        ...extra,
    }, { preserveState: true, preserveScroll: true, replace: true, only: ['mutasis', 'filters'] });
}
function scheduleReload(delay = 200) {
    clearTimeout(timer);
    timer = setTimeout(reload, delay);
}
watch(search, () => scheduleReload(300));
watch([tipe, gudangId, status], () => scheduleReload(100));
function changePerPage(n) { perPage.value = n; reload(); }

const tipeBadge = {
    in:       { class: 'badge-soft-success',   label: 'Masuk' },
    out:      { class: 'badge-soft-danger',    label: 'Keluar' },
    transfer: { class: 'badge-soft-warning',   label: 'Transfer' },
    adjust:   { class: 'badge-soft-secondary', label: 'Adjust' },
};
const statusBadge = {
    pending:  { class: 'badge-soft-warning', label: 'Menunggu Approval' },
    approved: { class: 'badge-soft-success', label: 'Disetujui' },
    rejected: { class: 'badge-soft-danger',  label: 'Ditolak' },
};

// Detail modal — items sudah eager-loaded di index payload, jadi instant.
const showDetail = ref(false);
const detail = ref(null);

// Approve / Reject actions
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const rejectionReason = ref('');
const processing = ref(false);

function fmtRp(v) {
    if (v === null || v === undefined || v === '') return '-';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
}
function fmtDate(v) {
    if (!v) return '-';
    return new Date(v).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' });
}

function openDetail(m) {
    detail.value = m;
    showDetail.value = true;
}

function openApprove() {
    showApproveModal.value = true;
}
function submitApprove() {
    if (!detail.value) return;
    processing.value = true;
    router.post(`/mutasi/${detail.value.id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            showApproveModal.value = false;
            showDetail.value = false;
        },
    });
}
function openReject() {
    rejectionReason.value = '';
    showRejectModal.value = true;
}
function submitReject() {
    if (!detail.value) return;
    if (!rejectionReason.value.trim()) { window.toast?.error('Alasan penolakan wajib diisi.'); return; }
    processing.value = true;
    router.post(`/mutasi/${detail.value.id}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            showRejectModal.value = false;
            showDetail.value = false;
        },
    });
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
                            <Link href="/mutasi/create" prefetch class="btn btn-success btn-rounded">
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
                        <div class="col-md-2">
                            <select v-model="tipe" class="form-select">
                                <option value="">Semua Tipe</option>
                                <option value="in">Masuk</option>
                                <option value="out">Keluar</option>
                                <option value="transfer">Transfer</option>
                                <option value="adjust">Adjust</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select v-model="gudangId" class="form-select">
                                <option value="">Semua Gudang</option>
                                <option v-for="g in gudangs" :key="g.id" :value="g.id">{{ g.nama_gudang }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select v-model="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending">Menunggu Approval</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
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
                                    <th>Status</th>
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
                                    <td>
                                        <span class="badge rounded-pill" :class="statusBadge[m.status]?.class">
                                            {{ statusBadge[m.status]?.label ?? m.status }}
                                        </span>
                                    </td>
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
                                    <td colspan="12" class="text-center text-muted py-4">Tidak ada data</td>
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
                    <div class="col-md-3 mt-2"><small class="text-muted">Pengirim</small><div>{{ detail.user?.name ?? '-' }}</div></div>
                    <div class="col-md-3 mt-2"><small class="text-muted">Status</small>
                        <div><span class="badge rounded-pill" :class="statusBadge[detail.status]?.class">
                            {{ statusBadge[detail.status]?.label }}
                        </span></div>
                    </div>
                    <div class="col-md-3 mt-2" v-if="detail.approver"><small class="text-muted">Diproses oleh</small>
                        <div>{{ detail.approver?.name }}<br><small class="text-muted">{{ fmtDate(detail.approved_at) }}</small></div>
                    </div>
                    <div class="col-md-12 mt-2" v-if="detail.rejection_reason">
                        <div class="alert alert-danger mb-0 py-2">
                            <strong>Alasan Penolakan:</strong> {{ detail.rejection_reason }}
                        </div>
                    </div>
                    <div class="col-md-6 mt-2" v-if="detail.keterangan"><small class="text-muted">Keterangan</small><div>{{ detail.keterangan }}</div></div>
                </div>

                <div v-if="detail.tipe === 'transfer' && detail.status === 'pending'"
                    class="alert alert-warning d-flex align-items-center">
                    <i class="bx bx-info-circle font-size-20 me-2"></i>
                    <div>
                        Transfer ini menunggu persetujuan <strong>{{ detail.gudang_tujuan?.nama_gudang }}</strong>.
                        Stok sudah dikurangi dari gudang asal (in-transit) — barang baru masuk ke gudang tujuan setelah disetujui.
                    </div>
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
            <template v-if="detail && detail.tipe === 'transfer' && detail.status === 'pending'">
                <button class="btn btn-danger" :disabled="processing" @click="openReject">
                    <i class="bx bx-x me-1"></i>Tolak
                </button>
                <button class="btn btn-success" :disabled="processing" @click="openApprove">
                    <i class="bx bx-check me-1"></i>Setujui &amp; Terima Barang
                </button>
            </template>
            <a v-if="detail" :href="`/mutasi/${detail.id}/print`" target="_blank" class="btn btn-primary">
                <i class="bx bx-printer me-1"></i>Cetak Surat Jalan
            </a>
            <button class="btn btn-light" @click="showDetail = false">Tutup</button>
        </div>
    </Modal>

    <Modal :show="showApproveModal" title="Setujui Transfer" size="modal-md" @close="showApproveModal = false">
        <div class="modal-body">
            <p>
                Setujui transfer <strong>{{ detail?.nomor_mutasi }}</strong>?
                Stok akan masuk ke gudang tujuan
                <strong>{{ detail?.gudang_tujuan?.nama_gudang }}</strong>
                dari gudang asal <strong>{{ detail?.gudang?.nama_gudang }}</strong>.
            </p>
            <div class="bg-light rounded p-2 mt-2">
                <div class="d-flex justify-content-between">
                    <small class="text-muted">Total Item</small>
                    <strong>{{ detail?.items_count ?? detail?.items?.length ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <small class="text-muted">Total Qty</small>
                    <strong>{{ Number(detail?.total_qty ?? 0).toLocaleString('id-ID') }}</strong>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" @click="showApproveModal = false">Batal</button>
            <button class="btn btn-success" :disabled="processing" @click="submitApprove">
                <i class="bx bx-check me-1"></i>Konfirmasi Setujui
            </button>
        </div>
    </Modal>

    <Modal :show="showRejectModal" title="Tolak Transfer" size="modal-md" @close="showRejectModal = false">
        <div class="modal-body">
            <p>Tolak transfer <strong>{{ detail?.nomor_mutasi }}</strong>? Stok akan dikembalikan ke gudang asal <strong>{{ detail?.gudang?.nama_gudang }}</strong>.</p>
            <div class="mb-3">
                <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                <textarea v-model="rejectionReason" class="form-control" rows="3"
                    placeholder="Mis: Barang tidak sesuai PO, jumlah kurang, kondisi rusak..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" @click="showRejectModal = false">Batal</button>
            <button class="btn btn-danger" :disabled="processing" @click="submitReject">
                <i class="bx bx-x me-1"></i>Konfirmasi Tolak
            </button>
        </div>
    </Modal>
</template>
