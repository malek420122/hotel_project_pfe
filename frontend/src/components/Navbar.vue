<template>
  <nav :class="['fixed top-0 left-0 right-0 z-50 transition-all duration-300', scrolled ? 'nav-solid' : 'nav-glass']">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
      <RouterLink to="/" class="flex items-center gap-2">
        <AppLogo variant="dark" size="md" />
      </RouterLink>

      <div class="hidden md:flex items-center gap-6">
        <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to" :class="['nav-link', route.path === link.to && 'nav-link-active']">
          {{ link.label }}
        </RouterLink>
      </div>

      <div class="flex items-center gap-3">
        <LanguageSwitcher variant="dark" />

        <template v-if="auth.isAuthenticated">
          <div ref="userMenuRef" class="relative">
            <button type="button" class="user-pill" @click="userMenuOpen = !userMenuOpen" :aria-expanded="userMenuOpen ? 'true' : 'false'" aria-haspopup="menu">
              <span class="user-avatar">{{ userInitials }}</span>
              <span class="user-name">{{ displayName }}</span>
              <span :class="['user-chevron', userMenuOpen && 'user-chevron-open']">▾</span>
            </button>

            <transition name="user-menu-drop">
              <div v-if="userMenuOpen" class="user-menu" role="menu">
                <div class="user-menu-head">
                  <p class="user-menu-name">👤 {{ displayName }}</p>
                  <p class="user-menu-email">{{ auth.user?.email || '' }}</p>
                  <p class="user-menu-role">🏷️ {{ roleLabel }}</p>
                </div>

                <div class="user-menu-group">
                  <RouterLink :to="dashboardUrl" class="user-menu-item" @click="userMenuOpen = false">🏠 {{ t('nav.dashboard') }}</RouterLink>
                  <template v-if="String(auth.user?.role || '') === 'client'">
                    <RouterLink to="/dashboard/client/reservations" class="user-menu-item" @click="userMenuOpen = false">📋 Mes réservations</RouterLink>
                    <RouterLink to="/dashboard/client/reviews" class="user-menu-item" @click="userMenuOpen = false">⭐ Mes avis</RouterLink>
                    <RouterLink to="/dashboard/client/loyalty" class="user-menu-item" @click="userMenuOpen = false">🎁 Programme fidélité</RouterLink>
                    <RouterLink to="/dashboard/client/profile" class="user-menu-item" @click="userMenuOpen = false">👤 Mon profil</RouterLink>
                  </template>
                </div>

                <div class="user-menu-group">
                  <button type="button" class="user-menu-item user-menu-item-danger" @click="handleLogout">🚪 {{ t('auth.logout') }}</button>
                </div>
              </div>
            </transition>
          </div>
        </template>

        <template v-else>
          <RouterLink to="/login" class="login-link text-sm">{{ t('auth.login') }}</RouterLink>
          <RouterLink to="/register" class="register-btn text-sm">{{ t('auth.register') }}</RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'
import LanguageSwitcher from './LanguageSwitcher.vue'
import AppLogo from './AppLogo.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const scrolled = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)

const navLinks = computed(() => [
  { to: '/', label: t('nav.home') },
  { to: '/hotels', label: t('nav.hotels') },
])

const dashboardUrl = computed(() => {
  const map = {
    admin: '/dashboard/admin',
    client: '/dashboard/client/overview',
    receptionniste: '/dashboard/receptionniste',
    marketing: '/dashboard/marketing',
  }
  return map[auth.user?.role] || '/dashboard/client/overview'
})

const roleLabel = computed(() => {
  const role = String(auth.user?.role || '').toLowerCase()
  const map = {
    admin: 'Admin',
    client: 'Client',
    receptionniste: 'Réceptionniste',
    marketing: 'Marketing',
  }
  return map[role] || role || 'Utilisateur'
})

const displayName = computed(() => {
  if (auth.user?.role === 'receptionniste') return t('layout.receptionTeam')
  if (auth.user?.role === 'marketing') return t('layout.marketingTeam')
  const first = auth.user?.prenom || ''
  const last = auth.user?.nom || ''
  const full = `${first} ${last}`.trim()
  return full || auth.user?.name || auth.user?.email || 'User'
})

const userInitials = computed(() => {
  const words = displayName.value.split(' ').filter(Boolean)
  if (words.length === 0) return 'U'
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase()
  return `${words[0].slice(0, 1)}${words[1].slice(0, 1)}`.toUpperCase()
})

async function handleLogout() {
  userMenuOpen.value = false
  await auth.logout()
  await router.push('/')
}

function handleScroll() {
  scrolled.value = window.scrollY > 50
}

function handleDocumentClick(event) {
  if (!userMenuRef.value) return
  if (!userMenuRef.value.contains(event.target)) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleDocumentClick)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<style scoped>
.nav-glass {
  background: rgba(10, 20, 60, 0.3);
  backdrop-filter: blur(10px);
}

.nav-solid {
  background: #0f172a;
  box-shadow: 0 10px 24px rgba(2, 6, 23, 0.28);
}

.nav-link {
  color: #fff;
  font-weight: 600;
  position: relative;
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: #f59e0b;
}

.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -4px;
  width: 0;
  height: 2px;
  background: #f59e0b;
  transition: width 0.2s ease;
}

.nav-link:hover::after,
.nav-link-active::after {
  width: 100%;
}

.nav-link-active {
  color: #fbbf24;
}

.login-link {
  color: #fff;
  font-weight: 500;
  text-decoration: none;
  transition: opacity 0.2s ease;
}

.login-link:hover {
  opacity: 0.84;
}

.register-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  border-radius: 999px;
  padding: 0.48rem 0.95rem;
  background: linear-gradient(90deg, #f59e0b, #f97316);
  box-shadow: 0 8px 18px rgba(249, 115, 22, 0.38);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.register-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(249, 115, 22, 0.48);
}

.user-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.52rem;
}

.user-avatar {
  display: inline-flex;
  width: 34px;
  height: 34px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 800;
}

.user-name {
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-chevron {
  color: #fff;
  font-size: 0.72rem;
  transform: rotate(0deg);
  transition: transform 0.2s ease;
}

.user-chevron-open {
  transform: rotate(180deg);
}

.user-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  min-width: 220px;
  border-radius: 16px;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
  overflow: hidden;
  z-index: 70;
}

.user-menu-head {
  padding: 10px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.user-menu-name {
  color: #fff;
  font-size: 0.92rem;
  font-weight: 700;
}

.user-menu-email {
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.82rem;
  margin-top: 2px;
}

.user-menu-role {
  color: #fbbf24;
  font-size: 0.78rem;
  margin-top: 4px;
  font-weight: 700;
}

.user-menu-group + .user-menu-group {
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.user-menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  color: #fff;
  font-size: 0.9rem;
  text-align: left;
}

.user-menu-item:hover {
  background: rgba(245, 158, 11, 0.2);
}

.user-menu-item-danger {
  color: #ef4444;
}

.user-menu-item-danger:hover {
  background: rgba(239, 68, 68, 0.1);
}

.user-menu-drop-enter-active,
.user-menu-drop-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.user-menu-drop-enter-from,
.user-menu-drop-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.user-menu-drop-enter-to,
.user-menu-drop-leave-from {
  opacity: 1;
  transform: translateY(0);
}
</style>
