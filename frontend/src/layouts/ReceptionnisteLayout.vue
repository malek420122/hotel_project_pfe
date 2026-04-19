<template>
  <DashboardShell
    :nav-items="navItems"
    :page-title="pageTitle"
    :role-label="$t('layout.reception')"
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
  { to: '/dashboard/receptionniste/queue', icon: '⏳', label: t('nav.queue') },
  { to: '/dashboard/receptionniste/checkin', icon: '🔑', label: t('nav.checkin') },
  { to: '/dashboard/receptionniste/checkout', icon: '🚪', label: t('nav.checkout') },
  { to: '/dashboard/receptionniste/rooms', icon: '🗂️', label: t('nav.room_grid') },
  { to: '/dashboard/receptionniste/special-requests', icon: '📋', label: t('nav.special_requests') },
])
const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('layout.reception'))
async function logout() { await auth.logout(); router.push('/login') }
</script>
