<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    permissions: { type: Object, required: true },
});

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    display_name: '',
    description: '',
    is_active: true,
    permissions: [],
});

const moduleLabels = {
    dashboard: 'Dashboard', barang: 'Master Barang', category: 'Kategori',
    sub_category: 'Sub Kategori', merk: 'Merk', group: 'Group',
    gudang: 'Gudang', supplier: 'Supplier', mutasi: 'Transaksi & Mutasi',
    stok: 'Stok Gudang', laporan: 'Laporan', user: 'User Management',
    role: 'Role Management', other: 'Lainnya',
};

const actionLabels = {
    view: 'Lihat', create: 'Tambah', edit: 'Edit', update: 'Update',
    delete: 'Hapus', restore: 'Pulihkan', 'force-delete': 'Hapus Permanen',
    export: 'Export', import: 'Import', print: 'Cetak',
    approve: 'Setujui', reject: 'Tolak', manage: 'Kelola', access: 'Akses',
};

const getPermissionLabel = (name) => {
    if (!name) return '';
    const parts = name.split('.');
    if (parts.length === 2) {
        return actionLabels[parts[1]] || parts[1];
    }
    return actionLabels[parts[0]] || name;
};

const searchQuery = ref('');
const expandedModules = ref([]);

const toggleExpand = (module) => {
    const i = expandedModules.value.indexOf(module);
    if (i > -1) expandedModules.value.splice(i, 1);
    else expandedModules.value.push(module);
};
const isExpanded = (module) => expandedModules.value.includes(module);

const filteredPermissions = computed(() => {
    if (!searchQuery.value) return props.permissions;
    const q = searchQuery.value.toLowerCase();
    const result = {};
    Object.keys(props.permissions).forEach(module => {
        const list = Array.isArray(props.permissions[module]) ? props.permissions[module] : [];
        const matches = list.filter(p =>
            p.name.toLowerCase().includes(q) ||
            getPermissionLabel(p.name).toLowerCase().includes(q) ||
            (moduleLabels[module] || module).toLowerCase().includes(q)
        );
        if (matches.length > 0) result[module] = matches;
    });
    return result;
});

const totalPermissions = computed(() =>
    Object.values(props.permissions).reduce((sum, perms) => sum + (Array.isArray(perms) ? perms.length : 0), 0)
);

const moduleStats = (module) => {
    const list = props.permissions[module] || [];
    const selected = list.filter(p => form.permissions.includes(p.id)).length;
    return { total: list.length, selected };
};

const isModuleSelected = (module) => {
    const s = moduleStats(module);
    return s.total > 0 && s.selected === s.total;
};

const isModulePartial = (module) => {
    const s = moduleStats(module);
    return s.selected > 0 && s.selected < s.total;
};

const toggleModule = (module) => {
    const ids = props.permissions[module].map(p => p.id);
    if (isModuleSelected(module)) {
        form.permissions = form.permissions.filter(id => !ids.includes(id));
    } else {
        form.permissions = [...new Set([...form.permissions, ...ids])];
    }
};

const selectAll = () => {
    form.permissions = Object.values(props.permissions).flat().map(p => p.id);
};

const deselectAll = () => { form.permissions = []; };

function submit() {
    form.post('/role', {
        preserveScroll: true,
        onSuccess: () => {
            window.toast?.success('Role berhasil ditambahkan');
        },
        onError: () => {
            window.toast?.error('Gagal menambahkan role');
        },
    });
}
</script>

<template>
    <Head title="Tambah Role" />

    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold">
                            <i class="bx bx-plus-circle me-2"></i>Tambah Role
                        </h4>
                        <p class="text-muted small mb-0">Buat role baru beserta permissions-nya</p>
                    </div>
                    <Link href="/role" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Kembali
                    </Link>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" novalidate>
            <div class="row">
                <!-- Main Form -->
                <div class="col-lg-8">
                    <!-- Info Role -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="bx bx-info-circle me-1"></i>Informasi Role
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        Nama Role <span class="text-danger">*</span>
                                    </label>
                                    <input v-model="form.name" type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.name }"
                                        placeholder="manager" required>
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                    <small class="text-muted">Slug unik (huruf kecil, tanpa spasi)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium">
                                        Display Name <span class="text-danger">*</span>
                                    </label>
                                    <input v-model="form.display_name" type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.display_name }"
                                        placeholder="Manager Gudang" required>
                                    <div v-if="form.errors.display_name" class="invalid-feedback">
                                        {{ form.errors.display_name }}
                                    </div>
                                </div>

                                <div class="col-md-9">
                                    <label class="form-label fw-medium">Deskripsi</label>
                                    <input v-model="form.description" type="text"
                                        class="form-control"
                                        placeholder="Deskripsi singkat role ini">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Status</label>
                                    <div class="form-check form-switch">
                                        <input id="is_active" v-model="form.is_active"
                                            type="checkbox" class="form-check-input" role="switch">
                                        <label for="is_active" class="form-check-label">
                                            {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <h6 class="fw-bold mb-0">
                                    <i class="bx bx-key me-1"></i>Permissions
                                    <span class="text-muted fw-normal small ms-1">({{ form.permissions.length }}/{{ totalPermissions }})</span>
                                </h6>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        @click="expandedModules = Object.keys(filteredPermissions)">
                                        <i class="bx bx-expand-alt me-1"></i>Buka Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        @click="expandedModules = []">
                                        <i class="bx bx-collapse-alt me-1"></i>Tutup Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" @click="selectAll">
                                        <i class="bx bx-check-double me-1"></i>Pilih Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="deselectAll">
                                        <i class="bx bx-x-circle me-1"></i>Kosongkan
                                    </button>
                                </div>
                            </div>

                            <input v-model="searchQuery" type="text" class="form-control form-control-sm mb-3"
                                placeholder="Cari permission...">

                            <div v-if="form.errors.permissions" class="alert alert-danger py-2 small">
                                {{ form.errors.permissions }}
                            </div>

                            <div v-if="Object.keys(filteredPermissions).length === 0" class="text-center text-muted py-4">
                                <i class="bx bx-search-alt fs-3 d-block mb-1"></i>
                                <small>Tidak ada permission yang cocok</small>
                            </div>

                            <div v-for="(perms, module) in filteredPermissions" :key="module" class="permission-group mb-2">
                                <div class="permission-group-header" @click="toggleExpand(module)">
                                    <div class="form-check m-0" @click.stop>
                                        <input
                                            :id="`mod-${module}`"
                                            class="form-check-input"
                                            type="checkbox"
                                            :checked="isModuleSelected(module)"
                                            :indeterminate.prop="isModulePartial(module)"
                                            @change="toggleModule(module)">
                                    </div>
                                    <div class="flex-grow-1 ms-2">
                                        <span class="fw-semibold">{{ moduleLabels[module] || module }}</span>
                                        <span class="text-muted small fw-normal ms-1">
                                            ({{ moduleStats(module).selected }}/{{ perms.length }})
                                        </span>
                                    </div>
                                    <i class="bx bx-chevron-down chevron" :class="{ rotated: isExpanded(module) }"></i>
                                </div>
                                <div v-show="isExpanded(module)" class="permission-group-body">
                                    <div class="row g-2">
                                        <div v-for="p in perms" :key="p.id" class="col-md-6 col-lg-4">
                                            <div class="form-check">
                                                <input
                                                    :id="`perm-${p.id}`"
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :value="p.id"
                                                    v-model="form.permissions">
                                                <label :for="`perm-${p.id}`" class="form-check-label small">
                                                    {{ getPermissionLabel(p.name) }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <Link href="/role" class="btn btn-secondary">Batal</Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bx bx-save me-1"></i>
                                    Simpan Role
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Sidebar -->
                <div class="col-lg-4">
                    <div class="card shadow-sm bg-light">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="bx bx-info-circle me-1"></i>Petunjuk
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2"><strong>Nama Role</strong> adalah slug unik untuk referensi sistem (contoh: <code>manager</code>).</li>
                                <li class="mb-2"><strong>Display Name</strong> adalah label yang muncul untuk user.</li>
                                <li class="mb-2">Pilih permissions per modul, atau gunakan tombol <em>Pilih Semua</em>.</li>
                                <li>Role nonaktif tidak bisa dipakai user baru.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<style scoped>
.permission-group {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.permission-group-header {
    display: flex;
    align-items: center;
    padding: 0.6rem 0.85rem;
    cursor: pointer;
    transition: background 0.15s;
}
.permission-group-header:hover {
    background: #f8f9fa;
}
.permission-group-body {
    padding: 0.75rem 1rem 1rem;
    border-top: 1px solid #f1f3f5;
    background: #fbfbfc;
}
.chevron {
    transition: transform 0.2s;
    color: #adb5bd;
    font-size: 1.2rem;
}
.chevron.rotated {
    transform: rotate(180deg);
}
</style>
