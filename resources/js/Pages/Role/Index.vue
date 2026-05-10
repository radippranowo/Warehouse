<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

const searchQuery = ref('');
const filterStatus = ref('all');

const filteredRoles = computed(() => {
    let filtered = props.roles;
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(role => 
            role.name.toLowerCase().includes(query) ||
            role.display_name.toLowerCase().includes(query) ||
            role.description?.toLowerCase().includes(query)
        );
    }
    
    if (filterStatus.value !== 'all') {
        const isActive = filterStatus.value === 'active';
        filtered = filtered.filter(role => role.is_active === isActive);
    }
    
    return filtered;
});

function deleteRole(role) {
    if (role.name === 'admin') {
        window.toast?.error('Tidak bisa menghapus role admin');
        return;
    }
    
    if (role.users_count > 0) {
        window.toast?.error('Role masih digunakan oleh ' + role.users_count + ' user');
        return;
    }
    
    if (confirm(`Yakin ingin menghapus role "${role.display_name}"?`)) {
        router.delete(`/role/${role.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                window.toast?.success('Role berhasil dihapus');
            },
        });
    }
}

function getRoleIcon(roleName) {
    const icons = {
        admin: 'bx-shield-alt-2',
        manager: 'bx-user-check',
        staff: 'bx-user',
        viewer: 'bx-show',
    };
    return icons[roleName] || 'bx-user-circle';
}
</script>

<template>
    <Head title="Role Management" />
    
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold">
                            <i class="bx bx-shield me-2"></i>Role Management
                        </h4>
                        <p class="text-muted small mb-0">Kelola role dan permissions</p>
                    </div>
                    <Link href="/role/create" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Tambah Role
                    </Link>
                </div>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Filters in card header -->
                <div class="row g-3 mb-4">
                    <div class="col-md-10">
                        <input 
                            type="text" 
                            class="form-control" 
                            v-model="searchQuery"
                            placeholder="Cari nama atau deskripsi role..."
                        >
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" v-model="filterStatus">
                            <option value="all">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div v-if="filteredRoles.length === 0" class="text-center text-muted py-5">
                    <i class="bx bx-search-alt fs-1 d-block mb-2"></i>
                    <p class="mb-0">Tidak ada role yang ditemukan</p>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 20%;">Nama Role</th>
                                <th style="width: 35%;">Deskripsi</th>
                                <th style="width: 10%;" class="text-center">Status</th>
                                <th style="width: 10%;" class="text-center">Users</th>
                                <th style="width: 10%;" class="text-center">Permissions</th>
                                <th style="width: 10%;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(role, index) in filteredRoles" :key="role.id">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i :class="`bx ${getRoleIcon(role.name)} fs-5 me-2 text-primary`"></i>
                                        <div>
                                            <div class="fw-medium">{{ role.display_name }}</div>
                                            <small class="text-muted">{{ role.name }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">{{ role.description || '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <span v-if="role.is_active" class="badge bg-success">Aktif</span>
                                    <span v-else class="badge bg-secondary">Nonaktif</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ role.users_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ role.permissions_count }}</span>
                                </td>
                                <td>
                                    <Link :href="`/role/${role.id}/edit`"
                                        class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                        title="Edit">
                                    </Link>
                                    <button 
                                        @click="deleteRole(role)" 
                                        class="btn btn-soft-danger btn-sm border-0 shadow-sm bx bx-trash font-size-16 ms-1"
                                        :disabled="role.name === 'admin' || role.users_count > 0"
                                        :title="role.name === 'admin' ? 'Tidak bisa hapus admin' : role.users_count > 0 ? 'Role masih digunakan' : 'Hapus'"
                                    ></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
