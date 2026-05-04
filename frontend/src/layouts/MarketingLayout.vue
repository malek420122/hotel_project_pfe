<template>
  <DashboardShell
    :nav-items="navItems"
    :page-title="pageTitle"
    :role-label="$t('layout.marketing')"
    :user="auth.user"
    @logout="logout"
  />
</template>
<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Squares2X2Icon, TagIcon, TicketIcon, ChartBarIcon, StarIcon, EnvelopeIcon, GiftIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../stores/auth'
import { useI18n } from 'vue-i18n'
import DashboardShell from '../components/DashboardShell.vue'
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { t } = useI18n()
const navItems = computed(() => [
  { to: '/dashboard/marketing/overview', icon: Squares2X2Icon, label: t('nav.overview') },
  { to: '/dashboard/marketing/promotions', icon: TagIcon, label: t('nav.promotions') },
  { to: '/dashboard/marketing/promo-codes', icon: TicketIcon, label: t('nav.promo_codes') },
  { to: '/dashboard/marketing/statistics', icon: ChartBarIcon, label: t('nav.statistics') },
  { to: '/dashboard/marketing/reviews', icon: StarIcon, label: t('nav.reviews') },
  { to: '/dashboard/marketing/emails', icon: EnvelopeIcon, label: t('nav.send_offers') },
  { to: '/dashboard/marketing/loyalty', icon: GiftIcon, label: t('nav.loyalty') },
])
const pageTitle = computed(() => navItems.value.find(i => route.path.startsWith(i.to))?.label || t('layout.marketing'))
async function logout() { await auth.logout(); router.push('/login') }
</script>
