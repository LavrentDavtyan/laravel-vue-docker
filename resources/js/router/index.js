// resources/js/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

// Core pages
import LoginForm from '../components/auth/LoginForm.vue'
import RegisterForm from '../components/auth/RegisterForm.vue'
import Dashboard from '../components/Dashboard.vue'

// Lazy-loaded Share pages
const ShareList = () => import('@/share/ShareList.vue');
const ShareTopic = () => import('@/share/ShareTopic.vue');
const JoinShare = () => import('@/share/JoinShare.vue');
const JoinRequestPanel = () => import('@/share/JoinRequestPanel.vue');

// Services used by guards
import { shareService } from '@/services/shareService';

// --- Guard: allow opening /share/:id only if the user is a member/owner ---
// If backend returns 403, redirect to /share/:id/request so the user can submit a join request.
async function memberOnlyGuard(to) {
    const id = parseInt(to.params.id, 10);
    if (!Number.isFinite(id)) return true; // let the page handle bad ids

    try {
        await shareService.listMembers(id); // GET /api/share/topics/:id/members
        return true; // user is owner/member
    } catch (e) {
        const status = e?.response?.status || 0;
        if (status === 403) {
            // Not a member → go to request page
            return { name: 'share.request', params: { id } };
        }
        // For other errors (404/500), let the page handle it (or redirect to list if you prefer)
        return true;
    }
}

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
        props: true,
        meta: { requiresAuth: true }
    },
    {
        path: '/incomes/category/:slug',
        name: 'IncomeCategory',
        component: () => import('../components/IncomeCategory.vue'),
        props: true,
        meta: { requiresAuth: true }
    },

    {
        path: '/budgets',
        name: 'Budgets',
        component: () => import('../components/BudgetsPanel.vue'),
        meta: { requiresAuth: true }
    },

    // --- Share module routes ---
    {
        path: '/share',
        name: 'share.list',
        component: ShareList,
        meta: { title: 'Share Expenses', requiresAuth: true }
    },
    {
        path: '/share/:id',
        name: 'share.topic',
        component: ShareTopic,
        meta: { title: 'Share Topic', requiresAuth: true },
        beforeEnter: memberOnlyGuard, // member/owner-only guard
    },
    {
        path: '/share/join/:token',
        name: 'share.join',
        component: JoinShare,
        meta: { title: 'Join Shared Topic', requiresAuth: true }
    },
    {
        path: '/share/:id/request',
        name: 'share.request',
        component: JoinRequestPanel,
        meta: { title: 'Request Access to Topic', requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Global navigation guards
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()
    const isAuthenticated = authStore.isAuthenticated

    // requires auth
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login')
        return
    }

    // requires guest
    if (to.meta.requiresGuest && isAuthenticated) {
        next('/dashboard')
        return
    }

    next()
})

export default router
