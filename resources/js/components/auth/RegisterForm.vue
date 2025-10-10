<template>
    <div class="auth-viewport">
        <div class="auth-card card border-0 shadow-lg p-3 p-md-4">
            <h2 class="text-center fw-semibold mb-1">Create Account</h2>
            <p class="text-muted text-center mb-4">Join and start tracking your expenses.</p>

            <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>

            <form @submit.prevent="handleRegister" novalidate>
                <!-- Name Row -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">First Name</label>
                        <input
                            id="name"
                            type="text"
                            v-model.trim="form.name"
                            :disabled="loading"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': !!validationErrors.name }"
                            placeholder="Enter your first name"
                            required
                        />
                        <div v-if="validationErrors.name" class="invalid-feedback d-block">{{ validationErrors.name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label for="surname" class="form-label">Last Name</label>
                        <input
                            id="surname"
                            type="text"
                            v-model.trim="form.surname"
                            :disabled="loading"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': !!validationErrors.surname }"
                            placeholder="Enter your last name"
                            required
                        />
                        <div v-if="validationErrors.surname" class="invalid-feedback d-block">{{ validationErrors.surname }}</div>
                    </div>
                </div>

                <!-- Email -->
                <div class="mt-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        v-model.trim="form.email"
                        :disabled="loading"
                        class="form-control form-control-lg"
                        :class="{ 'is-invalid': !!validationErrors.email }"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                    />
                    <div v-if="validationErrors.email" class="invalid-feedback d-block">{{ validationErrors.email }}</div>
                </div>

                <!-- Passwords -->
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group input-group-lg">
                            <input
                                :type="showPass ? 'text' : 'password'"
                                id="password"
                                v-model="form.password"
                                :disabled="loading"
                                class="form-control"
                                :class="{ 'is-invalid': !!validationErrors.password }"
                                placeholder="Enter your password"
                                autocomplete="new-password"
                                required
                            />
                            <button type="button" class="btn btn-outline-secondary" @click="showPass = !showPass">
                                {{ showPass ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <div v-if="validationErrors.password" class="invalid-feedback d-block">{{ validationErrors.password }}</div>
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group input-group-lg">
                            <input
                                :type="showConfirm ? 'text' : 'password'"
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :disabled="loading"
                                class="form-control"
                                :class="{ 'is-invalid': !!validationErrors.password_confirmation }"
                                placeholder="Confirm your password"
                                autocomplete="new-password"
                                required
                            />
                            <button type="button" class="btn btn-outline-secondary" @click="showConfirm = !showConfirm">
                                {{ showConfirm ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <div v-if="validationErrors.password_confirmation" class="invalid-feedback d-block">
                            {{ validationErrors.password_confirmation }}
                        </div>
                    </div>
                </div>

                <!-- Gender -->
                <div class="mt-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select
                        id="gender"
                        v-model="form.gender"
                        :disabled="loading"
                        class="form-select form-select-lg"
                        :class="{ 'is-invalid': !!validationErrors.gender }"
                    >
                        <option value="">Select gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <div v-if="validationErrors.gender" class="invalid-feedback d-block">{{ validationErrors.gender }}</div>
                </div>

                <button type="submit" :disabled="loading" class="btn btn-gradient w-100 btn-lg mt-4">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <span v-if="loading">Creating account...</span>
                    <span v-else>Register</span>
                </button>

                <div class="text-center mt-3 small">
                    Already have an account?
                    <RouterLink class="text-decoration-none" to="/login">Login here</RouterLink>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'

export default {
    name: 'RegisterForm',
    setup () {
        const router = useRouter()
        const authStore = useAuthStore()
        const loading = ref(false)
        const error = ref('')
        const showPass = ref(false)
        const showConfirm = ref(false)
        const validationErrors = reactive({})

        const form = reactive({
            name: '',
            surname: '',
            email: '',
            password: '',
            password_confirmation: '',
            gender: ''
        })

        const validateForm = () => {
            validationErrors.name = !form.name
                ? 'First name is required'
                : form.name.length < 2
                    ? 'First name must be at least 2 characters'
                    : ''
            validationErrors.surname = !form.surname
                ? 'Last name is required'
                : form.surname.length < 2
                    ? 'Last name must be at least 2 characters'
                    : ''
            validationErrors.email = !form.email
                ? 'Email is required'
                : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)
                    ? 'Invalid email address'
                    : ''
            validationErrors.password = !form.password
                ? 'Password is required'
                : form.password.length < 8
                    ? 'Password must be at least 8 characters'
                    : !/[A-Z]/.test(form.password) || !/[0-9]/.test(form.password)
                        ? 'Password must include at least one uppercase letter and one number'
                        : ''
            validationErrors.password_confirmation =
                form.password !== form.password_confirmation ? 'Passwords do not match' : ''
            validationErrors.gender = !form.gender ? 'Gender is required' : ''
            return Object.values(validationErrors).every(v => v === '')
        }

        const handleRegister = async () => {
            loading.value = true
            error.value = ''
            if (!validateForm()) {
                loading.value = false
                return
            }
            try {
                await authStore.register(form)
                router.push('/dashboard')
            } catch (err) {
                error.value = err?.response?.data?.message || err?.message || 'Registration failed'
            } finally {
                loading.value = false
            }
        }

        return { form, loading, error, validationErrors, showPass, showConfirm, handleRegister }
    }
}
</script>

<style scoped>
.auth-viewport {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background: radial-gradient(circle at 50% 35%, #f8faff 0%, #e7edf8 55%, #dfe6f2 100%);
    overflow: auto;
}

.auth-card {
    width: 100%;
    max-width: 560px;
    border-radius: 16px;
    background-color: #ffffff;
}

/* Soft blue gradient button */
.btn-gradient {
    color: #fff;
    background: linear-gradient(90deg, #4b6cb7 0%, #182848 100%);
    border: 0;
}
.btn-gradient:hover,
.btn-gradient:focus {
    color: #fff;
    filter: brightness(1.1);
}

/* Inputs */
.form-control-lg,
.form-select-lg {
    border-radius: 12px;
    border: 2px solid #e0e6f0;
}
.input-group > .form-control,
.input-group > .btn {
    border-radius: 12px;
}
.input-group > .form-control { border-right: 0; }
.input-group > .btn { border-left: 0; }

/* Card shadow */
.card.shadow-lg {
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
}
</style>
