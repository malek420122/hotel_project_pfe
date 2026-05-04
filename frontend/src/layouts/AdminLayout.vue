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
import { Squares2X2Icon, BuildingOfficeIcon, RectangleStackIcon, UsersIcon, CreditCardIcon, ChartBarIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import DashboardShell from '../components/DashboardShell.vue'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
const navItems = computed(() => [
  { to: '/dashboard/admin/overview', icon: Squares2X2Icon, label: t('nav.overview') },
  { to: '/dashboard/admin/hotels', icon: BuildingOfficeIcon, label: t('nav.hotels') },
  { to: '/dashboard/admin/rooms', icon: RectangleStackIcon, label: t('nav.rooms') },
  { to: '/dashboard/admin/users', icon: UsersIcon, label: t('nav.users') },
  { to: '/dashboard/admin/payments', icon: CreditCardIcon, label: t('nav.payments') },
  { to: '/dashboard/admin/incidents', icon: ExclamationTriangleIcon, label: t('nav.incidents') },
  { to: '/dashboard/admin/pricing', icon: ChartBarIcon, label: t('nav.dynamic_pricing') },
])
const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('layout.admin'))
async function logout() { await auth.logout(); router.push('/login') }
</script>
