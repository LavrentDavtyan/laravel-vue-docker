<template>
  <div class="dashboard">
    <div class="dashboard-content">
      <div class="container">
        <h1>Dashboard</h1>

        <div class="user-info">
          <h3>User Information</h3>
          <div class="info-grid">
            <div class="info-item">
              <label>Name:</label>
              <span>{{ userFullName }}</span>
            </div>
            <div class="info-item">
              <label>Email:</label>
              <span>{{ currentUser?.email }}</span>
            </div>
            <div class="info-item">
              <label>Gender:</label>
              <span>{{ currentUser?.gender || 'Not specified' }}</span>
            </div>
            <div class="info-item">
              <label>Status:</label>
              <span class="status" :class="{ active: currentUser?.is_active }">
                {{ currentUser?.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

export default {
  name: 'Dashboard',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const currentUser = computed(() => authStore.currentUser)
    const userFullName = computed(() => authStore.userFullName)
    const loading = computed(() => authStore.loading)

    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      }
    }

    return {
      currentUser,
      userFullName,
      loading,
      handleLogout
    }
  }
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background-color: #f8f9fa;
}



.welcome-text {
  color: #666;
  font-weight: 500;
}

.dashboard-content {
  padding: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.container h1 {
  color: #333;
  margin-bottom: 2rem;
}

.user-info {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.user-info h3 {
  margin-top: 0;
  color: #333;
  margin-bottom: 1rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item label {
  font-weight: 600;
  color: #666;
  font-size: 0.9rem;
}

.info-item span {
  color: #333;
}

.status {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  display: inline-block;
  width: fit-content;
}

.status.active {
  background-color: #d4edda;
  color: #155724;
}

.status:not(.active) {
  background-color: #f8d7da;
  color: #721c24;
}

.actions {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.actions h3 {
  margin-top: 0;
  color: #333;
  margin-bottom: 1rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 500;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover {
  background-color: #0056b3;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #545b62;
}

.btn-outline {
  background-color: transparent;
  color: #dc3545;
  border: 2px solid #dc3545;
}

.btn-outline:hover:not(:disabled) {
  background-color: #dc3545;
  color: white;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>


