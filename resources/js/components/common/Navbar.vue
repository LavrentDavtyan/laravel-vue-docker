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
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container-fluid">
      <!-- Brand -->
      <router-link class="navbar-brand fw-bold" to="/">Tracker</router-link>

      <!-- Toggler for mobile -->
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarContent"
        aria-controls="navbarContent"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <router-link class="nav-link" to="/dashboard" active-class="active" exact-active-class="active">
            Dashboard
          </router-link>
        </li>
        <li class="nav-item">
          <router-link class="nav-link" to="/expenses" active-class="active" exact-active-class="active">
            Expenses
          </router-link>
        </li>
        <li class="nav-item">
          <router-link class="nav-link" to="/incomes" active-class="active" exact-active-class="active">
            Incomes
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            class="nav-link"
            to="/share"
            active-class="active"
            exact-active-class="active"
          >
            Share Expenses
          </router-link>
        </li>
        <li class="nav-item">
          <router-link class="nav-link" to="/investstion" active-class="active" exact-active-class="active">
            Investments
          </router-link>
        </li>
      </ul>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item d-flex align-items-center me-3">
            <span class="text-muted">Welcome, {{ userFullName }}</span>
          </li>
          <li class="nav-item">
            <button
              @click="handleLogout"
              class="btn btn-outline-danger btn-sm"
              :disabled="loading"
            >
              Logout
            </button>
          </li>
        </ul>
      </div>
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

.nav-link.active {
  font-weight: 600;
  color: #36A2EB !important;
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
