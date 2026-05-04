<template>
  <nav :class="['fixed top-0 left-0 right-0 z-50 navbar-shell', scrolled ? 'nav-solid' : 'nav-glass']">
    <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
      <RouterLink to="/" class="flex items-center gap-2">
        <AppLogo :variant="logoVariant" size="md" />
      </RouterLink>

      <div class="hidden md:flex items-center gap-6">
        <RouterLink v-for="link in navLinks" :key="link.to" :to="link.to" :class="['nav-link', route.path === link.to && 'nav-link-active']">
          {{ link.label }}
        </RouterLink>
      </div>

      <div class="flex items-center gap-3">
        <LanguageSwitcher />

        <template v-if="auth.isAuthenticated">
          <div ref="userMenuRef" class="relative">
            <button type="button" class="user-pill" @click="userMenuOpen = !userMenuOpen" :aria-expanded="userMenuOpen ? 'true' : 'false'" aria-haspopup="menu">
              <span class="user-avatar">{{ userInitials }}</span>
              <span class="user-name">{{ displayName }}</span>
              <span :class="['user-chevron', userMenuOpen && 'user-chevron-open']">▾</span>
            </button>

            <transition name="user-menu-drop">
              <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-50 overflow-hidden z-[999]" role="menu">
                
                <!-- Section Header : Infos Utilisateur -->
                <div class="px-5 py-4 bg-gray-50/50 border-b border-gray-100">
                  <div class="flex items-center space-x-3">
                    <div class="p-2 bg-orange-100 rounded-lg">
                      <User :size="20" class="text-orange-600" />
                    </div>
                    <div class="min-w-0">
                      <p class="text-[#2D1B08] font-bold text-sm truncate">{{ displayName }}</p>
                      <p class="text-gray-500 text-xs truncate">{{ auth.user?.email || '' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Liens du Menu -->
                <div class="p-2">
                  <!-- Role -->
                  <div class="flex items-center px-4 py-3 text-sm text-orange-600 font-semibold bg-orange-50 rounded-xl mb-1">
                    <ShieldCheck :size="18" class="mr-3 flex-shrink-0" />
                    <span class="truncate">{{ roleLabel }}</span>
                  </div>

                  <!-- Tableau de Bord -->
                  <RouterLink :to="dashboardUrl" class="flex items-center px-4 py-3 text-sm text-[#2D1B08] hover:bg-gray-50 rounded-xl transition-colors group" @click="userMenuOpen = false">
                    <LayoutDashboard :size="18" class="mr-3 text-gray-400 group-hover:text-orange-500 flex-shrink-0" />
                    <span>{{ t('nav.dashboard') }}</span>
                  </RouterLink>

                  <template v-if="String(auth.user?.role || '') === 'client'">
                    <RouterLink to="/dashboard/client/reservations" class="flex items-center px-4 py-3 text-sm text-[#2D1B08] hover:bg-gray-50 rounded-xl transition-colors group" @click="userMenuOpen = false">
                      <ClipboardList :size="18" class="mr-3 text-gray-400 group-hover:text-orange-500 flex-shrink-0" />
                      <span>Mes réservations</span>
                    </RouterLink>
                    <RouterLink to="/dashboard/client/reviews" class="flex items-center px-4 py-3 text-sm text-[#2D1B08] hover:bg-gray-50 rounded-xl transition-colors group" @click="userMenuOpen = false">
                      <Star :size="18" class="mr-3 text-gray-400 group-hover:text-orange-500 flex-shrink-0" />
                      <span>Mes avis</span>
                    </RouterLink>
                    <RouterLink to="/dashboard/client/loyalty" class="flex items-center px-4 py-3 text-sm text-[#2D1B08] hover:bg-gray-50 rounded-xl transition-colors group" @click="userMenuOpen = false">
                      <Gift :size="18" class="mr-3 text-gray-400 group-hover:text-orange-500 flex-shrink-0" />
                      <span>Programme fidélité</span>
                    </RouterLink>
                    <RouterLink to="/dashboard/client/profile" class="flex items-center px-4 py-3 text-sm text-[#2D1B08] hover:bg-gray-50 rounded-xl transition-colors group" @click="userMenuOpen = false">
                      <UserCircle :size="18" class="mr-3 text-gray-400 group-hover:text-orange-500 flex-shrink-0" />
                      <span>Mon profil</span>
                    </RouterLink>
                  </template>

                  <!-- Déconnexion -->
                  <button type="button" @click="handleLogout" class="w-full flex items-center px-4 py-3 text-sm text-red-500 hover:bg-red-50 rounded-xl transition-colors group mt-1 border-t border-gray-50">
                    <LogOut :size="18" class="mr-3 text-red-400 group-hover:text-red-600 flex-shrink-0" />
                    <span class="font-medium">{{ t('auth.logout') }}</span>
                  </button>
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
import { 
  User, 
  ShieldCheck, 
  LayoutDashboard, 
  LogOut,
  ClipboardList,
  Star,
  Gift,
  UserCircle
} from 'lucide-vue-next'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const scrolled = ref(false)
const userMenuOpen = ref(false)
const userMenuRef = ref(null)

const logoVariant = computed(() => {
  if (route.path !== '/') return 'light'
  return scrolled.value ? 'light' : 'dark'
})

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
  handleScroll()
  window.addEventListener('scroll', handleScroll)
  document.addEventListener('click', handleDocumentClick)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<style scoped>
.navbar-shell {
  transition:
    background-color 0.35s ease,
    box-shadow 0.35s ease,
    border-color 0.35s ease,
    backdrop-filter 0.35s ease,
    transform 0.35s ease;
  will-change: background-color, box-shadow, border-color, backdrop-filter;
}

.nav-glass {
  background: rgba(253, 251, 247, 0);
  backdrop-filter: blur(0px);
  box-shadow: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0);
}

.nav-solid {
  background: rgba(255, 255, 255, 0.92);
  -webkit-backdrop-filter: blur(16px) saturate(160%);
  backdrop-filter: blur(16px) saturate(160%);
  box-shadow: 0 4px 20px rgba(45, 27, 8, 0.05);
  border-bottom: 1px solid rgba(45, 27, 8, 0.06);
}

.nav-link {
  color: #8B4513;
  font-weight: 600;
  position: relative;
  transition: color 0.2s ease;
}

.nav-link:hover {
  color: #D4820A;
}

.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -4px;
  width: 0;
  height: 2px;
  background: #D4820A;
  transition: width 0.2s ease;
}

.nav-link:hover::after,
.nav-link-active::after {
  width: 100%;
}

.nav-link-active {
  color: #D4820A;
}

.login-link {
  color: #8B4513;
  font-weight: 500;
  text-decoration: none;
  transition: opacity 0.2s ease;
}

.login-link:hover {
  opacity: 0.7;
  color: #D4820A;
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
  color: #8B4513;
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-chevron {
  color: #8B4513;
  font-size: 0.72rem;
  transform: rotate(0deg);
  transition: transform 0.2s ease;
}

.user-chevron-open {
  transform: rotate(180deg);
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
