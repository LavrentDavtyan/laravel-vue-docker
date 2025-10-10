<template>
  <div class="login-form">
    <div class="form-container">
      <h2>Login</h2>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="email">Email</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            :disabled="loading"
            class="form-control"
            placeholder="Enter your email"
          />
          <small class="text-danger">{{ errors.email }}</small>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            :disabled="loading"
            class="form-control"
            placeholder="Enter your password"
          />
          <small class="text-danger">{{ errors.password }}</small>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="btn btn-primary">
          <span v-if="loading">Logging in...</span>
          <span v-else>Login</span>
        </button>

        <div class="form-footer">
          <p>Don't have an account? <router-link to="/register">Register here</router-link></p>
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
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const loading = ref(false)
    const error = ref('')

    const form = reactive({
      email: '',
      password: ''
    })

    // validation errors
    const errors = reactive({
      email: '',
      password: ''
    })

    // define schema
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
        err.inner.forEach((e) => {
          errors[e.path] = e.message
        })
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
        error.value = err.message || 'Login failed'
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      error,
      errors,
      handleLogin
    }
  }
}
</script>

<style scoped>
.login-form {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
  padding: 20px;
}

.form-container {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.form-container h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
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

.text-danger {
  color: #dc3545;
  font-size: 0.9rem;
}
</style>
