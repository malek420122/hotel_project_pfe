<template>
  <div class="flex h-screen bg-neutral">
    <aside class="w-64 flex-shrink-0 flex flex-col" style="background: linear-gradient(160deg, #065f46 0%, #10b981 100%);">
      <div class="p-5 border-b border-white/10">
        <RouterLink to="/" class="flex items-center gap-2 text-white">
          <span class="text-2xl">🏨</span>
          <span class="text-xl font-extrabold">HotelEase</span>
        </RouterLink>
        <p class="text-white/50 text-xs mt-1">Réception</p>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          :class="['sidebar-link', $route.path.startsWith(item.to) ? 'active' : '']">
          <span class="text-lg">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
      <div class="p-3 border-t border-white/10">
        <button @click="logout" class="sidebar-link w-full"><span>🚪</span><span>Déconnexion</span></button>
      </div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white shadow-sm px-6 py-3 flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-800">{{ pageTitle }}</h1>
        <div class="flex items-center gap-3">
          <LanguageSwitcher />
          <NotifBell />
          <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold">Réceptionniste</span>
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
  { to: '/dashboard/receptionniste/queue', icon: '⏳', label: 'File d\'attente' },
  { to: '/dashboard/receptionniste/checkin', icon: '🔑', label: 'Check-In' },
  { to: '/dashboard/receptionniste/checkout', icon: '🚪', label: 'Check-Out' },
  { to: '/dashboard/receptionniste/rooms', icon: '🗂️', label: 'Grille chambres' },
  { to: '/dashboard/receptionniste/special-requests', icon: '📋', label: 'Demandes spéciales' },
]
const pageTitle = computed(() => navItems.find(i => route.path.startsWith(i.to))?.label || 'Réception')
async function logout() { await auth.logout(); router.push('/login') }
</script>
