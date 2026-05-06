<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SearchSelect from '@/Components/SearchSelect.vue';

const props = defineProps({
    barangs: { type: Object, required: true },
    filters: { type: Object, required: true },
    masters: { type: Object, default: () => ({ categories: [], merks: [], groups: [] }) },
});

const masters = computed(() => ({
    categories: props.masters.categories ?? [],
    merks:      props.masters.merks      ?? [],
    groups:     props.masters.groups     ?? [],
    loading:    false,
}));

defineOptions({ layout: AppLayout });

// --- OPTIMISTIC mirror -------------------------------------------------------
const localData  = ref([...(props.barangs.data ?? [])]);
const localTotal = ref(props.barangs.total ?? 0);
const animateRows = ref(true);

watch(() => props.barangs, (val) => {
    animateRows.value = false;
    localData.value  = [...(val.data ?? [])];
    localTotal.value = val.total ?? 0;
    nextTick(() => { animateRows.value = true; });
}, { deep: false });

const displayBarangs = computed(() => ({
    ...props.barangs,
    data:  localData.value,
    total: localTotal.value,
}));

// --- search & perPage (URL-synced, debounced) -----------------------------
const search = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 25);
let searchTimer = null;

function reload(extra = {}) {
    router.get(
        '/barang',
        { search: search.value, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, replace: true, only: ['barangs', 'filters'] }
    );
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(reload, 300);
});

function changePerPage(n) {
    perPage.value = n;
    reload();
}

// =========================================================================
// EDIT (Single)
// =========================================================================
const showEditModal = ref(false);
const editForm = useForm({
    id: null,
    kode_barang: '',
    part_number: '',
    nama_barang: '',
    category_code: '',
    merk_code: '',
    group_code: '',
    satuan: 'pcs',
    harga_beli: 0,
    harga_jual: 0,
    min_stok: 0,
    deskripsi: '',
});

function openEdit(item) {
    editForm.clearErrors();
    Object.assign(editForm, {
        id: item.id,
        kode_barang: item.kode_barang ?? '',
        part_number: item.part_number ?? '',
        nama_barang: item.nama_barang ?? '',
        category_code: item.category_code ?? '',
        merk_code: item.merk_code ?? '',
        group_code: item.group_code ?? '',
        satuan: item.satuan ?? 'pcs',
        harga_beli: item.harga_beli ?? 0,
        harga_jual: item.harga_jual ?? 0,
        min_stok: item.min_stok ?? 0,
        deskripsi: item.deskripsi ?? '',
    });
    showEditModal.value = true;
}

function closeEdit() { showEditModal.value = false; }

function submitEdit() {
    // OPTIMISTIC: update lokal duluan, tutup modal seketika.
    const snapshot = [...localData.value];
    const idx = localData.value.findIndex(x => x.id === editForm.id);
    if (idx !== -1) {
        const cat = masters.value.categories.find(c => c.kode_category === editForm.category_code);
        const mrk = masters.value.merks.find(m => m.kode_merk === editForm.merk_code);
        const grp = masters.value.groups.find(g => g.kode_group === editForm.group_code);
        localData.value[idx] = {
            ...localData.value[idx],
            kode_barang:   editForm.kode_barang,
            part_number:   editForm.part_number,
            nama_barang:   editForm.nama_barang,
            category_code: editForm.category_code,
            merk_code:     editForm.merk_code,
            group_code:    editForm.group_code,
            satuan:        editForm.satuan,
            harga_beli:    editForm.harga_beli,
            harga_jual:    editForm.harga_jual,
            min_stok:      editForm.min_stok,
            deskripsi:     editForm.deskripsi,
            kategori: cat ? { kode_category: cat.kode_category, nama_category: cat.nama_category } : null,
            merk:     mrk ? { kode_merk: mrk.kode_merk, nama_merk: mrk.nama_merk } : null,
            group:    grp ? { kode_group: grp.kode_group, nama_group: grp.nama_group } : null,
        };
    }
    showEditModal.value = false;
    window.toast?.success('Barang diubah');

    editForm.put(`/barang/${editForm.id}`, {
        preserveScroll: true,
        preserveState:  true,
        only: ['barangs', 'errors'],
        onSuccess: () => router.flushAll(),
        onError: () => {
            localData.value = snapshot;
            showEditModal.value = true;
            window.toast?.error('Gagal menyimpan, periksa form');
        },
    });
}

// =========================================================================
// DELETE
// =========================================================================
function destroy(item) {
    const doDelete = () => {
        const snapshot  = [...localData.value];
        const snapTotal = localTotal.value;
        localData.value  = localData.value.filter(x => x.id !== item.id);
        localTotal.value = Math.max(0, snapTotal - 1);
        window.toast?.success('Barang dihapus');

        router.delete(`/barang/${item.id}`, {
            preserveScroll: true,
            preserveState:  true,
            only: ['barangs'],
            onSuccess: () => router.flushAll(),
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${item.nama_barang}?`,
            text:  'Data tidak bisa dikembalikan',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${item.nama_barang}?`)) {
        doDelete();
    }
}

function fmtRp(v) {
    if (v === null || v === undefined || v === '') return 'Rp 0';
    return 'Rp ' + Number(v).toLocaleString('id-ID');
}
</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">BARANG</h5>
                        <div class="flex-shrink-0">
                            <Link href="/barang/create" prefetch class="btn btn-success btn-rounded">
                                <i class="mdi mdi-plus me-1"></i>Tambah Barang
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-sm-8">
                            <div class="d-flex align-items-center gap-2">
                                <div class="search-box">
                                    <div class="position-relative">
                                        <input
                                            v-model="search"
                                            type="text"
                                            class="form-control btn-rounded"
                                            placeholder="Cari kode / nama / part number..."
                                            style="padding-left: 40px;"
                                        >
                                        <i class="bx bx-search-alt search-icon" style="left: 13px;"></i>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button
                                        class="btn btn-light btn-rounded shadow-sm border dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        style="min-width: 70px;"
                                    >
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
                                    Total: <strong>{{ displayBarangs.total?.toLocaleString('id-ID') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-check">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode</th>
                                    <th>Part Number</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Merk</th>
                                    <th>Group</th>
                                    <th>Satuan</th>
                                    <th>Total Stok</th>
                                    <th>Harga Jual</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" :name="animateRows ? 'row-fade' : ''">
                                <tr v-for="(item, i) in displayBarangs.data" :key="item.id">
                                    <td>{{ (displayBarangs.current_page - 1) * displayBarangs.per_page + i + 1 }}</td>
                                    <td>{{ item.kode_barang }}</td>
                                    <td>{{ item.part_number }}</td>
                                    <td>{{ item.nama_barang }}</td>
                                    <td>{{ item.kategori?.nama_category }}</td>
                                    <td>{{ item.merk?.nama_merk }}</td>
                                    <td>{{ item.group?.nama_group }}</td>
                                    <td>{{ item.satuan ?? '-' }}</td>
                                    <td>
                                        <span class="badge rounded-pill badge-soft-info font-size-12">
                                            {{ item.stok_total ?? 0 }}
                                        </span>
                                    </td>
                                    <td>{{ fmtRp(item.harga_jual) }}</td>
                                    <td>
                                        <Link :href="`/barang/${item.id}`"
                                            class="btn btn-sm btn-soft-primary border-0 shadow-sm bx bx-show font-size-16"
                                            title="Detail">
                                        </Link>
                                        <button
                                            class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16 ms-1"
                                            @click="openEdit(item)"
                                        ></button>
                                        <button
                                            class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16 ms-1"
                                            @click="destroy(item)"
                                        ></button>
                                    </td>
                                </tr>
                                <tr v-if="!displayBarangs.data.length" key="empty">
                                    <td colspan="11" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Menampilkan {{ displayBarangs.from ?? 0 }}–{{ displayBarangs.to ?? 0 }} dari {{ displayBarangs.total }}
                        </small>
                        <Pagination :links="displayBarangs.links" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============= MODAL EDIT (SINGLE) ============= -->
    <Modal :show="showEditModal" title="Edit Barang" size="modal-lg" @close="closeEdit">
        <form @submit.prevent="submitEdit">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kode</label>
                        <input v-model="editForm.kode_barang" class="form-control" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Part Number</label>
                        <input v-model="editForm.part_number" class="form-control"
                            :class="{ 'is-invalid': editForm.errors.part_number }">
                        <small class="text-danger" v-if="editForm.errors.part_number">{{ editForm.errors.part_number }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nama</label>
                        <input v-model="editForm.nama_barang" class="form-control"
                            :class="{ 'is-invalid': editForm.errors.nama_barang }">
                        <small class="text-danger" v-if="editForm.errors.nama_barang">{{ editForm.errors.nama_barang }}</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Category</label>
                        <SearchSelect
                            v-model="editForm.category_code"
                            :options="masters.categories"
                            option-value="kode_category"
                            option-label="nama_category"
                            placeholder="Pilih Kategori"
                            search-placeholder="Cari kategori..."
                            :loading="masters.loading"
                            :invalid="!!editForm.errors.category_code"
                        />
                        <small class="text-danger" v-if="editForm.errors.category_code">{{ editForm.errors.category_code }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Merk</label>
                        <SearchSelect
                            v-model="editForm.merk_code"
                            :options="masters.merks"
                            option-value="kode_merk"
                            option-label="nama_merk"
                            placeholder="Pilih Merk"
                            search-placeholder="Cari merk..."
                            :loading="masters.loading"
                            :invalid="!!editForm.errors.merk_code"
                        />
                        <small class="text-danger" v-if="editForm.errors.merk_code">{{ editForm.errors.merk_code }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Group</label>
                        <SearchSelect
                            v-model="editForm.group_code"
                            :options="masters.groups"
                            option-value="kode_group"
                            option-label="nama_group"
                            placeholder="Pilih Group"
                            search-placeholder="Cari group..."
                            :loading="masters.loading"
                            :invalid="!!editForm.errors.group_code"
                        />
                        <small class="text-danger" v-if="editForm.errors.group_code">{{ editForm.errors.group_code }}</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Satuan</label>
                        <input v-model="editForm.satuan" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Harga Beli</label>
                        <input v-model="editForm.harga_beli" type="number" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Harga Jual</label>
                        <input v-model="editForm.harga_jual" type="number" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Min Stok</label>
                        <input v-model="editForm.min_stok" type="number" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea v-model="editForm.deskripsi" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" @click="closeEdit">Batal</button>
                <button type="submit" class="btn btn-success">Update</button>
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
/* Leave: instan — kalau di-fade dulu, row di bawah baru snap ke atas → kejut. */
.row-fade-leave-active { display: none; }
</style>
