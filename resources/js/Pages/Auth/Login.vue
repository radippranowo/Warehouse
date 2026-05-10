<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            console.error('Login errors:', errors);
        },
        onSuccess: () => {
            console.log('Login successful');
        },
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="account-pages my-5 pt-sm-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card overflow-hidden">
                        <div class="bg-primary bg-soft">
                            <div class="row">
                                <div class="col-7">
                                    <div class="text-primary p-4">
                                        <h5 class="text-primary">Welcome Back!</h5>
                                        <p>Sign in to continue to Warehouse.</p>
                                    </div>
                                </div>
                                <div class="col-5 align-self-end">
                                    <img src="/assets/images/profile-img.png" alt="" class="img-fluid">
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="auth-logo">
                                <a href="/" class="auth-logo-light">
                                    <div class="avatar-md profile-user-wid mb-4">
                                        <span class="avatar-title rounded-circle bg-light">
                                            <img src="/assets/images/logo-light.svg" alt="" class="rounded-circle" height="34">
                                        </span>
                                    </div>
                                </a>
                                <a href="/" class="auth-logo-dark">
                                    <div class="avatar-md profile-user-wid mb-4">
                                        <span class="avatar-title rounded-circle bg-light">
                                            <img src="/assets/images/logo.svg" alt="" class="rounded-circle" height="34">
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2">
                                <!-- Status Message -->
                                <div v-if="status" class="alert alert-success mb-4" role="alert">
                                    {{ status }}
                                </div>

                                <form @submit.prevent="submit">
                                    <!-- Email -->
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input 
                                            id="email"
                                            type="email" 
                                            class="form-control" 
                                            :class="{ 'is-invalid': form.errors.email }"
                                            v-model="form.email"
                                            placeholder="Enter email"
                                            required
                                            autofocus
                                        >
                                        <div v-if="form.errors.email" class="invalid-feedback">
                                            {{ form.errors.email }}
                                        </div>
                                    </div>

                                    <!-- Password -->
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <input 
                                                id="password"
                                                :type="showPassword ? 'text' : 'password'" 
                                                class="form-control" 
                                                :class="{ 'is-invalid': form.errors.password }"
                                                v-model="form.password"
                                                placeholder="Enter password"
                                                required
                                            >
                                            <button 
                                                class="btn btn-light" 
                                                type="button" 
                                                @click="showPassword = !showPassword"
                                            >
                                                <i class="mdi" :class="showPassword ? 'mdi-eye-off-outline' : 'mdi-eye-outline'"></i>
                                            </button>
                                            <div v-if="form.errors.password" class="invalid-feedback d-block">
                                                {{ form.errors.password }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Remember Me -->
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            id="remember-check"
                                            v-model="form.remember"
                                        >
                                        <label class="form-check-label" for="remember-check">
                                            Remember me
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-3 d-grid">
                                        <button 
                                            class="btn btn-primary waves-effect waves-light" 
                                            type="submit"
                                            :disabled="form.processing"
                                        >
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            Log In
                                        </button>
                                    </div>

                                    <!-- Forgot Password -->
                                    <div v-if="canResetPassword" class="mt-4 text-center">
                                        <a href="/forgot-password" class="text-muted">
                                            <i class="mdi mdi-lock me-1"></i> Forgot your password?
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Default Credentials Info -->
                    <div class="mt-5 text-center">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-3">Default Login Credentials</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Role</th>
                                                <th>Email</th>
                                                <th>Password</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge bg-danger">Admin</span></td>
                                                <td>admin@warehouse.com</td>
                                                <td>admin123</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-primary">Manager</span></td>
                                                <td>manager@warehouse.com</td>
                                                <td>manager123</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-success">Staff</span></td>
                                                <td>staff@warehouse.com</td>
                                                <td>staff123</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-secondary">Viewer</span></td>
                                                <td>viewer@warehouse.com</td>
                                                <td>viewer123</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-center">
                        <div>
                            <p>© {{ new Date().getFullYear() }} Warehouse Management System</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.account-pages {
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.bg-soft {
    background-color: rgba(85, 110, 230, 0.25) !important;
}

.auth-logo {
    text-align: center;
    margin-top: -50px;
}

.auth-pass-inputgroup {
    position: relative;
}

.auth-pass-inputgroup .btn {
    border-left: 0;
}

.auth-pass-inputgroup input {
    border-right: 0;
}

.auth-pass-inputgroup input:focus {
    border-color: #ced4da;
}

.auth-pass-inputgroup input:focus + .btn {
    border-color: #ced4da;
}
</style>
