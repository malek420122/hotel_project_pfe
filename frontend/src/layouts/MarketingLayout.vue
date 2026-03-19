<template>
  <div class="flex h-screen bg-neutral">
    <aside class="w-64 flex-shrink-0 flex flex-col" style="background: linear-gradient(160deg, #581c87 0%, #7c3aed 100%);">
      <div class="p-5 border-b border-white/10">
        <RouterLink to="/" class="flex items-center gap-2 text-white">
          <span class="text-2xl">🏨</span>
          <span class="text-xl font-extrabold">HotelEase</span>
        </RouterLink>
        <p class="text-white/50 text-xs mt-1">Marketing</p>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
          :class="['sidebar-link', $route.path.startsWith(item.to) ? 'active' : '']">
          <span class="text-lg">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>
      <div class="p-3 border-t border-white/10">
        <button @click="logout" class="sidebar-link w-full"><span>🚪</span><span>{{ t('auth.logout') }}</span></button>
      </div>
    </aside>
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="bg-white shadow-sm px-6 py-3 flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-800">{{ pageTitle }}</h1>
        <div class="flex items-center gap-3">
          <LanguageSwitcher />
          <NotifBell />
          <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-bold">Marketing</span>
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
import { useI18n } from 'vue-i18n'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import NotifBell from '../components/NotifBell.vue'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
const navItems = computed(() => [
  { to: '/dashboard/marketing/overview', icon: '📊', label: t('nav.overview') },
  { to: '/dashboard/marketing/promotions', icon: '🎯', label: t('nav.promotions') },
  { to: '/dashboard/marketing/promo-codes', icon: '🏷️', label: t('nav.promo_codes') },
  { to: '/dashboard/marketing/statistics', icon: '📈', label: t('nav.statistics') },
  { to: '/dashboard/marketing/reviews', icon: '⭐', label: t('nav.reviews') },
  { to: '/dashboard/marketing/loyalty', icon: '🎁', label: t('nav.loyalty') },
])
const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('nav.marketing'))
async function logout() { await auth.logout(); router.push('/login') }
</script>
