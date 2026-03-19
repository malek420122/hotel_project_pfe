<template>
  <nav :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', scrolled ? 'bg-white shadow-md' : 'bg-transparent']">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
      <RouterLink to="/" class="flex items-center gap-2">
        <span class="text-2xl">🏨</span>
        <span :class="['text-xl font-extrabold', scrolled ? 'text-primary' : 'text-white']">HotelEase</span>
      </RouterLink>
      <!-- Desktop nav -->
      <div class="hidden md:flex items-center gap-6">
        <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to"
          :class="['font-medium hover:text-accent transition-colors', scrolled ? 'text-gray-700' : 'text-white']">
          {{ link.label }}
        </RouterLink>
      </div>
      <div class="flex items-center gap-3">
        <LanguageSwitcher />
        <template v-if="auth.isAuthenticated">
          <RouterLink :to="dashboardUrl" :class="['btn-primary text-sm', !scrolled && 'bg-white/20 hover:bg-white/30 border border-white/30']">
            {{ t('nav.dashboard') }}
          </RouterLink>
        </template>
        <template v-else>
          <RouterLink to="/login" :class="['font-semibold text-sm', scrolled ? 'text-secondary' : 'text-white']">{{ t('auth.login') }}</RouterLink>
          <RouterLink to="/register" class="btn-primary text-sm">{{ t('auth.register') }}</RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from './LanguageSwitcher.vue'

const { t } = useI18n()
const auth = useAuthStore()
const scrolled = ref(false)

const navLinks = computed(() => [
  { to: '/', label: t('nav.home') },
  { to: '/hotels', label: t('nav.hotels') },
])

const dashboardUrl = computed(() => {
  const map = { admin: '/dashboard/admin', client: '/dashboard/client', receptionniste: '/dashboard/receptionniste', marketing: '/dashboard/marketing' }
  return map[auth.user?.role] || '/dashboard/client'
})

function handleScroll() { scrolled.value = window.scrollY > 50 }
onMounted(() => window.addEventListener('scroll', handleScroll))
onUnmounted(() => window.removeEventListener('scroll', handleScroll))
</script>
