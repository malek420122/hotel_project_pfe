<template>
  <div class="flex h-screen bg-neutral">
    <aside class="w-64 flex-shrink-0 flex flex-col" style="background: linear-gradient(160deg, #1a1a2e 0%, #003580 100%);">
      <div class="p-5 border-b border-white/10">
        <RouterLink to="/" class="flex items-center gap-2 text-white">
          <span class="text-2xl">🏨</span>
          <span class="text-xl font-extrabold">HotelEase</span>
        </RouterLink>
        <p class="text-white/50 text-xs mt-1">Administration</p>
      </div>
      <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          :class="['sidebar-link', $route.path.startsWith(item.to) ? 'active' : '']">
          <span class="text-lg">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
      <div class="p-3 border-t border-white/10">
        <button @click="logout" class="sidebar-link w-full">
          <span>🚪</span><span>Déconnexion</span>
        </button>
      </div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white shadow-sm px-6 py-3 flex items-center justify-between flex-shrink-0">
        <h1 class="text-lg font-bold text-gray-800">{{ pageTitle }}</h1>
        <div class="flex items-center gap-3">
          <LanguageSwitcher />
          <NotifBell />
          <span class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full font-bold">Admin</span>
        </div>
      </header>
      <main class="flex-1 overflow-y-auto p-6"><RouterView /></main>
    </div>
  </div>
</template>
<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import NotifBell from '../components/NotifBell.vue'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const navItems = [
  { to: '/dashboard/admin/overview', icon: '📊', label: 'Vue d\'ensemble' },
  { to: '/dashboard/admin/hotels', icon: '🏨', label: 'Hôtels' },
  { to: '/dashboard/admin/rooms', icon: '🛏️', label: 'Chambres' },
  { to: '/dashboard/admin/users', icon: '👥', label: 'Utilisateurs' },
  { to: '/dashboard/admin/pricing', icon: '💰', label: 'Prix dynamiques' },
]
const pageTitle = computed(() => navItems.find(i => route.path.startsWith(i.to))?.label || 'Admin')
async function logout() { await auth.logout(); router.push('/login') }
</script>
