<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { usePartialReloadLoading } from '@/composables/usePartialReloadLoading';

const { loading } = usePartialReloadLoading('/gudang');

const props = defineProps({
    gudangs: { type: Object, required: true },
    filters: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const localData  = ref([...(props.gudangs.data ?? [])]);
const localTotal = ref(props.gudangs.total ?? 0);
const animateRows = ref(true);

watch(() => props.gudangs, (val) => {
    animateRows.value = false;
    localData.value  = [...(val.data ?? [])];
    localTotal.value = val.total ?? 0;
    nextTick(() => { animateRows.value = true; });
}, { deep: false });

const displayItems = computed(() => ({
    ...props.gudangs,
    data:  localData.value,
    total: localTotal.value,
}));

const search  = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
const skeletonRows = computed(() => Math.min(Number(perPage.value) || 10, 10));
let timer = null;

function reload(extra = {}) {
    router.get('/gudang',
        { search: search.value, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, replace: true, only: ['gudangs', 'filters'] }
    );
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
function changePerPage(n) { perPage.value = n; reload(); }

const showModal = ref(false);
const isEdit    = ref(false);
const form = useForm({
    id: null,
    kode_gudang: '',
    nama_gudang: '',
    alamat: '',
    penanggung_jawab: '',
    is_active: true,
});

function openCreate() {
    isEdit.value = false;
    form.reset();
    form.is_active = true;
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item) {
    isEdit.value = true;
    form.clearErrors();
    Object.assign(form, {
        id: item.id,
        kode_gudang: item.kode_gudang ?? '',
        nama_gudang: item.nama_gudang ?? '',
        alamat: item.alamat ?? '',
        penanggung_jawab: item.penanggung_jawab ?? '',
        is_active: item.is_active ?? true,
    });
    showModal.value = true;
}

function close() { showModal.value = false; }

function submit() {
    const editing     = isEdit.value;
    const payloadId   = form.id;
    const payloadKode = form.kode_gudang;
    const payloadNama = form.nama_gudang;

    // === CLIENT-SIDE PRE-VALIDATION ===
    // Cek duplikat kode/nama di data lokal supaya error langsung tampil
    // tanpa flicker round-trip server, dan optimistic insert tidak bikin
    // konflik di UI.
    const errors = {};
    if (!payloadKode?.toString().trim()) {
        errors.kode_gudang = 'Kode wajib diisi';
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x.kode_gudang).toLowerCase() === String(payloadKode).toLowerCase())) {
        errors.kode_gudang = 'Kode sudah ada';
    }
    if (!payloadNama?.toString().trim()) {
        errors.nama_gudang = 'Nama wajib diisi';
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x.nama_gudang).toLowerCase() === String(payloadNama).toLowerCase())) {
        errors.nama_gudang = 'Nama sudah ada';
    }
    if (Object.keys(errors).length) {
        form.clearErrors();
        form.setError(errors);
        return;
    }

    // Snapshot untuk rollback kalau server tolak (mis. duplikat di halaman lain).
    const dataSnapshot  = [...localData.value];
    const totalSnapshot = localTotal.value;

    if (editing) {
        const idx = localData.value.findIndex(x => x.id === payloadId);
        if (idx !== -1) {
            localData.value[idx] = {
                ...localData.value[idx],
                kode_gudang:      form.kode_gudang,
                nama_gudang:      form.nama_gudang,
                alamat:           form.alamat,
                penanggung_jawab: form.penanggung_jawab,
                is_active:        form.is_active,
            };
        }
    } else {
        // Prepend optimistic. id null → akan ter-update ke id real dari server
        // via watch(props.gudangs) saat partial reload selesai.
        localData.value = [{
            id: null,
            kode_gudang:      form.kode_gudang,
            nama_gudang:      form.nama_gudang,
            alamat:           form.alamat,
            penanggung_jawab: form.penanggung_jawab,
            is_active:        form.is_active,
            stoks_with_qty:   0,
        }, ...localData.value];
        localTotal.value = totalSnapshot + 1;
    }

    showModal.value = false;
    window.toast?.success(`Gudang ${editing ? 'diubah' : 'ditambah'}`);

    const opts = {
        preserveScroll: true,
        preserveState:  true,
        only: ['gudangs', 'errors'],
        onSuccess: () => {
            router.flushAll();
            form.reset();
        },
        onError: () => {
            // Rollback optimistic.
            localData.value  = dataSnapshot;
            localTotal.value = totalSnapshot;
            // Buka modal lagi → errors tampil inline via form.errors.
            showModal.value  = true;
            window.toast?.error('Gagal Simpan');
        },
    };

    if (editing) form.put(`/gudang/${payloadId}`, opts);
    else         form.post('/gudang', opts);
}

// === SELECTION (bulk delete) =================================================
const selected = ref(new Set());
watch(() => props.gudangs, () => { selected.value = new Set(); }, { deep: false });

const selectedCount = computed(() => selected.value.size);
const allSelected = computed(() =>
    localData.value.length > 0 && localData.value.every(x => selected.value.has(x.id))
);
const someSelected = computed(() => selectedCount.value > 0 && !allSelected.value);

function toggleOne(id) {
    const next = new Set(selected.value);
    if (next.has(id)) next.delete(id); else next.add(id);
    selected.value = next;
}
function toggleAll(e) {
    selected.value = e.target.checked
        ? new Set(localData.value.map(x => x.id))
        : new Set();
}

function bulkDestroy() {
    const ids = [...selected.value];
    if (!ids.length) return;

    const doDelete = () => {
        const idSet      = new Set(ids);
        const deletables = localData.value.filter(x => idSet.has(x.id) && (x.stoks_with_qty ?? 0) === 0);
        const blocked    = ids.length - deletables.length;

        if (!deletables.length) {
            selected.value = new Set();
            window.toast?.error('Semua gudang yang dipilih masih punya stok.');
            return;
        }

        const snapshot   = [...localData.value];
        const snapTotal  = localTotal.value;
        const removeIds  = new Set(deletables.map(x => x.id));
        const sendIds    = deletables.map(x => x.id);

        localData.value  = localData.value.filter(x => !removeIds.has(x.id));
        localTotal.value = Math.max(0, snapTotal - removeIds.size);
        selected.value   = new Set();

        window.toast?.success(
            `${removeIds.size} gudang dihapus`
            + (blocked ? `, ${blocked} dilewati (masih ada stok)` : '')
        );

        router.delete('/gudang/bulk', {
            data: { ids: sendIds },
            preserveScroll: true,
            preserveState:  true,
            only: ['gudangs'],
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${ids.length} gudang?`,
            text:  'Gudang yang masih punya stok akan dilewati.',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${ids.length} gudang?`)) {
        doDelete();
    }
}

function destroy(item) {
    const hasStok = (item.stoks_with_qty ?? 0) > 0;

    const doDelete = () => {
        if (hasStok) {
            window.toast?.error(`Gudang '${item.nama_gudang}' masih punya stok.`);
            return;
        }

        const snap = [...localData.value];
        const snapTotal = localTotal.value;
        const wasSelected = selected.value.has(item.id);
        localData.value  = localData.value.filter(x => x.id !== item.id);
        localTotal.value = Math.max(0, snapTotal - 1);
        if (wasSelected) {
            const next = new Set(selected.value);
            next.delete(item.id);
            selected.value = next;
        }
        window.toast?.success('Gudang dihapus');

        const restoreSelection = () => {
            if (wasSelected) {
                const next = new Set(selected.value);
                next.add(item.id);
                selected.value = next;
            }
        };

        router.delete(`/gudang/${item.id}`, {
            preserveScroll: true,
            preserveState: true,
            only: ['gudangs', 'flash'],
            onSuccess: (page) => {
                const flashError = page.props?.flash?.error;
                if (flashError) {
                    localData.value = snap;
                    localTotal.value = snapTotal;
                    restoreSelection();
                    window.toast?.error(flashError);
                }
            },
            onError: () => {
                localData.value = snap;
                localTotal.value = snapTotal;
                restoreSelection();
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${item.nama_gudang}?`,
            text:  'Data tidak bisa dikembalikan',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${item.nama_gudang}?`)) {
        doDelete();
    }
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">GUDANG</h5>
                        <div class="flex-shrink-0 d-flex gap-2">
                            <button v-if="selectedCount > 0" type="button"
                                class="btn btn-danger btn-rounded" @click="bulkDestroy">
                                <i class="mdi mdi-trash-can-outline me-1"></i>Hapus ({{ selectedCount }})
                            </button>
                            <button class="btn btn-success btn-rounded" @click="openCreate">
                                <i class="mdi mdi-plus me-1"></i>Tambah Gudang
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input id="search_gudang" name="search" v-model="search" type="text" class="form-control"
                                        placeholder="Cari kode / nama / PIC..." style="padding-left: 36px; height: 38px;" aria-label="Cari gudang">
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-auto col-md-auto ms-auto">
                            <label class="form-label mb-1 small fw-medium d-block">&nbsp;</label>
                            <div class="d-flex gap-2 align-items-center">
                                <small class="text-muted">
                                    Total: <strong>{{ displayItems.total?.toLocaleString('id-ID') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 36px;">
                                        <input type="checkbox" class="form-check-input"
                                            :checked="allSelected"
                                            :indeterminate.prop="someSelected"
                                            @change="toggleAll">
                                    </th>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Alamat</th>
                                    <th>PIC</th>
                                    <th>Status</th>
                                    <th style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" :name="animateRows ? 'row-fade' : ''">
                                <tr v-if="loading" v-for="n in skeletonRows" :key="`skel-${n}`" class="skeleton-row">
                                    <td><span class="skel skel-sm" style="width: 18px; height: 18px;"></span></td>
                                    <td><span class="skel skel-sm" style="width: 24px;"></span></td>
                                    <td><span class="skel" style="width: 70px;"></span></td>
                                    <td><span class="skel" style="width: 120px;"></span></td>
                                    <td><span class="skel" style="width: 200px;"></span></td>
                                    <td><span class="skel" style="width: 100px;"></span></td>
                                    <td><span class="skel skel-pill" style="width: 60px;"></span></td>
                                    <td>
                                        <span class="skel skel-sm" style="width: 28px; height: 28px; border-radius: 4px;"></span>
                                        <span class="skel skel-sm ms-1" style="width: 28px; height: 28px; border-radius: 4px;"></span>
                                    </td>
                                </tr>
                                <tr v-else v-for="(item, i) in displayItems.data" :key="item.id ?? item.kode_gudang"
                                    :class="{ 'table-active': selected.has(item.id) }">
                                    <td>
                                        <input type="checkbox" class="form-check-input"
                                            :checked="selected.has(item.id)"
                                            @change="toggleOne(item.id)">
                                    </td>
                                    <td>{{ (displayItems.current_page - 1) * displayItems.per_page + i + 1 }}</td>
                                    <td>{{ item.kode_gudang }}</td>
                                    <td>{{ item.nama_gudang }}</td>
                                    <td class="text-truncate" style="max-width: 280px;">{{ item.alamat || '-' }}</td>
                                    <td>{{ item.penanggung_jawab || '-' }}</td>
                                    <td>
                                        <span class="badge rounded-pill"
                                            :class="item.is_active ? 'badge-soft-success' : 'badge-soft-secondary'">
                                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                            @click="openEdit(item)"></button>
                                        <button class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16 ms-1"
                                            @click="destroy(item)"></button>
                                    </td>
                                </tr>
                                <tr v-if="!loading && !displayItems.data.length" key="empty">
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ displayItems.from ?? 0 }}–{{ displayItems.to ?? 0 }} dari {{ displayItems.total }}
                            </small>
                            <select id="per_page_gudang" name="per_page" v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        <Pagination :links="displayItems.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="showModal" :title="(isEdit ? 'Edit ' : 'Tambah ') + 'Gudang'" size="modal-lg" @close="close">
        <form @submit.prevent="submit">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="kode_gudang" class="form-label">Kode</label>
                        <input id="kode_gudang" name="kode_gudang" v-model="form.kode_gudang" class="form-control" :disabled="isEdit"
                            :class="{ 'is-invalid': form.errors.kode_gudang }" placeholder="GDG-01">
                        <small class="text-danger" v-if="form.errors.kode_gudang">{{ form.errors.kode_gudang }}</small>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="nama_gudang" class="form-label">Nama</label>
                        <input id="nama_gudang" name="nama_gudang" v-model="form.nama_gudang" class="form-control"
                            :class="{ 'is-invalid': form.errors.nama_gudang }" placeholder="Gudang Pusat">
                        <small class="text-danger" v-if="form.errors.nama_gudang">{{ form.errors.nama_gudang }}</small>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="alamat_gudang" class="form-label">Alamat</label>
                        <textarea id="alamat_gudang" name="alamat" v-model="form.alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                        <input id="penanggung_jawab" name="penanggung_jawab" v-model="form.penanggung_jawab" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" v-model="form.is_active" id="g-active" name="is_active">
                            <label class="form-check-label" for="g-active">
                                {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" @click="close">Batal</button>
                <button type="submit" class="btn btn-success">{{ isEdit ? 'Update' : 'Simpan' }}</button>
            </div>
        </form>
    </Modal>
</template>

<style scoped>
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}

.is-invalid { border-color: #f46a6a !important; background-color: #fff5f5; }
.row-fade-enter-active { transition: opacity 180ms ease; }
.row-fade-enter-from   { opacity: 0; }
.row-fade-leave-active { display: none; }
</style>
