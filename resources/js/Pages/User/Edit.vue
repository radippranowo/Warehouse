<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role_id: props.user.role_id,
    is_active: props.user.is_active,
});

function submit() {
    form.put(`/user/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            window.toast?.success('User berhasil diupdate');
        },
        onError: () => {
            window.toast?.error('Gagal mengupdate user');
        },
    });
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
                            <i class="bx bx-edit me-2"></i>Edit User
                        </h4>
                        <p class="text-muted small mb-0">Update informasi user</p>
                    </div>
                    <Link href="/user" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Kembali
                    </Link>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form @submit.prevent="submit">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-medium">
                                        Nama Lengkap <span class="text-danger">*</span>
                                    </label>
                                    <input id="name" v-model="form.name" type="text" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.name }"
                                        placeholder="Masukkan nama lengkap" required>
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-medium">
                                        Email <span class="text-danger">*</span>
                                    </label>
                                    <input id="email" v-model="form.email" type="email" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.email }"
                                        placeholder="user@example.com" required>
                                    <div v-if="form.errors.email" class="invalid-feedback">
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info py-2 small mb-0">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Kosongkan password jika tidak ingin mengubahnya
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-medium">
                                        Password Baru
                                    </label>
                                    <input id="password" v-model="form.password" type="password" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.password }"
                                        placeholder="Minimal 8 karakter">
                                    <div v-if="form.errors.password" class="invalid-feedback">
                                        {{ form.errors.password }}
                                    </div>
                                    <small class="text-muted">Minimal 8 karakter</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label fw-medium">
                                        Konfirmasi Password
                                    </label>
                                    <input id="password_confirmation" v-model="form.password_confirmation" 
                                        type="password" class="form-control" 
                                        placeholder="Ulangi password baru">
                                </div>

                                <div class="col-md-6">
                                    <label for="role_id" class="form-label fw-medium">
                                        Role <span class="text-danger">*</span>
                                    </label>
                                    <select id="role_id" v-model="form.role_id" 
                                        class="form-select" 
                                        :class="{ 'is-invalid': form.errors.role_id }" required>
                                        <option value="">-- Pilih Role --</option>
                                        <option v-for="role in roles" :key="role.id" :value="role.id">
                                            {{ role.display_name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.role_id" class="invalid-feedback">
                                        {{ form.errors.role_id }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Status</label>
                                    <div class="form-check form-switch">
                                        <input id="is_active" v-model="form.is_active" 
                                            type="checkbox" class="form-check-input" role="switch"
                                            :disabled="user.id === $page.props.auth.user.id">
                                        <label for="is_active" class="form-check-label">
                                            {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </label>
                                    </div>
                                    <small v-if="user.id === $page.props.auth.user.id" class="text-muted">
                                        Tidak bisa menonaktifkan akun sendiri
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <Link href="/user" class="btn btn-secondary">
                                    Batal
                                </Link>
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bx bx-save me-1"></i>
                                    Update User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="bx bx-info-circle me-1"></i>Informasi User
                        </h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <strong>Dibuat:</strong><br>
                                {{ new Date(user.created_at).toLocaleString('id-ID') }}
                            </li>
                            <li class="mb-2">
                                <strong>Terakhir Update:</strong><br>
                                {{ new Date(user.updated_at).toLocaleString('id-ID') }}
                            </li>
                            <li v-if="user.last_login_at" class="mb-2">
                                <strong>Last Login:</strong><br>
                                {{ new Date(user.last_login_at).toLocaleString('id-ID') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
