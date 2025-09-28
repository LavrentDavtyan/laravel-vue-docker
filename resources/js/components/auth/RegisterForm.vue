<template>
  <div class="register-form">
    <div class="form-container">
      <h2>Register</h2>
      <form @submit.prevent="handleRegister">
        <div class="form-row">
          <div class="form-group">
            <label for="name">First Name</label>
            <input
              type="text"
              id="name"
              v-model="form.name"
              :disabled="loading"
              class="form-control"
              :class="{ 'is-invalid': validationErrors.name }"
              placeholder="Enter your first name"
            />
            <div v-if="validationErrors.name" class="error-message">
              {{ validationErrors.name }}
            </div>
          </div>
          <div class="form-group">
            <label for="surname">Last Name</label>
            <input
              type="text"
              id="surname"
              v-model="form.surname"
              :disabled="loading"
              class="form-control"
              :class="{ 'is-invalid': validationErrors.surname }"
              placeholder="Enter your last name"
            />
            <div v-if="validationErrors.surname" class="error-message">
              {{ validationErrors.surname }}
            </div>
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            :disabled="loading"
            class="form-control"
            :class="{ 'is-invalid': validationErrors.email }"
            placeholder="Enter your email"
          />
          <div v-if="validationErrors.email" class="error-message">
            {{ validationErrors.email }}
          </div>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            :disabled="loading"
            class="form-control"
            :class="{ 'is-invalid': validationErrors.password }"
            placeholder="Enter your password"
          />
          <div v-if="validationErrors.password" class="error-message">
            {{ validationErrors.password }}
          </div>
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
            :disabled="loading"
            class="form-control"
            :class="{ 'is-invalid': validationErrors.password_confirmation }"
            placeholder="Confirm your password"
          />
          <div v-if="validationErrors.password_confirmation" class="error-message">
            {{ validationErrors.password_confirmation }}
          </div>
        </div>
        <div class="form-group">
          <label for="gender">Gender</label>
          <select
            id="gender"
            v-model="form.gender"
            :disabled="loading"
            class="form-control"
            :class="{ 'is-invalid': validationErrors.gender }"
          >
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <div v-if="validationErrors.gender" class="error-message">
            {{ validationErrors.gender }}
          </div>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="btn btn-primary">
          <span v-if="loading">Creating account...</span>
          <span v-else>Register</span>
        </button>

        <div class="form-footer">
          <p>Already have an account? <router-link to="/login">Login here</router-link></p>
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
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const loading = ref(false)
    const error = ref('')
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
        form.password !== form.password_confirmation
          ? 'Passwords do not match'
          : ''

      validationErrors.gender = !form.gender
        ? 'Gender is required'
        : ''

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
        error.value = err.message || 'Registration failed'
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      error,
      validationErrors,
      handleRegister
    }
  }
}
</script>

<style scoped>
.register-form {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.form-container {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 500px;
}

.form-container h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e1e5e9;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
}

.form-control:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.is-invalid {
  border-color: #c33;
}

.btn {
  width: 100%;
  padding: 0.75rem;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-primary {
  background-color: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #5a6fd8;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  background-color: #fee;
  color: #c33;
  padding: 0.5rem;
  border-radius: 5px;
  margin-top: 0.25rem;
  font-size: 0.9rem;
}
</style>
