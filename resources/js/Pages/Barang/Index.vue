<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SearchSelect from '@/Components/SearchSelect.vue';
import Rupiah from '@/Components/Rupiah.vue';

const props = defineProps({
    barangs: { type: Object, required: true },
    filters: { type: Object, required: true },
    masters: { type: Object, default: () => ({ categories: [], merks: [], groups: [] }) },
});


const masters = computed(() => ({
    categories:    props.masters.categories    ?? [],
    subCategories: props.masters.subCategories ?? [],
    merks:         props.masters.merks         ?? [],
    groups:        props.masters.groups        ?? [],
    loading:       false,
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
    searchTimer = setTimeout(reload, 400);
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
    sub_category_code: '',
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
        sub_category_code: item.sub_category_code ?? '',
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
        const sub = masters.value.subCategories.find(s => s.kode_sub_category === editForm.sub_category_code);
        const mrk = masters.value.merks.find(m => m.kode_merk === editForm.merk_code);
        const grp = masters.value.groups.find(g => g.kode_group === editForm.group_code);
        localData.value[idx] = {
            ...localData.value[idx],
            kode_barang:       editForm.kode_barang,
            part_number:       editForm.part_number,
            nama_barang:       editForm.nama_barang,
            category_code:     editForm.category_code,
            sub_category_code: editForm.sub_category_code,
            merk_code:         editForm.merk_code,
            group_code:        editForm.group_code,
            satuan:            editForm.satuan,
            harga_beli:        editForm.harga_beli,
            harga_jual:        editForm.harga_jual,
            min_stok:          editForm.min_stok,
            deskripsi:         editForm.deskripsi,
            kategori:     cat ? { kode_category: cat.kode_category, nama_category: cat.nama_category } : null,
            sub_kategori: sub ? { kode_sub_category: sub.kode_sub_category, nama_sub_category: sub.nama_sub_category } : null,
            merk:        mrk ? { kode_merk: mrk.kode_merk, nama_merk: mrk.nama_merk } : null,
            group:       grp ? { kode_group: grp.kode_group, nama_group: grp.nama_group } : null,
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
// SELECTION (bulk delete)
// =========================================================================
const selected = ref(new Set());

watch(() => props.barangs, () => { selected.value = new Set(); }, { deep: false });

const selectedCount = computed(() => selected.value.size);
const allSelected = computed(() =>
    localData.value.length > 0 && localData.value.every(x => selected.value.has(x.id))
);
const someSelected = computed(() =>
    selectedCount.value > 0 && !allSelected.value
);

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
        const deletables = localData.value.filter(x => idSet.has(x.id) && (x.stok_total ?? 0) === 0);
        const blocked    = ids.length - deletables.length;

        // Semua dipilih masih punya stok → instan error, gak hit server.
        if (!deletables.length) {
            selected.value = new Set();
            window.toast?.error('Semua barang yang dipilih masih punya stok.');
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
            `${removeIds.size} barang dihapus`
            + (blocked ? `, ${blocked} dilewati (masih ada stok)` : '')
        );

        router.delete('/barang/bulk', {
            data: { ids: sendIds },
            preserveScroll: true,
            preserveState:  true,
            only: ['barangs', 'flash'],
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                window.toast?.error('Gagal menghapus');
            },
        });
    };

    if (window.confirmDialog) {
        window.confirmDialog({
            title: `Hapus ${ids.length} barang?`,
            text:  'Barang yang masih punya stok / riwayat mutasi akan dilewati.',
        }).then(ok => { if (ok) doDelete(); });
    } else if (confirm(`Hapus ${ids.length} barang?`)) {
        doDelete();
    }
}

// =========================================================================
// DELETE
// =========================================================================
function destroy(item) {
    const hasStok = (item.stok_total ?? 0) > 0;

    const doDelete = () => {
        // Guard setelah konfirmasi — sama pola dgn MasterCrud (Category/Merk/Group).
        if (hasStok) {
            window.toast?.error(`Barang '${item.nama_barang}' masih punya stok.`);
            return;
        }

        const snapshot  = [...localData.value];
        const snapTotal = localTotal.value;
        const wasSelected = selected.value.has(item.id);
        localData.value  = localData.value.filter(x => x.id !== item.id);
        localTotal.value = Math.max(0, snapTotal - 1);
        if (wasSelected) {
            const next = new Set(selected.value);
            next.delete(item.id);
            selected.value = next;
        }
        window.toast?.success('Barang dihapus');

        router.delete(`/barang/${item.id}`, {
            preserveScroll: true,
            preserveState:  true,
            only: ['barangs', 'flash'],
            onSuccess: (page) => {
                const flashError = page.props?.flash?.error;
                if (flashError) {
                    // Server tolak (mis. ada riwayat mutasi). Rollback diam-diam.
                    localData.value  = snapshot;
                    localTotal.value = snapTotal;
                    if (wasSelected) {
                        const next = new Set(selected.value);
                        next.add(item.id);
                        selected.value = next;
                    }
                    window.toast?.error(flashError);
                } else {
                    router.flushAll();
                }
            },
            onError: () => {
                localData.value  = snapshot;
                localTotal.value = snapTotal;
                if (wasSelected) {
                    const next = new Set(selected.value);
                    next.add(item.id);
                    selected.value = next;
                }
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

</script>

<template>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body border">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 card-title flex-grow-1">BARANG</h5>
                        <div class="flex-shrink-0 d-flex gap-2">
                            <button
                                v-if="selectedCount > 0"
                                type="button"
                                class="btn btn-danger btn-rounded"
                                @click="bulkDestroy"
                            >
                                <i class="mdi mdi-trash-can-outline me-1"></i>Hapus ({{ selectedCount }})
                            </button>
                            <Link href="/barang/import" prefetch class="btn btn-info btn-rounded">
                                <i class="bx bx-upload me-1"></i>Import Excel
                            </Link>
                            <Link href="/barang/create" prefetch class="btn btn-success btn-rounded">
                                <i class="mdi mdi-plus me-1"></i>Tambah Barang
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-lg-4 col-md-6">
                            <label for="search_barang" class="form-label mb-1 small fw-medium">Pencarian</label>
                            <div class="search-box">
                                <div class="position-relative">
                                    <input
                                        id="search_barang"
                                        name="search"
                                        v-model="search"
                                        type="text"
                                        class="form-control"
                                        placeholder="Cari kode / nama / part number..."
                                        style="padding-left: 36px; height: 38px;"
                                        autocomplete="off"
                                        aria-label="Cari kode, nama, atau part number barang"
                                    >
                                    <i class="bx bx-search-alt search-icon" style="left: 12px; font-size: 18px;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-auto col-md-auto ms-auto">
                            <label class="form-label mb-1 small fw-medium d-block">&nbsp;</label>
                            <div class="d-flex gap-2 align-items-center">
                                <small class="text-muted">
                                    Total: <strong>{{ displayBarangs.total?.toLocaleString('id-ID') }}</strong>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 36px;">
                                        <input
                                            id="select_all_barang"
                                            name="select_all"
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="allSelected"
                                            :indeterminate.prop="someSelected"
                                            aria-label="Pilih semua barang"
                                            @change="toggleAll"
                                        >
                                    </th>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode</th>
                                    <th>Part Number</th>
                                    <th>Nama</th>
                                    <th>Kategori</th>
                                    <th>Sub Kategori</th>
                                    <th>Merk</th>
                                    <th>Group</th>
                                    <th>Satuan</th>
                                    <th>Total Stok</th>
                                    <th>Harga Jual</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" :name="animateRows ? 'row-fade' : ''">
                                <tr v-for="(item, i) in displayBarangs.data" :key="item.id" :class="{ 'table-active': selected.has(item.id) }">
                                    <td>
                                        <input
                                            :id="`select_barang_${item.id}`"
                                            name="select_barang"
                                            type="checkbox"
                                            class="form-check-input"
                                            :checked="selected.has(item.id)"
                                            :aria-label="`Pilih ${item.nama_barang}`"
                                            @change="toggleOne(item.id)"
                                        >
                                    </td>
                                    <td>{{ (displayBarangs.current_page - 1) * displayBarangs.per_page + i + 1 }}</td>
                                    <td>{{ item.kode_barang }}</td>
                                    <td>{{ item.part_number }}</td>
                                    <td>{{ item.nama_barang }}</td>
                                    <td>{{ item.kategori?.nama_category }}</td>
                                    <td>{{ item.sub_kategori?.nama_sub_category ?? '-' }}</td>
                                    <td>{{ item.merk?.nama_merk }}</td>
                                    <td>{{ item.group?.nama_group }}</td>
                                    <td>{{ item.satuan ?? '-' }}</td>
                                    <td>
                                        <span class="badge rounded-pill badge-soft-info font-size-12">
                                            {{ item.stok_total ?? 0 }}
                                        </span>
                                    </td>
                                    <td><Rupiah :value="item.harga_jual" /></td>
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
                                    <td colspan="13" class="text-center text-muted py-4">Tidak ada data</td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                Menampilkan {{ displayBarangs.from ?? 0 }}–{{ displayBarangs.to ?? 0 }} dari {{ displayBarangs.total }}
                            </small>
                            <select id="per_page_barang" name="per_page" v-model="perPage" @change="changePerPage(perPage)" class="form-select form-select-sm" style="width: 70px;" aria-label="Jumlah data per halaman">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
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
                        <label for="kode_barang_edit" class="form-label">Kode</label>
                        <input id="kode_barang_edit" name="kode_barang" v-model="editForm.kode_barang" class="form-control" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="part_number_edit" class="form-label">Part Number</label>
                        <input id="part_number_edit" name="part_number" v-model="editForm.part_number" class="form-control"
                            :class="{ 'is-invalid': editForm.errors.part_number }">
                        <small class="text-danger" v-if="editForm.errors.part_number">{{ editForm.errors.part_number }}</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="nama_barang_edit" class="form-label">Nama</label>
                        <input id="nama_barang_edit" name="nama_barang" v-model="editForm.nama_barang" class="form-control"
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
                        <label class="form-label">Sub Kategori</label>
                        <SearchSelect
                            v-model="editForm.sub_category_code"
                            :options="masters.subCategories"
                            option-value="kode_sub_category"
                            option-label="nama_sub_category"
                            placeholder="Pilih Sub Kategori"
                            search-placeholder="Cari sub kategori..."
                            :loading="masters.loading"
                            :invalid="!!editForm.errors.sub_category_code"
                        />
                        <small class="text-danger" v-if="editForm.errors.sub_category_code">{{ editForm.errors.sub_category_code }}</small>
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
                        <label for="satuan_edit" class="form-label">Satuan</label>
                        <input id="satuan_edit" name="satuan" v-model="editForm.satuan" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="harga_beli_edit" class="form-label">Harga Beli</label>
                        <input id="harga_beli_edit" name="harga_beli" v-model="editForm.harga_beli" type="number" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="harga_jual_edit" class="form-label">Harga Jual</label>
                        <input id="harga_jual_edit" name="harga_jual" v-model="editForm.harga_jual" type="number" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="min_stok_edit" class="form-label">Min Stok</label>
                        <input id="min_stok_edit" name="min_stok" v-model="editForm.min_stok" type="number" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="deskripsi_edit" class="form-label">Deskripsi</label>
                        <textarea id="deskripsi_edit" name="deskripsi" v-model="editForm.deskripsi" class="form-control" rows="2"></textarea>
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
/* Pastikan semua input berbentuk kotak (tidak bulat) */
.form-control,
.form-select {
    border-radius: 0.25rem !important;
}

.is-invalid {
    border-color: #f46a6a !important;
    background-color: #fff5f5;
}

.row-fade-enter-active { transition: opacity 180ms ease; }
.row-fade-enter-from   { opacity: 0; }
/* Leave: instan — kalau di-fade dulu, row di bawah baru snap ke atas → kejut. */
.row-fade-leave-active { display: none; }
</style>
