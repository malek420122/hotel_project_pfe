<template>
  <div class="dashboard-shell">
    <aside :class="['dashboard-sidebar', sidebarOpen ? 'sidebar-open' : '']">
      <div class="dashboard-sidebar-top">
        <RouterLink to="/" class="dashboard-logo-link">
          <AppLogo variant="dark" size="md" />
        </RouterLink>
        <p class="dashboard-role-caption">{{ roleLabel }}</p>
      </div>

      <nav class="dashboard-nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          :class="['dashboard-nav-item', isActive(item) && 'dashboard-nav-item-active']"
          :title="item.label"
          @click="sidebarOpen = false"
        >
          <span class="dashboard-nav-icon">
            <component :is="item.icon" v-if="typeof item.icon !== 'string'" class="dashboard-nav-icon-svg" />
            <span v-else>{{ item.icon }}</span>
          </span>
          <span class="dashboard-nav-label">{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="dashboard-sidebar-bottom">
        <div class="dashboard-user-row">
          <span class="dashboard-avatar">{{ userInitial }}</span>
          <div class="dashboard-user-meta">
            <p class="dashboard-user-name">{{ userName }}</p>
            <span class="dashboard-role-badge">{{ roleLabel }}</span>
          </div>
        </div>
        <button class="dashboard-logout" @click="$emit('logout')">
          <span>⎋</span>
          <span>{{ t('auth.logout') }}</span>
        </button>
      </div>
    </aside>

    <div class="dashboard-main">
      <header class="dashboard-header">
        <div class="dashboard-header-left">
          <button class="dashboard-menu-btn" @click="sidebarOpen = !sidebarOpen">☰</button>
          <h1 class="dashboard-title">{{ pageTitle }}</h1>
        </div>
        <div class="dashboard-header-right">
          <NotifBell />
          <LanguageSwitcher variant="dark" />
          <button class="dashboard-profile-trigger" @click="userMenuOpen = !userMenuOpen">
            <span class="dashboard-avatar">{{ userInitial }}</span>
            <span class="dashboard-profile-name">{{ userName }}</span>
          </button>
          <div v-if="userMenuOpen" class="dashboard-profile-menu">
            <RouterLink to="/dashboard/client/profile" class="dashboard-profile-menu-item">Profile</RouterLink>
            <button class="dashboard-profile-menu-item" @click="$emit('logout')">{{ t('auth.logout') }}</button>
          </div>
        </div>
      </header>

      <main class="dashboard-content">
        <transition name="dashboard-page-fade" mode="out-in">
          <RouterView />
        </transition>
      </main>
    </div>

    <nav class="dashboard-bottom-tabs">
      <RouterLink
        v-for="item in navItems.slice(0, 5)"
        :key="`tab-${item.to}`"
        :to="item.to"
        :class="['dashboard-tab-item', isActive(item) && 'dashboard-tab-item-active']"
      >
        <span class="dashboard-nav-icon">
          <component :is="item.icon" v-if="typeof item.icon !== 'string'" class="dashboard-nav-icon-svg" />
          <span v-else>{{ item.icon }}</span>
        </span>
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AppLogo from './AppLogo.vue'
import LanguageSwitcher from './LanguageSwitcher.vue'
import NotifBell from './NotifBell.vue'

const props = defineProps({
  navItems: { type: Array, required: true },
  pageTitle: { type: String, required: true },
  roleLabel: { type: String, required: true },
  user: { type: Object, default: null },
})

defineEmits(['logout'])

const route = useRoute()
const { t } = useI18n()
const sidebarOpen = ref(false)
const userMenuOpen = ref(false)

const userInitial = computed(() => {
  if (props.user?.role === 'marketing') return 'M'
  if (props.user?.role === 'receptionniste') return 'RT'
  return String(props.user?.prenom || props.user?.nom || 'U').charAt(0).toUpperCase()
})
const userName = computed(() => {
  if (props.user?.role === 'marketing') return t('layout.marketingTeam')
  if (props.user?.role === 'receptionniste') return t('layout.receptionTeam')
  const full = [props.user?.prenom, props.user?.nom].filter(Boolean).join(' ').trim()
  return full || props.user?.email || 'User'
})

function isActive(item) {
  return route.path.startsWith(item.to)
}

function onDocumentClick(event) {
  if (!(event.target instanceof HTMLElement)) return
  const root = event.target.closest('.dashboard-header-right')
  if (!root) userMenuOpen.value = false
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>
