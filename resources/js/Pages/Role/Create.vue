<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    permissions: Array,
});

const form = useForm({
    name: '',
    description: '',
    permissions: [],
});

// Group permissions by module
const groupedPermissions = computed(() => {
    const groups = {};
    props.permissions.forEach(permission => {
        const module = permission.module || 'other';
        if (!groups[module]) {
            groups[module] = [];
        }
        groups[module].push(permission);
    });
    return groups;
});

// Module display names
const moduleNames = {
    'dashboard': 'Dashboard',
    'barang': 'Master Barang',
    'category': 'Kategori',
    'merk': 'Merk',
    'group': 'Group',
    'gudang': 'Gudang',
    'barang_masuk': 'Barang Masuk',
    'barang_keluar': 'Barang Keluar',
    'transfer': 'Transfer Barang',
    'penyesuaian': 'Penyesuaian Stok',
    'stok': 'Stok Gudang',
    'laporan': 'Laporan',
    'user': 'User Management',
    'role': 'Role Management',
    'other': 'Lainnya',
};

// Function to convert permission name to human-readable format
const getHumanReadableName = (name) => {
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

const toggleModule = (module) => {
    const modulePermissions = groupedPermissions.value[module].map(p => p.id);
    const allSelected = modulePermissions.every(id => form.permissions.includes(id));
    
    if (allSelected) {
        // Unselect all
        form.permissions = form.permissions.filter(id => !modulePermissions.includes(id));
    } else {
        // Select all
        const newPermissions = [...form.permissions];
        modulePermissions.forEach(id => {
            if (!newPermissions.includes(id)) {
                newPermissions.push(id);
            }
        });
        form.permissions = newPermissions;
    }
};

const isModuleSelected = (module) => {
    const modulePermissions = groupedPermissions.value[module].map(p => p.id);
    return modulePermissions.every(id => form.permissions.includes(id));
};

const isModulePartiallySelected = (module) => {
    const modulePermissions = groupedPermissions.value[module].map(p => p.id);
    const selectedCount = modulePermissions.filter(id => form.permissions.includes(id)).length;
    return selectedCount > 0 && selectedCount < modulePermissions.length;
};

const submit = () => {
    form.post(route('role.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Tambah Role" />

        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Tambah Role Baru</h5>
                            <a :href="route('role.index')" class="btn btn-sm btn-secondary">
                                <i class='bx bx-arrow-back'></i> Kembali
                            </a>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Role <span class="text-danger">*</span></label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': form.errors.name }"
                                            v-model="form.name"
                                            placeholder="Contoh: Manager Gudang"
                                            required
                                        >
                                        <div v-if="form.errors.name" class="invalid-feedback">
                                            {{ form.errors.name }}
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': form.errors.description }"
                                            v-model="form.description"
                                            placeholder="Deskripsi singkat role"
                                        >
                                        <div v-if="form.errors.description" class="invalid-feedback">
                                            {{ form.errors.description }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Permissions <span class="text-danger">*</span></label>
                                    <div v-if="form.errors.permissions" class="text-danger small mb-2">
                                        {{ form.errors.permissions }}
                                    </div>
                                    
                                    <div class="accordion" id="permissionsAccordion">
                                        <div 
                                            v-for="(permissions, module) in groupedPermissions" 
                                            :key="module"
                                            class="accordion-item"
                                        >
                                            <h2 class="accordion-header">
                                                <button 
                                                    class="accordion-button collapsed" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    :data-bs-target="`#collapse-${module}`"
                                                >
                                                    <div class="form-check me-3" @click.stop>
                                                        <input 
                                                            class="form-check-input" 
                                                            type="checkbox"
                                                            :checked="isModuleSelected(module)"
                                                            :indeterminate="isModulePartiallySelected(module)"
                                                            @change="toggleModule(module)"
                                                        >
                                                    </div>
                                                    <strong>{{ moduleNames[module] || module }}</strong>
                                                    <span class="badge bg-primary ms-2">{{ permissions.length }}</span>
                                                </button>
                                            </h2>
                                            <div 
                                                :id="`collapse-${module}`" 
                                                class="accordion-collapse collapse"
                                                data-bs-parent="#permissionsAccordion"
                                            >
                                                <div class="accordion-body">
                                                    <div class="row">
                                                        <div 
                                                            v-for="permission in permissions" 
                                                            :key="permission.id"
                                                            class="col-md-4 col-lg-3 mb-2"
                                                        >
                                                            <div class="form-check">
                                                                <input 
                                                                    class="form-check-input" 
                                                                    type="checkbox"
                                                                    :id="`permission-${permission.id}`"
                                                                    :value="permission.id"
                                                                    v-model="form.permissions"
                                                                >
                                                                <label 
                                                                    class="form-check-label" 
                                                                    :for="`permission-${permission.id}`"
                                                                >
                                                                    {{ getHumanReadableName(permission.name) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a :href="route('role.index')" class="btn btn-secondary">
                                        Batal
                                    </a>
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                        <i v-else class='bx bx-save me-1'></i>
                                        Simpan
                                    </button>
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
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}

.form-check-input:indeterminate {
    background-color: #0d6efd;
    border-color: #0d6efd;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10h8'/%3e%3c/svg%3e");
}
</style>
