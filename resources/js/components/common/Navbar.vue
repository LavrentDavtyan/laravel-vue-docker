<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const userFullName = computed(() => authStore.userFullName)
const loading = computed(() => authStore.loading)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <nav class="navbar">
    <div class="nav-brand">
      <h2>Laravel Vue App</h2>
    </div>
    <div class="nav-menu">
      <span class="welcome-text">Welcome, {{ userFullName }}</span>
      <button @click="handleLogout" class="btn btn-outline" :disabled="loading">
        Logout
      </button>
    </div>
  </nav>
</template>

<style scoped>
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
  
  .dashboard-content {
    padding: 1rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
