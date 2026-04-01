<template>
  <div class="flex h-screen bg-neutral">
    <!-- Sidebar -->
    <aside :class="['w-64 flex-shrink-0 flex flex-col transition-all duration-300', sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0']"
      style="background: linear-gradient(160deg, #003580 0%, #0071c2 100%);">
      <div class="p-5 border-b border-white/10">
        <RouterLink to="/" class="flex items-center gap-2 text-white">
          <span class="text-2xl">🏨</span>
          <span class="text-xl font-extrabold">HotelEase</span>
        </RouterLink>
        <p class="text-white/50 text-xs mt-1">{{ $t('layout.client_space') }}</p>
      </div>
      <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          :class="['sidebar-link', $route.path.startsWith(item.to) && item.to !== '/dashboard/client' ? 'active' : '']"
          @click="sidebarOpen = false">
          <span class="text-lg">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
          <span v-if="item.badge" class="ml-auto bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ item.badge }}</span>
        </RouterLink>
      </nav>
      <div class="p-3 border-t border-white/10">
        <div class="flex items-center gap-3 mb-3 px-3">
          <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-white font-bold text-sm">
            {{ auth.user?.prenom?.[0] || 'C' }}
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-white text-sm font-semibold truncate">{{ auth.user?.prenom }}</p>
            <p class="text-white/50 text-xs truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button @click="logout" class="sidebar-link w-full">
          <span>🚪</span><span>{{ $t('auth.logout') }}</span>
        </button>
      </div>
    </aside>
    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Topbar -->
      <header class="bg-white shadow-sm px-6 py-3 flex items-center justify-between flex-shrink-0">
        <div class="flex items-center gap-3">
          <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-100">☰</button>
          <h1 class="text-lg font-bold text-gray-800">{{ pageTitle }}</h1>
        </div>
        <div class="flex items-center gap-3">
          <LanguageSwitcher />
          <NotifBell />
        </div>
      </header>
      <main class="flex-1 overflow-y-auto p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import NotifBell from '../components/NotifBell.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const sidebarOpen = ref(false)
const { t } = useI18n()

const navItems = computed(() => [
  { to: '/dashboard/client/overview', icon: '🏠', label: t('nav.overview') },
  { to: '/dashboard/client/reservations', icon: '📅', label: t('dashboard.my_reservations') },
  { to: '/dashboard/client/new-booking', icon: '➕', label: t('dashboard.new_booking') },
  { to: '/dashboard/client/services', icon: '🛎️', label: t('nav.my_services') },
  { to: '/dashboard/client/payments', icon: '💳', label: t('nav.payments') },
  { to: '/dashboard/client/reviews', icon: '⭐', label: t('nav.my_reviews') },
  { to: '/dashboard/client/loyalty', icon: '🎁', label: t('nav.loyalty') },
  { to: '/dashboard/client/profile', icon: '👤', label: t('nav.my_profile') },
])

const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('nav.dashboard'))

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>
