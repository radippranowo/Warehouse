<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    suppliers: { type: Object, required: true },
    filters: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const localData  = ref([...(props.suppliers.data ?? [])]);
const localTotal = ref(props.suppliers.total ?? 0);
const animateRows = ref(true);

watch(() => props.suppliers, (val) => {
    animateRows.value = false;
    localData.value  = [...(val.data ?? [])];
    localTotal.value = val.total ?? 0;
    nextTick(() => { animateRows.value = true; });
}, { deep: false });

const displayItems = computed(() => ({
    ...props.suppliers,
    data:  localData.value,
    total: localTotal.value,
}));

const search  = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
let timer = null;

function reload(extra = {}) {
    router.get('/supplier',
        { search: search.value, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, replace: true, only: ['suppliers', 'filters'] }
    );
}
watch(search, () => { clearTimeout(timer); timer = setTimeout(reload, 300); });
function changePerPage(n) { perPage.value = n; reload(); }

const showModal = ref(false);
const isEdit    = ref(false);
const form = useForm({
    id: null,
    kode_supplier: '',
    nama_supplier: '',
    kontak: '',
    telepon: '',
    alamat: '',
    keterangan: '',
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
        kode_supplier: item.kode_supplier ?? '',
        nama_supplier: item.nama_supplier ?? '',
        kontak: item.kontak ?? '',
        telepon: item.telepon ?? '',
        alamat: item.alamat ?? '',
        keterangan: item.keterangan ?? '',
        is_active: item.is_active ?? true,
    });
    showModal.value = true;
}

function close() { showModal.value = false; }

function submit() {
    const editing     = isEdit.value;
    const payloadId   = form.id;
    const payloadKode = form.kode_supplier;
    const payloadNama = form.nama_supplier;

    // Client-side validation
    const errors = {};
    if (!payloadKode?.toString().trim()) {
        errors.kode_supplier = 'Kode wajib diisi';
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x.kode_supplier).toLowerCase() === String(payloadKode).toLowerCase())) {
        errors.kode_supplier = 'Kode sudah ada';
    }
    if (!payloadNama?.toString().trim()) {
        errors.nama_supplier = 'Nama wajib diisi';
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x.nama_supplier).toLowerCase() === String(payloadNama).toLowerCase())) {
        errors.nama_supplier = 'Nama sudah ada';
    }
    if (Object.keys(errors).length) {
        form.clearErrors();
        form.setError(errors);
        return;
    }

    // Snapshot untuk rollback
    const dataSnapshot  = [...localData.value];
    const totalSnapshot = localTotal.value;

    if (editing) {
        const idx = localData.value.findIndex(x => x.id === payloadId);
        if (idx !== -1) {
            localData.value[idx] = {
                ...localData.value[idx],
                kode_supplier: form.kode_supplier,
                nama_supplier: form.nama_supplier,
                kontak:        form.kontak,
                telepon:       form.telepon,
                alamat:        form.alamat,
                keterangan:    form.keterangan,
                is_active:     form.is_active,
            };
        }
    } else {
        // Optimistic insert
        localData.value = [{
            id: null,
            kode_supplier: form.kode_supplier,
            nama_supplier: form.nama_supplier,
            kontak:        form.kontak,
            telepon:       form.telepon,
            alamat:        form.alamat,
            keterangan:    form.keterangan,
            is_active:     form.is_active,
        }, ...localData.value];
        localTotal.value = totalSnapshot + 1;
    }

    showModal.value = false;
    window.toast?.success(`Supplier ${editing ? 'diubah' : 'ditambah'}`);

    const opts = {
        preserveScroll: true,
        preserveState:  true,
        only: ['suppliers', 'errors'],
        onSuccess: () => {
            router.flushAll();
            form.reset();
        },
        onError: () => {
            // Rollback optimistic
            localData.value  = dataSnapshot;
            localTotal.value = totalSnapshot;
            showModal.value  = true;
            window.toast?.error('Gagal Simpan');
        },
    };

    if (editing) form.put(`/supplier/${payloadId}`, opts);
    else         form.post('/supplier', opts);
}

// === SELECTION (bulk delete) =================================================
const selected = ref(new Set());
watch(() => props.suppliers, () => { selected.value = new Set(); }, { deep: false });

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
        const snapshot   = [...localData.value];
        const snapTotal  = localTotal.value;
        const removeIds  = new Set(ids);

        localData.value  = localData.value.filter(x => !removeIds.has(x.id));
        localTotal.value = Math.max(0, snapTotal - removeIds.size);
        selected.value   = new Set();

        window.toast?.success(`${removeIds.size} supplier dihapus`);

        router.delete('/supplier/bulk', {
            data: { ids },
            preserveScroll: true,
            preserveState:  true,
            only: ['suppliers'],
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${ids.length} supplier?`,
            text:  'Data tidak bisa dikembalikan.',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${ids.length} supplier?`)) {
        doDelete();
    }
}

function destroy(item) {
    const doDelete = () => {
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
        window.toast?.success('Supplier dihapus');

        const restoreSelection = () => {
            if (wasSelected) {
                const next = new Set(selected.value);
                next.add(item.id);
                selected.value = next;
            }
        };

        router.delete(`/supplier/${item.id}`, {
            preserveScroll: true,
            preserveState: true,
            only: ['suppliers', 'flash'],
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
            title: `Hapus ${item.nama_supplier}?`,
            text:  'Data tidak bisa dikembalikan',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${item.nama_supplier}?`)) {
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
                        <h5 class="mb-0 card-title flex-grow-1">SUPPLIER</h5>
                        <div class="flex-shrink-0 d-flex gap-2">
                            <button v-if="selectedCount > 0" type="button"
                                class="btn btn-danger btn-rounded" @click="bulkDestroy">
                                <i class="mdi mdi-trash-can-outline me-1"></i>Hapus ({{ selectedCount }})
                            </button>
                            <button class="btn btn-success btn-rounded" @click="openCreate">
                                <i class="mdi mdi-plus me-1"></i>Tambah Supplier
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-2">
                                <div class="search-box">
                                    <div class="position-relative">
                                        <input id="search_supplier" name="search" v-model="search" type="text" class="form-control btn-rounded"
                                            placeholder="Cari kode / nama / kontak..." style="padding-left: 40px;" aria-label="Cari supplier">
                                        <i class="bx bx-search-alt search-icon" style="left: 13px;"></i>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-light btn-rounded shadow-sm border dropdown-toggle"
                                        type="button" data-bs-toggle="dropdown" style="min-width: 70px;">
                                        {{ perPage }} <i class="mdi mdi-chevron-down ms-1"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow rounded-4 border-0 mt-2">
                                        <li v-for="n in [10, 25, 50, 100]" :key="n">
                                            <a class="dropdown-item rounded-3" href="javascript:void(0);"
                                                @click="changePerPage(n)">{{ n }}</a>
                                        </li>
                                    </ul>
                                </div>
                                <small class="text-muted ms-2">
                                    Total: <strong>{{ displayItems.total?.toLocaleString('id-ID') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap">
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
                                    <th>Nama Supplier</th>
                                    <th>Kontak</th>
                                    <th>Telepon</th>
                                    <th>Status</th>
                                    <th style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" :name="animateRows ? 'row-fade' : ''">
                                <tr v-for="(item, i) in displayItems.data" :key="item.id ?? item.kode_supplier"
                                    :class="{ 'table-active': selected.has(item.id) }">
                                    <td>
                                        <input type="checkbox" class="form-check-input"
                                            :checked="selected.has(item.id)"
                                            @change="toggleOne(item.id)">
                                    </td>
                                    <td>{{ (displayItems.current_page - 1) * displayItems.per_page + i + 1 }}</td>
                                    <td>{{ item.kode_supplier }}</td>
                                    <td>{{ item.nama_supplier }}</td>
                                    <td>{{ item.kontak || '-' }}</td>
                                    <td>{{ item.telepon || '-' }}</td>
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
                                <tr v-if="!displayItems.data.length" key="empty">
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ displayItems.from ?? 0 }}–{{ displayItems.to ?? 0 }} dari {{ displayItems.total }}
                        </small>
                        <Pagination :links="displayItems.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Modal :show="showModal" :title="(isEdit ? 'Edit ' : 'Tambah ') + 'Supplier'" size="modal-lg" @close="close">
        <form @submit.prevent="submit">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="kode_supplier" class="form-label">Kode</label>
                        <input id="kode_supplier" name="kode_supplier" v-model="form.kode_supplier" class="form-control" :disabled="isEdit"
                            :class="{ 'is-invalid': form.errors.kode_supplier }" placeholder="SUP-01">
                        <small class="text-danger" v-if="form.errors.kode_supplier">{{ form.errors.kode_supplier }}</small>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="nama_supplier" class="form-label">Nama Supplier</label>
                        <input id="nama_supplier" name="nama_supplier" v-model="form.nama_supplier" class="form-control"
                            :class="{ 'is-invalid': form.errors.nama_supplier }" placeholder="PT. Supplier ABC">
                        <small class="text-danger" v-if="form.errors.nama_supplier">{{ form.errors.nama_supplier }}</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="kontak_supplier" class="form-label">Kontak Person</label>
                        <input id="kontak_supplier" name="kontak" v-model="form.kontak" class="form-control" placeholder="Nama kontak">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="telepon_supplier" class="form-label">Telepon</label>
                        <input id="telepon_supplier" name="telepon" v-model="form.telepon" class="form-control" placeholder="08123456789">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="alamat_supplier" class="form-label">Alamat</label>
                        <textarea id="alamat_supplier" name="alamat" v-model="form.alamat" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="keterangan_supplier" class="form-label">Keterangan</label>
                        <textarea id="keterangan_supplier" name="keterangan" v-model="form.keterangan" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-12 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" v-model="form.is_active" id="s-active" name="is_active">
                            <label class="form-check-label" for="s-active">
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
.is-invalid { border-color: #f46a6a !important; background-color: #fff5f5; }
.row-fade-enter-active { transition: opacity 180ms ease; }
.row-fade-enter-from   { opacity: 0; }
.row-fade-leave-active { display: none; }
</style>
