import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

// Import components
import LoginForm from '../components/auth/LoginForm.vue'
import RegisterForm from '../components/auth/RegisterForm.vue'
import Dashboard from '../components/Dashboard.vue'
import TaskApp from '../components/TaskApp.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginForm,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterForm,
    meta: { requiresGuest: true }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../components/UserManagement.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/expenses',
    name: 'Expenses',
    component: () => import('../components/ExpenseTracker.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/expenses/create',
    name: 'ExpenseCreate',
    component: () => import('../components/ExpenseForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/expenses/:id/edit',
    name: 'ExpenseEdit',
    component: () => import('../components/ExpenseForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/incomes',
    name: 'Incomes',
    component: () => import('../components/IncomeTreker.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/incomes/create',
    name: 'IncomesCreate',
    component: () => import('../components/IncomeTrekerForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/incomes/:id/edit',
    name: 'IncomesEdit',
    component: () => import('../components/IncomeTrekerForm.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../components/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated

  // Check if route requires authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
    return
  }

  // Check if route requires guest (not authenticated)
  if (to.meta.requiresGuest && isAuthenticated) {
    next('/dashboard')
    return
  }

  next()
})

export default router
