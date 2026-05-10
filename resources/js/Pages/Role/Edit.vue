<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    role: Object,
    permissions: Object,
    rolePermissions: Array,
});

const form = useForm({
    name: props.role.name,
    display_name: props.role.display_name,
    description: props.role.description || '',
    is_active: props.role.is_active ?? true,
    permissions: props.rolePermissions || [],
});

const searchQuery = ref('');
const expandedModules = ref([]);

// Module display names with icons
const moduleConfig = {
    'dashboard': { name: 'Dashboard', icon: 'bx-home-circle', color: 'primary' },
    'barang': { name: 'Master Barang', icon: 'bx-package', color: 'success' },
    'category': { name: 'Kategori', icon: 'bx-category', color: 'info' },
    'sub_category': { name: 'Sub Kategori', icon: 'bx-category-alt', color: 'info' },
    'merk': { name: 'Merk', icon: 'bx-purchase-tag', color: 'warning' },
    'group': { name: 'Group', icon: 'bx-group', color: 'secondary' },
    'gudang': { name: 'Gudang', icon: 'bx-building', color: 'primary' },
    'supplier': { name: 'Supplier', icon: 'bx-store', color: 'success' },
    'mutasi': { name: 'Transaksi & Mutasi', icon: 'bx-transfer', color: 'danger' },
    'stok': { name: 'Stok Gudang', icon: 'bx-box', color: 'warning' },
    'laporan': { name: 'Laporan', icon: 'bx-bar-chart-alt-2', color: 'info' },
    'user': { name: 'User Management', icon: 'bx-user', color: 'primary' },
    'role': { name: 'Role Management', icon: 'bx-shield', color: 'danger' },
    'other': { name: 'Lainnya', icon: 'bx-dots-horizontal-rounded', color: 'secondary' },
};

// Function to convert permission name to human-readable format
const getHumanReadableName = (name) => {
    if (!name) return '';
    
    const actionMap = {
        'view': 'Lihat',
        'create': 'Tambah',
        'edit': 'Edit',
        'update': 'Update',
        'delete': 'Hapus',
        'restore': 'Pulihkan',
        'force-delete': 'Hapus Permanen',
        'export': 'Export',
        'import': 'Import',
        'print': 'Cetak',
        'approve': 'Setujui',
        'reject': 'Tolak',
        'manage': 'Kelola',
        'access': 'Akses',
    };

    const moduleMap = {
        'dashboard': 'Dashboard',
        'barang': 'Barang',
        'category': 'Kategori',
        'sub_category': 'Sub Kategori',
        'merk': 'Merk',
        'group': 'Group',
        'gudang': 'Gudang',
        'supplier': 'Supplier',
        'mutasi': 'Mutasi',
        'stok': 'Stok',
        'laporan': 'Laporan',
        'user': 'User',
        'role': 'Role',
        'permission': 'Permission',
        'barang-masuk': 'Barang Masuk',
        'barang-keluar': 'Barang Keluar',
        'penyesuaian-stok': 'Penyesuaian Stok',
    };

    const parts = name.split('.');
    
    if (parts.length === 2) {
        const [module, action] = parts;
        const moduleText = moduleMap[module] || module.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const actionText = actionMap[action] || action.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        return `${actionText} ${moduleText}`;
    } else if (parts.length === 1) {
        const action = parts[0];
        return actionMap[action] || action.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    return name;
};

const filteredPermissions = computed(() => {
    const source = props.permissions;
    if (!searchQuery.value) {
        // Add labels to all permissions
        const result = {};
        Object.keys(source).forEach(module => {
            result[module] = source[module].map(p => ({
                ...p,
                label: getHumanReadableName(p.name)
            }));
        });
        return result;
    }
    
    const query = searchQuery.value.toLowerCase();
    const filtered = {};
    
    Object.keys(source).forEach(module => {
        const modulePerms = source[module].filter(p => {
            const humanName = getHumanReadableName(p.name).toLowerCase();
            return p.name.toLowerCase().includes(query) || 
                   humanName.includes(query) ||
                   (p.display_name && p.display_name.toLowerCase().includes(query));
        }).map(p => ({
            ...p,
            label: getHumanReadableName(p.name)
        }));
        
        if (modulePerms.length > 0) {
            filtered[module] = modulePerms;
        }
    });
    
    return filtered;
});

const totalPermissions = computed(() => {
    return Object.values(props.permissions).reduce((sum, perms) => sum + perms.length, 0);
});

const selectedCount = computed(() => form.permissions.length);

const toggleModule = (module) => {
    const modulePermissions = props.permissions[module].map(p => p.id);
    const allSelected = modulePermissions.every(id => form.permissions.includes(id));
    
    if (allSelected) {
        form.permissions = form.permissions.filter(id => !modulePermissions.includes(id));
    } else {
        const newPermissions = [...form.permissions];
        modulePermissions.forEach(id => {
            if (!newPermissions.includes(id)) {
                newPermissions.push(id);
            }
        });
        form.permissions = newPermissions;
    }
};

const selectAll = () => {
    const allIds = Object.values(props.permissions).flat().map(p => p.id);
    form.permissions = [...allIds];
};

const deselectAll = () => {
    form.permissions = [];
};

const isModuleSelected = (module) => {
    const modulePermissions = props.permissions[module].map(p => p.id);
    return modulePermissions.length > 0 && modulePermissions.every(id => form.permissions.includes(id));
};

const isModulePartiallySelected = (module) => {
    const modulePermissions = props.permissions[module].map(p => p.id);
    const selectedCount = modulePermissions.filter(id => form.permissions.includes(id)).length;
    return selectedCount > 0 && selectedCount < modulePermissions.length;
};

const toggleExpand = (module) => {
    const index = expandedModules.value.indexOf(module);
    if (index > -1) {
        expandedModules.value.splice(index, 1);
    } else {
        expandedModules.value.push(module);
    }
};

const expandAll = () => {
    expandedModules.value = Object.keys(filteredPermissions.value);
};

const collapseAll = () => {
    expandedModules.value = [];
};

const submit = () => {
    form.put(route('role.update', props.role.id), {
        preserveScroll: true,
        onSuccess: () => {
            window.toast?.success('Role berhasil diupdate');
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Edit Role" />

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="bx bx-shield-alt-2 text-primary me-2"></i>Edit Role: {{ role.display_name }}
                                    </h5>
                                    <p class="text-muted small mb-0">Kelola informasi dan permissions untuk role ini</p>
                                </div>
                                <a :href="route('role.index')" class="btn btn-sm btn-outline-secondary">
                                    <i class='bx bx-arrow-back me-1'></i> Kembali
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <!-- Role Information -->
                                <div class="mb-4">
                                    <h6 class="text-uppercase text-muted mb-3">
                                        <i class="bx bx-info-circle me-1"></i>Informasi Role
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                Nama Role <span class="text-danger">*</span>
                                                <i class="bx bx-help-circle text-muted ms-1" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Nama unik untuk role (slug format)"></i>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bx bx-tag"></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control" 
                                                    :class="{ 'is-invalid': form.errors.name }"
                                                    v-model="form.name"
                                                    placeholder="manager"
                                                    required
                                                >
                                            </div>
                                            <small class="text-muted">Gunakan huruf kecil, angka, dan underscore</small>
                                            <div v-if="form.errors.name" class="invalid-feedback d-block">
                                                {{ form.errors.name }}
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">
                                                Display Name <span class="text-danger">*</span>
                                                <i class="bx bx-help-circle text-muted ms-1" 
                                                   data-bs-toggle="tooltip" 
                                                   title="Nama yang ditampilkan ke user"></i>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bx bx-rename"></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control" 
                                                    :class="{ 'is-invalid': form.errors.display_name }"
                                                    v-model="form.display_name"
                                                    placeholder="Manager Gudang"
                                                    required
                                                >
                                            </div>
                                            <div v-if="form.errors.display_name" class="invalid-feedback d-block">
                                                {{ form.errors.display_name }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-10 mb-3">
                                            <label class="form-label fw-semibold">Deskripsi</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bx bx-message-square-detail"></i></span>
                                                <input 
                                                    type="text" 
                                                    class="form-control" 
                                                    :class="{ 'is-invalid': form.errors.description }"
                                                    v-model="form.description"
                                                    placeholder="Deskripsi singkat tentang role ini"
                                                >
                                            </div>
                                            <div v-if="form.errors.description" class="invalid-feedback d-block">
                                                {{ form.errors.description }}
                                            </div>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label class="form-label fw-semibold">Status</label>
                                            <div class="form-check form-switch mt-2">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    id="is_active"
                                                    v-model="form.is_active"
                                                    style="width: 3rem; height: 1.5rem;"
                                                >
                                                <label class="form-check-label ms-2" for="is_active">
                                                    <span :class="form.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                                        {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <!-- Permissions Section -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="text-uppercase text-muted mb-1">
                                                <i class="bx bx-lock-open me-1"></i>Permissions
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                Pilih permissions yang dapat diakses oleh role ini
                                                <span class="badge bg-info ms-2">{{ selectedCount }} / {{ totalPermissions }} dipilih</span>
                                            </p>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" @click="selectAll" title="Pilih Semua">
                                                <i class="bx bx-check-double"></i> Pilih Semua
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" @click="deselectAll" title="Hapus Semua">
                                                <i class="bx bx-x"></i> Hapus Semua
                                            </button>
                                            <button type="button" class="btn btn-outline-info" @click="expandAll" title="Expand Semua">
                                                <i class="bx bx-expand-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" @click="collapseAll" title="Collapse Semua">
                                                <i class="bx bx-collapse-alt"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Search Box -->
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                v-model="searchQuery"
                                                placeholder="Cari permission..."
                                            >
                                            <button 
                                                v-if="searchQuery" 
                                                class="btn btn-outline-secondary" 
                                                type="button"
                                                @click="searchQuery = ''"
                                            >
                                                <i class="bx bx-x"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="form.errors.permissions" class="alert alert-danger">
                                        <i class="bx bx-error-circle me-2"></i>{{ form.errors.permissions }}
                                    </div>
                                    
                                    <!-- No Permissions -->
                                    <div v-if="!permissions || Object.keys(permissions).length === 0" class="alert alert-warning">
                                        <i class="bx bx-info-circle me-2"></i>
                                        Tidak ada permissions tersedia. Silakan tambahkan permissions terlebih dahulu.
                                    </div>

                                    <!-- No Search Results -->
                                    <div v-else-if="searchQuery && Object.keys(filteredPermissions).length === 0" class="alert alert-info">
                                        <i class="bx bx-search-alt me-2"></i>
                                        Tidak ada permission yang cocok dengan pencarian "{{ searchQuery }}"
                                    </div>
                                    
                                    <!-- Permissions Accordion -->
                                    <div v-else class="accordion" id="permissionsAccordion">
                                        <div 
                                            v-for="(permissionList, module) in filteredPermissions" 
                                            :key="module"
                                            class="accordion-item mb-2 border rounded"
                                        >
                                            <h2 class="accordion-header" :id="`heading-${module}`">
                                                <button 
                                                    class="accordion-button" 
                                                    :class="{ 'collapsed': !expandedModules.includes(module) }"
                                                    type="button" 
                                                    @click="toggleExpand(module)"
                                                >
                                                    <div class="d-flex align-items-center w-100">
                                                        <div class="form-check me-3" @click.stop>
                                                            <input 
                                                                class="form-check-input" 
                                                                type="checkbox"
                                                                :checked="isModuleSelected(module)"
                                                                :indeterminate="isModulePartiallySelected(module)"
                                                                @change="toggleModule(module)"
                                                            >
                                                        </div>
                                                        <i :class="`bx ${moduleConfig[module]?.icon || 'bx-folder'} text-${moduleConfig[module]?.color || 'secondary'} fs-5 me-2`"></i>
                                                        <div class="flex-grow-1">
                                                            <strong>{{ moduleConfig[module]?.name || module }}</strong>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <span class="badge bg-primary">{{ permissionList.length }}</span>
                                                            <span 
                                                                v-if="isModulePartiallySelected(module)" 
                                                                class="badge bg-warning"
                                                            >
                                                                <i class="bx bx-check"></i>
                                                                {{ permissionList.filter(p => form.permissions.includes(p.id)).length }}
                                                            </span>
                                                            <span 
                                                                v-else-if="isModuleSelected(module)" 
                                                                class="badge bg-success"
                                                            >
                                                                <i class="bx bx-check-double"></i> Semua
                                                            </span>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div 
                                                :id="`collapse-${module}`" 
                                                class="accordion-collapse collapse"
                                                :class="{ 'show': expandedModules.includes(module) }"
                                                :aria-labelledby="`heading-${module}`"
                                            >
                                                <div class="accordion-body bg-light">
                                                    <div class="row g-3">
                                                        <div 
                                                            v-for="permission in permissionList" 
                                                            :key="permission.id"
                                                            class="col-md-6 col-lg-4"
                                                        >
                                                            <div class="card h-100 permission-card" :class="{ 'border-primary': form.permissions.includes(permission.id) }">
                                                                <div class="card-body p-3">
                                                                    <div class="form-check">
                                                                        <input 
                                                                            class="form-check-input" 
                                                                            type="checkbox"
                                                                            :id="`permission-${permission.id}`"
                                                                            :value="permission.id"
                                                                            v-model="form.permissions"
                                                                        >
                                                                        <label 
                                                                            class="form-check-label w-100" 
                                                                            :for="`permission-${permission.id}`"
                                                                        >
                                                                            <div class="fw-semibold">{{ permission.label }}</div>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <div class="text-muted small">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Pastikan semua informasi sudah benar sebelum menyimpan
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a :href="route('role.index')" class="btn btn-light">
                                            <i class="bx bx-x me-1"></i>Batal
                                        </a>
                                        <button 
                                            type="submit" 
                                            class="btn btn-primary px-4"
                                            :disabled="form.processing"
                                        >
                                            <span v-if="form.processing">
                                                <span class="spinner-border spinner-border-sm me-2"></span>
                                                Menyimpan...
                                            </span>
                                            <span v-else>
                                                <i class='bx bx-save me-1'></i>
                                                Simpan Perubahan
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #000;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}

.accordion-button {
    padding: 1rem 1.25rem;
}

.form-check-input:indeterminate {
    background-color: #ffc107;
    border-color: #ffc107;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}

.permission-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.permission-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    transform: translateY(-2px);
}

.permission-card.border-primary {
    background-color: #f0f7ff;
}

.form-check-label {
    cursor: pointer;
}

.accordion-item {
    border: 1px solid #dee2e6;
}

.accordion-body {
    padding: 1.5rem;
}

.btn-group-sm .btn {
    font-size: 0.875rem;
}

.input-group-text {
    background-color: #f8f9fa;
}
</style>
