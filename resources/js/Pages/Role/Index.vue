<script setup>
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

function deleteRole(role) {
    if (confirm(`Hapus role "${role.display_name}"?`)) {
        router.delete(`/role/${role.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                window.toast?.success('Role berhasil dihapus');
            },
        });
    }
}

function getRoleBadge(roleName) {
    const badges = {
        admin: 'bg-danger',
        manager: 'bg-primary',
        staff: 'bg-success',
        viewer: 'bg-secondary',
    };
    return badges[roleName] || 'bg-info';
}
</script>

<template>
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

        <!-- Roles Grid -->
        <div class="row g-4">
            <div v-for="role in roles" :key="role.id" class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span :class="`badge ${getRoleBadge(role.name)} mb-2`">
                                    {{ role.name }}
                                </span>
                                <h5 class="mb-1 fw-bold">{{ role.display_name }}</h5>
                                <p class="text-muted small mb-0">{{ role.description }}</p>
                            </div>
                            <span v-if="role.is_active" class="badge bg-success">Aktif</span>
                            <span v-else class="badge bg-secondary">Nonaktif</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="text-muted small">Users</div>
                                <div class="fw-bold fs-5">{{ role.users_count }}</div>
                            </div>
                            <div>
                                <div class="text-muted small">Permissions</div>
                                <div class="fw-bold fs-5">{{ role.permissions_count }}</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <Link :href="`/role/${role.id}/edit`" class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="bx bx-edit me-1"></i>Edit
                            </Link>
                            <button @click="deleteRole(role)" class="btn btn-outline-danger btn-sm"
                                :disabled="role.name === 'admin' || role.users_count > 0"
                                :title="role.name === 'admin' ? 'Tidak bisa hapus admin' : role.users_count > 0 ? 'Role masih digunakan' : 'Hapus role'">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
