<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ t('dashboard.hello', { name: auth.user?.prenom || '' }) }} 👋</h2>
      <p class="text-gray-500">{{ t('dashboard.accountOverview') }}</p>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <KpiCard icon="📅" :label="t('dashboard.activeBookings')" :value="stats.active" color="blue" />
      <KpiCard icon="✅" :label="t('dashboard.completedStays')" :value="stats.done" color="green" />
      <KpiCard icon="🎁" :label="t('dashboard.loyaltyPoints')" :value="loyalty.points" color="gold" />
      <KpiCard icon="💰" :label="t('dashboard.totalSpent')" :value="`${stats.spent}€`" color="purple" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="card">
        <h3 class="mb-4 text-lg font-bold text-gray-800">{{ t('dashboard.nextStay') }}</h3>

        <div v-if="nextBooking" class="flex items-center gap-4">
          <div class="text-5xl">🏨</div>
          <div>
            <p class="font-semibold text-gray-800">{{ nextBooking.hotel?.nom }}</p>
            <p class="text-sm text-gray-500">{{ formatDateRange(nextBooking.dateArrivee, nextBooking.dateDepart) }}</p>
            <StatusBadge :status="nextBooking.statut" class="mt-2" />
          </div>
        </div>

        <div v-else class="py-6 text-center text-gray-400">
          <p class="mb-2 text-3xl">🛏️</p>
          <p class="text-sm">{{ t('dashboard.noUpcomingBooking') }}</p>
          <RouterLink to="/hotels" class="btn-primary mt-3 inline-block text-sm">{{ t('dashboard.bookNow') }}</RouterLink>
        </div>
      </div>

      <div class="card">
        <h3 class="mb-4 text-lg font-bold text-gray-800">{{ t('dashboard.loyaltyProgram') }}</h3>

        <div class="mb-3 flex items-center gap-3">
          <span class="text-3xl">{{ loyaltyLevel.icon }}</span>
          <div>
            <p class="text-lg font-bold text-gray-800">{{ loyaltyLevel.title }}</p>
            <p class="text-sm text-gray-500">{{ loyalty.points || 0 }} {{ t('dashboard.points') }}</p>
          </div>
        </div>

        <div v-if="loyaltyLevel.nextTarget > 0" class="mb-2">
          <div class="mb-1 flex justify-between text-xs text-gray-500">
            <span>{{ loyalty.points || 0 }} {{ t('dashboard.points') }}</span>
            <span>
              {{ t('dashboard.pointsToNext', { current: loyalty.points || 0, next: loyaltyLevel.nextTarget, level: loyaltyLevel.nextTitle }) }}
            </span>
          </div>
          <div class="h-3 overflow-hidden rounded-full bg-gray-200">
            <div :class="['h-full rounded-full bg-gradient-to-r from-accent to-yellow-400 transition-all duration-1000', toPctClass(loyaltyPercent)]" />
          </div>
        </div>

        <p v-else class="text-sm font-semibold text-emerald-600">{{ t('dashboard.maxLevelReached') }}</p>
      </div>
    </div>

    <div class="card">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-800">{{ t('dashboard.recent_reservations') }}</h3>
        <RouterLink to="/dashboard/client/reservations" class="text-sm text-secondary hover:underline">{{ t('common.viewAll') }}</RouterLink>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('dashboard.hotel') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('booking.room') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('dashboard.dates') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('dashboard.price') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('dashboard.status') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="res in recentReservations" :key="res._id" class="hover:bg-gray-50">
              <td class="px-3 py-3 font-medium">{{ res.hotel?.nom }}</td>
              <td class="px-3 py-3 text-gray-600">{{ getRoomTypeName(res.chambre?.type) }}</td>
              <td class="px-3 py-3 text-gray-500">{{ formatDateRange(res.dateArrivee, res.dateDepart) }}</td>
              <td class="px-3 py-3 font-semibold text-secondary">{{ Number(res.prixTotal || 0) }}€</td>
              <td class="px-3 py-3"><StatusBadge :status="res.statut" /></td>
            </tr>

            <tr v-if="!recentReservations.length">
              <td colspan="5" class="px-3 py-8 text-center text-gray-400">{{ t('dashboard.noReservations') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../../stores/auth'
import api from '../../../api'
import KpiCard from '../../../components/KpiCard.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const auth = useAuthStore()
const { locale, t } = useI18n()
const loyalty = ref({ points: 0, niveau: 'Bronze', niveaux: [] })
const statsApi = ref(null)

const stats = computed(() => {
  return {
    active: Number(statsApi.value?.reservations_actives || 0),
    done: Number(statsApi.value?.sejours_completes || 0),
    spent: Number(statsApi.value?.total_depenses || 0),
  }
})

const nextBooking = computed(() => {
  return statsApi.value?.prochain_sejour || null
})

const recentReservations = computed(() => Array.isArray(statsApi.value?.recent_reservations) ? statsApi.value.recent_reservations.slice(0, 5) : [])

const loyaltyLevel = computed(() => {
  const points = Number(loyalty.value.points || 0)

  if (points >= 5000) {
    return {
      icon: '💎',
      title: t('dashboard.levelPlatinum'),
      nextTitle: '',
      nextTarget: 0,
      min: 5000,
      max: 5000,
    }
  }

  if (points >= 2000) {
    return {
      icon: '🥇',
      title: t('dashboard.levelGold'),
      nextTitle: t('dashboard.levelPlatinum'),
      nextTarget: 5000,
      min: 2000,
      max: 5000,
    }
  }

  if (points >= 500) {
    return {
      icon: '🥈',
      title: t('dashboard.levelSilver'),
      nextTitle: t('dashboard.levelGold'),
      nextTarget: 2000,
      min: 500,
      max: 2000,
    }
  }

  return {
    icon: '🥉',
    title: t('dashboard.levelBronze'),
    nextTitle: t('dashboard.levelSilver'),
    nextTarget: 500,
    min: 0,
    max: 500,
  }
})

const loyaltyPercent = computed(() => {
  const points = Number(loyalty.value.points || 0)
  const level = loyaltyLevel.value
  if (!level.nextTarget || level.max <= level.min) return 100
  const inLevel = Math.max(0, points - level.min)
  const span = Math.max(1, level.max - level.min)
  return Math.min(100, (inLevel / span) * 100)
})

function toPctClass(value) {
  const normalized = Math.max(0, Math.min(100, Math.round((Number(value || 0) / 5)) * 5))
  return `w-pct-${normalized}`
}

function formatDisplayDate(dateValue) {
  return formatLocalizedDate(dateValue, locale.value, { day: 'numeric', month: 'long', year: 'numeric' }) || '—'
}

function formatDateRange(start, end) {
  const startText = formatDisplayDate(start)
  const endText = formatDisplayDate(end)
  return locale.value === 'ar' ? `${endText} ← ${startText}` : `${startText} → ${endText}`
}

function getRoomTypeName(type) {
  const key = String(type || '').toLowerCase().trim()
  if (!key) return '—'

  const map = {
    simple: t('booking.roomSingle'),
    standard: t('room.standard'),
    double: t('booking.roomDouble'),
    suite: t('booking.roomSuite'),
    deluxe: t('room.deluxe'),
  }

  return map[key] || String(type || '—')
}

onMounted(async () => {
  try {
    const { data } = await api.get('/client/stats')
    statsApi.value = data || null
    loyalty.value = {
      points: Number(data?.loyalty_points ?? data?.points_fidelite ?? 0),
      niveau: data?.niveau_fidelite || 'Bronze',
      niveaux: [],
    }
  } catch {
    statsApi.value = null
    loyalty.value = { points: 0, niveau: 'Bronze', niveaux: [] }
  }
})
</script>
