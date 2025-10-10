<template>
    <!-- FULL-BLEED WRAP (fixed to viewport) -->
    <div class="auth-viewport">
        <div class="auth-card card border-0 shadow-lg p-3 p-md-4">
            <h2 class="text-center fw-semibold mb-1">Login</h2>
            <p class="text-muted text-center mb-4">Welcome back! Please sign in to continue.</p>

            <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>

            <form @submit.prevent="handleLogin" novalidate>
                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        v-model.trim="form.email"
                        :disabled="loading"
                        class="form-control form-control-lg"
                        :class="{ 'is-invalid': !!errors.email }"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                    />
                    <div v-if="errors.email" class="invalid-feedback d-block">{{ errors.email }}</div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group input-group-lg">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="form.password"
                            :disabled="loading"
                            class="form-control"
                            :class="{ 'is-invalid': !!errors.password }"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            minlength="6"
                            required
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="showPassword = !showPassword"
                            :title="showPassword ? 'Hide password' : 'Show password'"
                        >
                            {{ showPassword ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <div v-if="errors.password" class="invalid-feedback d-block">{{ errors.password }}</div>
                </div>

                <button type="submit" :disabled="loading" class="btn btn-gradient w-100 btn-lg mt-4">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    <span v-if="loading">Logging in...</span>
                    <span v-else>Login</span>
                </button>

                <div class="text-center mt-3 small">
                    Don’t have an account?
                    <RouterLink class="text-decoration-none" to="/register">Register here</RouterLink>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import * as yup from 'yup'

export default {
    name: 'LoginForm',
    setup () {
        const router = useRouter()
        const authStore = useAuthStore()

        const loading = ref(false)
        const error = ref('')
        const showPassword = ref(false)

        const form = reactive({ email: '', password: '' })

        const errors = reactive({ email: '', password: '' })

        const schema = yup.object().shape({
            email: yup.string().required('Email is required').email('Enter a valid email'),
            password: yup.string().required('Password is required').min(6, 'Password must be at least 6 characters')
        })

        const validateForm = async () => {
            try {
                await schema.validate(form, { abortEarly: false })
                errors.email = ''
                errors.password = ''
                return true
            } catch (err) {
                errors.email = ''
                errors.password = ''
                err.inner.forEach(e => { errors[e.path] = e.message })
                return false
            }
        }

        const handleLogin = async () => {
            const isValid = await validateForm()
            if (!isValid) return
            loading.value = true
            error.value = ''
            try {
                await authStore.login(form)
                router.push('/dashboard')
            } catch (err) {
                error.value = err?.response?.data?.message || err?.message || 'Login failed'
            } finally {
                loading.value = false
            }
        }

        return { form, loading, error, errors, showPassword, handleLogin }
    }
}
</script>

<style scoped>
/* Full-bleed background that ignores parent container padding */
.auth-viewport {
    position: fixed;      /* key: detach from parent layout */
    inset: 0;             /* top:0 right:0 bottom:0 left:0 */
    width: 100vw;
    height: 100vh;
    overflow: auto;       /* allow scroll on tiny screens */
    display: grid;
    place-items: center;
    padding: 24px;

    /* clean, edge-to-edge gradient */
    background: radial-gradient(circle at 50% 35%, #f8faff 0%, #e7edf8 55%, #dfe6f2 100%);
}

/* Center card */
.auth-card {
    width: 100%;
    max-width: 460px;
    border-radius: 16px;
    background-color: #ffffff;
}

/* Soft blue button */
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
.form-control-lg {
    border-radius: 12px;
    border: 2px solid #e0e6f0;
}
.input-group > .form-control,
.input-group > .btn { border-radius: 12px; }
.input-group > .form-control { border-right: 0; }
.input-group > .btn { border-left: 0; }

/* Subtle shadow */
.card.shadow-lg {
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
}
</style>
