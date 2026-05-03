import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: '',
          name: 'login',
          component: () => import('@/pages/auth/LoginPage.vue')
        }
      ],
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/pages/dashboard/DashboardPage.vue'),
          meta: { title: 'หน้าแรก' }
        },
        {
          path: 'pos',
          name: 'pos',
          component: () => import('@/pages/pos/PosPage.vue'),
          meta: { title: 'ขาย' }
        },
        {
          path: 'orders',
          name: 'orders',
          component: () => import('@/pages/orders/OrdersPage.vue'),
          meta: { title: 'รายการขาย' }
        },
        {
          path: 'products',
          name: 'products',
          component: () => import('@/pages/products/ProductsPage.vue'),
          meta: { title: 'สินค้า', requiresAdmin: true }
        },
        {
          path: 'sizes',
          name: 'sizes',
          component: () => import('@/pages/sizes/SizesPage.vue'),
          meta: { title: 'ไซต์', requiresAdmin: true }
        },
        {
          path: 'types',
          name: 'types',
          component: () => import('@/pages/types/TypesPage.vue'),
          meta: { title: 'ประเภท', requiresAdmin: true }
        },
        {
          path: 'toppings',
          name: 'toppings',
          component: () => import('@/pages/toppings/ToppingsPage.vue'),
          meta: { title: 'ท็อปปิ้ง', requiresAdmin: true }
        },
        {
          path: 'users',
          name: 'users',
          component: () => import('@/pages/users/UsersPage.vue'),
          meta: { title: 'ผู้ใช้งาน', requiresAdmin: true }
        },
      ]
    }
  ]
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Redirect unauthenticated users to login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  // Redirect authenticated users away from guest-only pages
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Fetch user profile if authenticated but user data not loaded
  if (authStore.isAuthenticated && !authStore.user) {
    await authStore.fetchMe()
  }

  // Redirect non-admin users away from admin-only pages
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
