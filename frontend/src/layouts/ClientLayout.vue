<template>
  <DashboardShell
    :nav-items="navItems"
    :page-title="pageTitle"
    :role-label="$t('layout.client_space')"
    :user="auth.user"
    @logout="logout"
  />
</template>

<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { HomeIcon, CalendarDaysIcon, PlusCircleIcon, SparklesIcon, CreditCardIcon, StarIcon, GiftIcon, UserCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import DashboardShell from '../components/DashboardShell.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()

const navItems = computed(() => [
  { to: '/dashboard/client/overview', icon: HomeIcon, label: t('nav.overview') },
  { to: '/dashboard/client/reservations', icon: CalendarDaysIcon, label: t('dashboard.my_reservations') },
  { to: '/hotels', icon: PlusCircleIcon, label: t('dashboard.new_booking') },
  { to: '/dashboard/client/services', icon: SparklesIcon, label: t('nav.my_services') },
  { to: '/dashboard/client/incidents', icon: ExclamationTriangleIcon, label: t('nav.report_problem') },
  { to: '/dashboard/client/payments', icon: CreditCardIcon, label: t('nav.payments') },
  { to: '/dashboard/client/reviews', icon: StarIcon, label: t('nav.my_reviews') },
  { to: '/dashboard/client/loyalty', icon: GiftIcon, label: t('nav.loyalty') },
  { to: '/dashboard/client/profile', icon: UserCircleIcon, label: t('nav.my_profile') },
])

const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('nav.dashboard'))

async function logout() {
  await auth.logout()
  router.push('/login')
}
</script>
