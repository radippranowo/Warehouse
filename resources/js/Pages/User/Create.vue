<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: { type: Array, required: true },
});

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    is_active: true,
});

function submit() {
    form.post('/user', {
        preserveScroll: true,
        onSuccess: () => {
            window.toast?.success('User berhasil ditambahkan');
        },
        onError: () => {
            window.toast?.error('Gagal menambahkan user');
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
                            <i class="bx bx-user-plus me-2"></i>Tambah User
                        </h4>
                        <p class="text-muted small mb-0">Buat user baru untuk sistem</p>
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

                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-medium">
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input id="password" v-model="form.password" type="password" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.password }"
                                        placeholder="Minimal 8 karakter" required>
                                    <div v-if="form.errors.password" class="invalid-feedback">
                                        {{ form.errors.password }}
                                    </div>
                                    <small class="text-muted">Minimal 8 karakter</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label fw-medium">
                                        Konfirmasi Password <span class="text-danger">*</span>
                                    </label>
                                    <input id="password_confirmation" v-model="form.password_confirmation" 
                                        type="password" class="form-control" 
                                        placeholder="Ulangi password" required>
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
                                            type="checkbox" class="form-check-input" role="switch">
                                        <label for="is_active" class="form-check-label">
                                            {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </label>
                                    </div>
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
                                    Simpan User
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
                            <i class="bx bx-info-circle me-1"></i>Informasi
                        </h6>
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2">
                                <i class="bx bx-check text-success me-1"></i>
                                Email harus unik dan valid
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-check text-success me-1"></i>
                                Password minimal 8 karakter
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-check text-success me-1"></i>
                                Role menentukan akses user
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-check text-success me-1"></i>
                                User nonaktif tidak bisa login
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
