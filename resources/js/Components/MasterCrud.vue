<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    title:     { type: String, required: true },
    baseUrl:   { type: String, required: true },
    pageProp:  { type: String, required: true },
    items:     { type: Object, required: true },
    filters:   { type: Object, required: true },
    fieldKode: { type: String, required: true },
    fieldNama: { type: String, required: true },
    labelKode: { type: String, default: 'Kode' },
    labelNama: { type: String, default: 'Nama' },
});

// Optimistic mirror — biar UI seketika tanpa nunggu round-trip
const localData   = ref([...(props.items.data ?? [])]);
const localTotal  = ref(props.items.total ?? 0);
const animateRows = ref(true);

watch(() => props.items, (val) => {
    // Saat data server datang: matikan transisi sebentar agar reconciliation
    // tidak menampilkan double animation (row temp keluar + row real masuk).
    animateRows.value = false;
    localData.value  = [...(val.data ?? [])];
    localTotal.value = val.total ?? 0;
    nextTick(() => { animateRows.value = true; });
}, { deep: false });

const displayItems = computed(() => ({
    ...props.items,
    data:  localData.value,
    total: localTotal.value,
}));

const search  = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
let timer = null;

function reload(extra = {}) {
    router.get(
        props.baseUrl,
        { search: search.value, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, replace: true, only: [props.pageProp, 'filters'] }
    );
}
watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(reload, 300);
});
function changePerPage(n) { perPage.value = n; reload(); }

const showModal = ref(false);
const isEdit    = ref(false);
const form = useForm({
    id: null,
    [props.fieldKode]: '',
    [props.fieldNama]: '',
});

function openCreate() {
    isEdit.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
}

function openEdit(item) {
    isEdit.value = true;
    form.clearErrors();
    form.id = item.id;
    form[props.fieldKode] = item[props.fieldKode];
    form[props.fieldNama] = item[props.fieldNama];
    showModal.value = true;
}

function close() { showModal.value = false; }

function submit() {
    // OPTIMISTIC untuk update DAN insert.
    // Kunci: TransitionGroup pakai :key="item[fieldKode]". Saat server respond,
    // row dengan kode yang sama tetap pakai DOM-node yang sama → no remount.
    const editing     = isEdit.value;
    const payloadId   = form.id;
    const payloadKode = form[props.fieldKode];
    const payloadNama = form[props.fieldNama];

    // === CLIENT-SIDE PRE-VALIDATION ===
    // Cek duplikat kode/nama di data lokal supaya:
    // (1) error langsung tampil inline tanpa flicker round-trip server,
    // (2) optimistic insert tidak bikin key-conflict di TransitionGroup
    //     (dua row dengan kode sama → render bermasalah).
    const errors = {};
    if (!payloadKode?.toString().trim()) {
        errors[props.fieldKode] = `${props.labelKode} wajib diisi`;
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x[props.fieldKode]).toLowerCase() === String(payloadKode).toLowerCase())) {
        errors[props.fieldKode] = `${props.labelKode} sudah ada`;
    }
    if (!payloadNama?.toString().trim()) {
        errors[props.fieldNama] = `${props.labelNama} wajib diisi`;
    } else if (localData.value.some(x => x.id !== payloadId
        && String(x[props.fieldNama]).toLowerCase() === String(payloadNama).toLowerCase())) {
        errors[props.fieldNama] = `${props.labelNama} sudah ada`;
    }
    if (Object.keys(errors).length) {
        form.clearErrors();
        form.setError(errors);
        return;   // modal tetap terbuka, errors tampil inline
    }

    // Snapshot untuk rollback kalau server tolak (mis. duplikat di halaman lain).
    const dataSnapshot  = [...localData.value];
    const totalSnapshot = localTotal.value;

    if (editing) {
        const idx = localData.value.findIndex(x => x.id === payloadId);
        if (idx !== -1) {
            localData.value[idx] = {
                ...localData.value[idx],
                [props.fieldKode]: payloadKode,
                [props.fieldNama]: payloadNama,
            };
        }
    } else {
        // Prepend optimistic. id null → akan ter-update ke id real dari server
        // via watch(props.items). Karena :key pakai kode, DOM-node tetap.
        localData.value = [{
            id: null,
            [props.fieldKode]: payloadKode,
            [props.fieldNama]: payloadNama,
            barangs_count: 0,
        }, ...localData.value];
        localTotal.value = totalSnapshot + 1;
    }

    showModal.value = false;
    window.toast?.success(`${props.title} ${editing ? 'diubah' : 'ditambah'}`);

    const opts = {
        preserveScroll: true,
        preserveState:  true,
        only: [props.pageProp, 'errors'],
        onSuccess: () => {
            router.flushAll();   // invalidate cache halaman lain (mis. /barang).
            form.reset();
        },
        onError: () => {
            // Rollback optimistic.
            localData.value  = dataSnapshot;
            localTotal.value = totalSnapshot;
            // Buka modal lagi → errors otomatis tampil inline via form.errors.
            showModal.value  = true;
            window.toast?.error('Gagal Simpan');
        },
    };

    if (editing) {
        form.put(`${props.baseUrl}/${payloadId}`, opts);
    } else {
        form.post(props.baseUrl, opts);
    }
}

function destroy(item) {
    const nama  = item[props.fieldNama];
    const inUse = (item.barangs_count ?? 0) > 0;

    const doDelete = () => {
        // Kalau masih dipakai barang → tampilkan error setelah konfirmasi.
        if (inUse) {
            window.toast?.error(`${props.title} '${nama}' masih digunakan barang.`);
            return;
        }

        // OPTIMISTIC: hapus lokal dulu, server menyusul.
        const snapshot  = [...localData.value];
        const snapTotal = localTotal.value;
        localData.value  = localData.value.filter(x => x.id !== item.id);
        localTotal.value = Math.max(0, snapTotal - 1);
        window.toast?.success(`${props.title} dihapus`);

        router.delete(`${props.baseUrl}/${item.id}`, {
            preserveScroll: true,
            preserveState:  true,
            only: [props.pageProp, 'flash'],
            onSuccess: (page) => {
                const flashError = page.props?.flash?.error;
                if (flashError) {
                    // Server tolak (race condition: barangs ditambah setelah load).
                    localData.value  = snapshot;
                    localTotal.value = snapTotal;
                    window.toast?.error(flashError);
                } else {
                    // Master berubah → invalidate prefetch cache halaman lain
                    // (terutama /barang yang menampilkan nama master).
                    router.flushAll();
                }
            },
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${nama}?`,
            text:  'Data tidak bisa dikembalikan',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${nama}?`)) {
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
                        <h5 class="mb-0 card-title flex-grow-1">{{ title.toUpperCase() }}</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-success btn-rounded" @click="openCreate">
                                <i class="mdi mdi-plus me-1"></i>Tambah {{ title }}
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
                                        <input v-model="search" type="text" class="form-control btn-rounded"
                                            placeholder="Cari..." style="padding-left: 40px;">
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
                        <table class="table align-middle table-nowrap table-check">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>{{ labelKode }}</th>
                                    <th>{{ labelNama }}</th>
                                    <th style="width: 140px;">Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" :name="animateRows ? 'row-fade' : ''">
                                <tr v-for="(item, i) in displayItems.data" :key="item[fieldKode]">
                                    <td>{{ (displayItems.current_page - 1) * displayItems.per_page + i + 1 }}</td>
                                    <td>{{ item[fieldKode] }}</td>
                                    <td>{{ item[fieldNama] }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                            @click="openEdit(item)"></button>
                                        <button class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16 ms-1"
                                            @click="destroy(item)"></button>
                                    </td>
                                </tr>
                                <tr v-if="!displayItems.data.length" key="empty">
                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data</td>
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

    <Modal :show="showModal" :title="(isEdit ? 'Edit ' : 'Tambah ') + title" @close="close">
        <form @submit.prevent="submit">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ labelKode }}</label>
                        <input v-model="form[fieldKode]" class="form-control"
                            :class="{ 'is-invalid': form.errors[fieldKode] }"
                            :placeholder="labelKode"
                            @input="form.errors[fieldKode] && (delete form.errors[fieldKode])">
                        <small class="text-danger d-block mt-1" v-if="form.errors[fieldKode]">
                            {{ form.errors[fieldKode] }}
                        </small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ labelNama }}</label>
                        <input v-model="form[fieldNama]" class="form-control"
                            :class="{ 'is-invalid': form.errors[fieldNama] }"
                            :placeholder="labelNama"
                            @input="form.errors[fieldNama] && (delete form.errors[fieldNama])">
                        <small class="text-danger d-block mt-1" v-if="form.errors[fieldNama]">
                            {{ form.errors[fieldNama] }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" @click="close">Batal</button>
                <button type="submit" class="btn btn-success">
                    {{ isEdit ? 'Update' : 'Simpan' }}
                </button>
                
            </div>
        </form>
    </Modal>
</template>

<style scoped>
.is-invalid {
    border-color: #f46a6a !important;
    background-color: #fff5f5;
}

.row-fade-enter-active { transition: opacity 180ms ease; }
.row-fade-enter-from   { opacity: 0; }
.row-fade-leave-active { display: none; }
</style>
