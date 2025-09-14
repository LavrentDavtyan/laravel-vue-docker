<template>
  <div class="user-management">
    <nav class="navbar">
      <div class="nav-brand">
        <h2>Laravel Vue App</h2>
      </div>
      <div class="nav-menu">
        <router-link to="/dashboard" class="nav-link">Dashboard</router-link>
        <router-link to="/tasks" class="nav-link">Tasks</router-link>
        <span class="welcome-text">Welcome, {{ userFullName }}</span>
        <button @click="handleLogout" class="btn btn-outline" :disabled="loading">
          Logout
        </button>
      </div>
    </nav>

    <div class="content">
      <div class="container">
        <h1>User Management</h1>
        
        <div class="user-list">
          <div v-if="loading" class="loading">
            Loading users...
          </div>
          
          <div v-else-if="error" class="error">
            {{ error }}
          </div>
          
          <div v-else-if="users.length === 0" class="no-users">
            No users found.
          </div>
          
          <div v-else class="users-grid">
            <div v-for="user in users" :key="user.id" class="user-card">
              <div class="user-info">
                <h3>{{ user.name }} {{ user.surname }}</h3>
                <p class="email">{{ user.email }}</p>
                <p class="gender">{{ user.gender || 'Not specified' }}</p>
                <span class="status" :class="{ active: user.is_active }">
                  {{ user.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <div class="user-actions">
                <button @click="editUser(user)" class="btn btn-sm btn-secondary">
                  Edit
                </button>
                <button @click="deleteUser(user)" class="btn btn-sm btn-danger">
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import authService from '../auth/authService'

export default {
  name: 'UserManagement',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const users = ref([])
    const loading = ref(false)
    const error = ref('')
    
    const userFullName = computed(() => authStore.userFullName)

    const fetchUsers = async () => {
      try {
        loading.value = true
        error.value = ''
        
        const response = await authService.apiRequest('/users')
        
        if (!response.ok) {
          throw new Error('Failed to fetch users')
        }
        
        const data = await response.json()
        users.value = data.data || []
      } catch (err) {
        error.value = err.message || 'Failed to fetch users'
      } finally {
        loading.value = false
      }
    }

    const editUser = (user) => {
      // TODO: Implement edit functionality
      console.log('Edit user:', user)
    }

    const deleteUser = async (user) => {
      if (!confirm(`Are you sure you want to delete ${user.name} ${user.surname}?`)) {
        return
      }
      
      try {
        const response = await authService.apiRequest(`/users/${user.id}`, {
          method: 'DELETE'
        })
        
        if (!response.ok) {
          throw new Error('Failed to delete user')
        }
        
        // Remove user from list
        users.value = users.value.filter(u => u.id !== user.id)
      } catch (err) {
        error.value = err.message || 'Failed to delete user'
      }
    }

    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      }
    }

    onMounted(() => {
      fetchUsers()
    })

    return {
      users,
      loading,
      error,
      userFullName,
      editUser,
      deleteUser,
      handleLogout
    }
  }
}
</script>

<style scoped>
.user-management {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.navbar {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-brand h2 {
  margin: 0;
  color: #333;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.nav-link {
  color: #666;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  transition: background-color 0.3s ease;
}

.nav-link:hover {
  background-color: #f8f9fa;
}

.welcome-text {
  color: #666;
  font-weight: 500;
}

.content {
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

.user-list {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

.loading, .error, .no-users {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.error {
  color: #dc3545;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  border-radius: 4px;
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.user-card {
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  padding: 1rem;
  transition: box-shadow 0.3s ease;
}

.user-card:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.user-info h3 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.user-info p {
  margin: 0.25rem 0;
  color: #666;
}

.status {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  display: inline-block;
  margin-top: 0.5rem;
}

.status.active {
  background-color: #d4edda;
  color: #155724;
}

.status:not(.active) {
  background-color: #f8d7da;
  color: #721c24;
}

.user-actions {
  margin-top: 1rem;
  display: flex;
  gap: 0.5rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.8rem;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #545b62;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c82333;
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

@media (max-width: 768px) {
  .navbar {
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;
  }
  
  .nav-menu {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .content {
    padding: 1rem;
  }
  
  .users-grid {
    grid-template-columns: 1fr;
  }
}
</style>
