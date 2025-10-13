import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

// Import components
import LoginForm from '../components/auth/LoginForm.vue'
import RegisterForm from '../components/auth/RegisterForm.vue'
import Dashboard from '../components/Dashboard.vue'
import Investstion from '../components/Investstion.vue'

const ShareList = () => import('@/share/ShareList.vue');
const ShareTopic = () => import('@/share/ShareTopic.vue');
const JoinShare = () => import('@/share/JoinShare.vue');


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
  },

    {
        path: '/expenses/category/:slug',
        name: 'ExpenseCategory',
        component: () => import('../components/ExpenseCategory.vue'),
        props: true
    },
    {
        path: '/incomes/category/:slug',
        name: 'IncomeCategory',
        component: () => import('../components/IncomeCategory.vue'),
        props: true
    },

    {
        path: '/budgets',
        name: 'Budgets',
        component: () => import('../components/BudgetsPanel.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/share',
        name: 'share.list',
        component: ShareList,
        meta: { title: 'Share Expenses' }
    },
    {
        path: '/share/:id',
        name: 'share.topic',
        component: ShareTopic,
        meta: { title: 'Share Topic' }
    },
    {
        path: '/share/join/:token',
        name: 'share.join',
        component: JoinShare,
        meta: { title: 'Join Shared Topic' }
    },
    {
      path: '/investstion',
      name: 'investstion',
      component: Investstion,
      meta: { requiresAuth: true }
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
