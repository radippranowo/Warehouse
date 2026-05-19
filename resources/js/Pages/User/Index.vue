<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useSingleFlight } from '@/composables/useSingleFlight';

const { busy, run } = useSingleFlight();

const props = defineProps({
    users: { type: Object, required: true },
    roles: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
});


defineOptions({ layout: AppLayout });

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');
const perPage = ref(props.filters.per_page || 20);

let searchTimer = null;

function applyFilters() {
    router.get('/user', {
        search: search.value,
        role: roleFilter.value,
        status: statusFilter.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

// Search di-debounce 300ms — sama pola dengan halaman lain.
// Filter dropdown (role/status/perPage) langsung apply tanpa delay karena
// user click cuma sekali, tidak ada keystroke storm.
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});
watch([roleFilter, statusFilter, perPage], () => applyFilters());

function resetFilters() {
    clearTimeout(searchTimer);
    search.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    perPage.value = 20;
    applyFilters();
}

function deleteUser(user) {
    if (!confirm(`Hapus user "${user.name}"?`)) return;
    run(`del-${user.id}`, (done) => {
        router.delete(`/user/${user.id}`, {
            preserveScroll: true,
            onSuccess: () => window.toast?.success('User berhasil dihapus'),
            onFinish: done,
        });
    });
}

function toggleStatus(user) {
    run(`toggle-${user.id}`, (done) => {
        router.post(`/user/${user.id}/toggle-status`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                const status = !user.is_active ? 'diaktifkan' : 'dinonaktifkan';
                window.toast?.success(`User berhasil ${status}`);
            },
            onFinish: done,
        });
    });
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
                            <i class="bx bx-user me-2"></i>User Management
                        </h4>
                        <p class="text-muted small mb-0">Kelola user dan akses sistem</p>
                    </div>
                    <Link href="/user/create" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Tambah User
                    </Link>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <!-- Filters in card header -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input id="search_user" name="search" v-model="search" type="text"
                            class="form-control" placeholder="Cari nama atau email..."
                            autocomplete="off" aria-label="Cari user">
                    </div>
                    <div class="col-md-2">
                        <select id="role_filter_user" name="role_filter" v-model="roleFilter" @change="applyFilters" class="form-select" aria-label="Filter role">
                            <option value="">Semua Role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.display_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="status_filter_user" name="status_filter" v-model="statusFilter" @change="applyFilters" class="form-select" aria-label="Filter status user">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="per_page_user" name="per_page" v-model="perPage" @change="applyFilters" class="form-select" aria-label="Jumlah data per halaman">
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button @click="resetFilters" class="btn btn-secondary w-100">
                            <i class="bx bx-reset me-1"></i>Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 23%;">Nama</th>
                                <th style="width: 23%;">Email</th>
                                <th style="width: 15%;">Role</th>
                                <th style="width: 10%;" class="text-center">Status</th>
                                <th style="width: 10%;">Last Login</th>
                                <th style="width: 14%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(user, index) in users.data" :key="user.id">
                                <td>{{ users.from + index }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2">
                                            <i class="bx bx-user text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ user.name }}</div>
                                            <small v-if="user.id === $page.props.auth.user.id" class="text-muted">
                                                (Anda)
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ user.email }}</td>
                                <td>
                                    <span v-if="user.role" :class="`badge ${getRoleBadge(user.role.name)}`">
                                        {{ user.role.display_name }}
                                    </span>
                                    <span v-else class="badge bg-secondary">No Role</span>
                                </td>
                                <td class="text-center">
                                    <span v-if="user.is_active" class="badge bg-success">Aktif</span>
                                    <span v-else class="badge bg-danger">Nonaktif</span>
                                </td>
                                <td>
                                    <small v-if="user.last_login_at" class="text-muted">
                                        {{ new Date(user.last_login_at).toLocaleDateString('id-ID') }}
                                    </small>
                                    <small v-else class="text-muted">-</small>
                                </td>
                                <td>
                                    <Link :href="`/user/${user.id}/edit`"
                                        class="btn btn-sm btn-soft-info border-0 shadow-sm bx bx-pencil font-size-16"
                                        title="Edit">
                                    </Link>
                                    <button
                                        @click="toggleStatus(user)"
                                        class="btn btn-sm btn-soft-warning border-0 shadow-sm bx font-size-16 ms-1"
                                        :class="user.is_active ? 'bx-lock' : 'bx-lock-open'"
                                        :title="user.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        :disabled="user.id === $page.props.auth.user.id || busy(`toggle-${user.id}`)"
                                    ></button>
                                    <button
                                        @click="deleteUser(user)"
                                        class="btn btn-sm btn-soft-danger border-0 shadow-sm bx bx-trash font-size-16 ms-1"
                                        title="Hapus"
                                        :disabled="user.id === $page.props.auth.user.id || busy(`del-${user.id}`)"
                                    ></button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                                    Tidak ada data user
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">
                        Menampilkan {{ users.from }} - {{ users.to }} dari {{ users.total }} user
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li :class="['page-item', { disabled: !users.prev_page_url }]">
                                <Link :href="users.prev_page_url || '#'" class="page-link">
                                    <i class="bx bx-chevron-left"></i>
                                </Link>
                            </li>
                            <li v-for="page in users.links.slice(1, -1)" :key="page.label" 
                                :class="['page-item', { active: page.active }]">
                                <Link :href="page.url" class="page-link" v-html="page.label"></Link>
                            </li>
                            <li :class="['page-item', { disabled: !users.next_page_url }]">
                                <Link :href="users.next_page_url || '#'" class="page-link">
                                    <i class="bx bx-chevron-right"></i>
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.avatar-xs {
    width: 32px;
    height: 32px;
}
</style>
