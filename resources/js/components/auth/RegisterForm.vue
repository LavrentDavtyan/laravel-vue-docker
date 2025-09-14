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
              required
              :disabled="loading"
              class="form-control"
              placeholder="Enter your first name"
            />
          </div>

          <div class="form-group">
            <label for="surname">Last Name</label>
            <input
              type="text"
              id="surname"
              v-model="form.surname"
              required
              :disabled="loading"
              class="form-control"
              placeholder="Enter your last name"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            required
            :disabled="loading"
            class="form-control"
            placeholder="Enter your email"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            required
            :disabled="loading"
            class="form-control"
            placeholder="Enter your password"
          />
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
            required
            :disabled="loading"
            class="form-control"
            placeholder="Confirm your password"
          />
        </div>

        <div class="form-group">
          <label for="gender">Gender (Optional)</label>
          <select
            id="gender"
            v-model="form.gender"
            :disabled="loading"
            class="form-control"
          >
            <option value="">Select gender</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
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
    
    const form = reactive({
      name: '',
      surname: '',
      email: '',
      password: '',
      password_confirmation: '',
      gender: ''
    })

    const handleRegister = async () => {
      loading.value = true
      error.value = ''

      // Basic validation
      if (form.password !== form.password_confirmation) {
        error.value = 'Passwords do not match'
        loading.value = false
        return
      }

      if (form.password.length < 8) {
        error.value = 'Password must be at least 8 characters long'
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
  padding: 0.75rem;
  border-radius: 5px;
  margin-bottom: 1rem;
  border: 1px solid #fcc;
}

.form-footer {
  text-align: center;
  margin-top: 1rem;
}

.form-footer a {
  color: #667eea;
  text-decoration: none;
}

.form-footer a:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
