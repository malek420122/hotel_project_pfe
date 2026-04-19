<template>
  <DashboardShell
    :nav-items="navItems"
    :page-title="pageTitle"
    :role-label="$t('layout.admin')"
    :user="auth.user"
    @logout="logout"
  />
</template>
<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import DashboardShell from '../components/DashboardShell.vue'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
const navItems = computed(() => [
  { to: '/dashboard/admin/overview', icon: '📊', label: t('nav.overview') },
  { to: '/dashboard/admin/hotels', icon: '🏨', label: t('nav.hotels') },
  { to: '/dashboard/admin/rooms', icon: '🛏️', label: t('nav.rooms') },
  { to: '/dashboard/admin/users', icon: '👥', label: t('nav.users') },
  { to: '/dashboard/admin/pricing', icon: '💰', label: t('nav.dynamic_pricing') },
])
const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('layout.admin'))
async function logout() { await auth.logout(); router.push('/login') }
</script>
