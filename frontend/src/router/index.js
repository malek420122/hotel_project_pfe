import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // Public
  { path: '/', component: () => import('../pages/HomePage.vue') },
  { path: '/hotels', component: () => import('../pages/HotelsPage.vue') },
  { path: '/hotels/:id', component: () => import('../pages/HotelDetailPage.vue') },
  { path: '/login', component: () => import('../pages/LoginPage.vue') },
  { path: '/register', component: () => import('../pages/RegisterPage.vue') },

  // Client Dashboard
  {
    path: '/dashboard/client',
    component: () => import('../layouts/ClientLayout.vue'),
    meta: { requiresAuth: true, role: 'client' },
    redirect: '/dashboard/client/overview',
    children: [
      { path: 'overview', component: () => import('../pages/dashboard/client/OverviewPage.vue') },
      { path: 'reservations', component: () => import('../pages/dashboard/client/ReservationsPage.vue') },
      { path: 'new-booking', component: () => import('../pages/dashboard/client/NewBookingPage.vue') },
      { path: 'services', component: () => import('../pages/dashboard/client/ServicesPage.vue') },
      { path: 'payments', component: () => import('../pages/dashboard/client/PaymentsPage.vue') },
      { path: 'reviews', component: () => import('../pages/dashboard/client/ReviewsPage.vue') },
      { path: 'loyalty', component: () => import('../pages/dashboard/client/LoyaltyPage.vue') },
      { path: 'profile', component: () => import('../pages/dashboard/client/ProfilePage.vue') },
    ]
  },

  // Admin Dashboard
  {
    path: '/dashboard/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, role: 'admin' },
    redirect: '/dashboard/admin/overview',
    children: [
      { path: 'overview', component: () => import('../pages/dashboard/admin/OverviewPage.vue') },
      { path: 'hotels', component: () => import('../pages/dashboard/admin/HotelsPage.vue') },
      { path: 'rooms', component: () => import('../pages/dashboard/admin/RoomsPage.vue') },
      { path: 'users', component: () => import('../pages/dashboard/admin/UsersPage.vue') },
      { path: 'pricing', component: () => import('../pages/dashboard/admin/PricingPage.vue') },
    ]
  },

  // Réceptionniste Dashboard
  {
    path: '/dashboard/receptionniste',
    component: () => import('../layouts/ReceptionnisteLayout.vue'),
    meta: { requiresAuth: true, role: 'receptionniste' },
    redirect: '/dashboard/receptionniste/queue',
    children: [
      { path: 'queue', component: () => import('../pages/dashboard/receptionniste/QueuePage.vue') },
      { path: 'checkin', component: () => import('../pages/dashboard/receptionniste/CheckInPage.vue') },
      { path: 'checkout', component: () => import('../pages/dashboard/receptionniste/CheckOutPage.vue') },
      { path: 'rooms', component: () => import('../pages/dashboard/receptionniste/RoomGridPage.vue') },
      { path: 'special-requests', component: () => import('../pages/dashboard/receptionniste/SpecialRequestsPage.vue') },
    ]
  },

  // Marketing Dashboard
  {
    path: '/dashboard/marketing',
    component: () => import('../layouts/MarketingLayout.vue'),
    meta: { requiresAuth: true, role: 'marketing' },
    redirect: '/dashboard/marketing/overview',
    children: [
      { path: 'overview', component: () => import('../pages/dashboard/marketing/OverviewPage.vue') },
      { path: 'promotions', component: () => import('../pages/dashboard/marketing/PromotionsPage.vue') },
      { path: 'promo-codes', component: () => import('../pages/dashboard/marketing/PromoCodesPage.vue') },
      { path: 'statistics', component: () => import('../pages/dashboard/marketing/StatisticsPage.vue') },
      { path: 'reviews', component: () => import('../pages/dashboard/marketing/ReviewsPage.vue') },
      { path: 'loyalty', component: () => import('../pages/dashboard/marketing/LoyaltyPage.vue') },
    ]
  },

  // 404
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// Navigation guard
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token')
  const user = JSON.parse(localStorage.getItem('user') || 'null')

  if (to.meta.requiresAuth) {
    if (!token || !user) {
      next('/login')
      return
    }
    if (to.meta.role && user.role !== 'admin' && user.role !== to.meta.role) {
      // Admin can access everything, others are redirected
      const roleMap = { client: '/hotels', admin: '/dashboard/admin', receptionniste: '/dashboard/receptionniste', marketing: '/dashboard/marketing' }
      next(roleMap[user.role] || '/')
      return
    }
  }

  // Redirect logged-in users away from auth pages
  if ((to.path === '/login' || to.path === '/register') && token && user) {
    const roleMap = { client: '/hotels', admin: '/dashboard/admin', receptionniste: '/dashboard/receptionniste', marketing: '/dashboard/marketing' }
    next(roleMap[user.role] || '/')
    return
  }

  next()
})

export default router
