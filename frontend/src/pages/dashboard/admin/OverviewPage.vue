<template>
  <div class="space-y-6">
    <header class="panel-card flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <h2 class="text-3xl font-serif font-bold text-[#2D1B08] tracking-tight">{{ t('overview.greeting') }}, {{ formattedAdminName }}</h2>
          <Sparkles :size="28" class="text-[#FF8C00] opacity-85" />
        </div>
        <p class="text-xs text-slate-500 font-medium mt-1 tracking-tight uppercase">{{ formattedDate }}</p>
      </div>
      <p class="text-xs text-slate-400">{{ t('overview.title') }}</p>
    </header>

    <section v-if="loading" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="n in 4" :key="`s-${n}`" class="panel-card animate-pulse">
        <div class="h-6 w-6 rounded bg-white/10"></div>
        <div class="mt-4 h-8 w-20 rounded bg-white/10"></div>
        <div class="mt-2 h-4 w-36 rounded bg-white/10"></div>
        <div class="mt-4 h-3 w-28 rounded bg-white/10"></div>
      </div>
    </section>

    <section v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <article v-for="stat in statsCards" :key="stat.key" class="panel-card stat-card" :style="stat.cardStyle">
        <div class="icon-wrapper">
          <component v-if="stat.icon && typeof stat.icon !== 'string'" :is="stat.icon" :size="64" stroke-width="1" class="stat-icon-main" />
          <span v-else class="stat-icon-main">{{ stat.icon }}</span>

          <div class="icon-badge">
            <component :is="stat.badgeIcon" :size="20" />
          </div>
        </div>
        <p class="stat-value">{{ loadFailed ? '--' : stat.value }}</p>
        <p class="stat-label">{{ stat.label }}</p>
        <div v-if="stat.trend !== null" class="trend-badge" :class="stat.trend >= 0 ? 'trend-positive' : 'trend-negative'">
          <span class="trend-arrow">{{ stat.trend >= 0 ? '↑' : '↓' }}</span>
          <span>{{ Math.abs(stat.trend) }}% {{ t('overview.vsLastMonth') }}</span>
        </div>
      </article>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <article class="panel-card">
        <h3 class="section-title">{{ t('overview.revenueByHotel') }}</h3>
        <div v-if="loading" class="h-64 animate-pulse rounded bg-white/5"></div>
        <template v-else>
          <p v-if="loadFailed" class="empty-state">{{ t('overview.dataUnavailable') }}</p>
          <div class="h-64">
            <Doughnut :key="donutChartKey" :data="revenueChart" :options="doughnutOpts" />
          </div>
          <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div v-for="item in donutLegend" :key="item.label" class="legend-row">
              <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
              <span class="legend-name">{{ item.label }}</span>
              <span class="legend-pct">{{ item.percent }}%</span>
            </div>
          </div>
        </template>
      </article>

      <article class="panel-card">
        <h3 class="section-title">{{ t('overview.reservationsByMonth') }}</h3>
        <div v-if="loading" class="h-64 animate-pulse rounded bg-white/5"></div>
        <p v-else-if="loadFailed" class="empty-state">{{ t('overview.dataUnavailable') }}</p>
        <div v-else class="h-64">
          <Bar :key="barChartKey" :data="reservationsChart" :options="barOpts" />
        </div>
      </article>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <article class="panel-card">
        <h3 class="section-title">{{ t('overview.topHotels') }}</h3>
        <div v-if="loading" class="space-y-4">
          <div v-for="n in 5" :key="`top-${n}`" class="h-10 animate-pulse rounded bg-white/5"></div>
        </div>
        <div v-else class="space-y-4">
          <p v-if="loadFailed" class="empty-state">{{ t('overview.dataUnavailable') }}</p>
          <div v-for="(hotel, idx) in topHotels" :key="hotel.id || idx" class="flex items-center gap-3">
            <span class="rank-badge">{{ idx + 1 }}</span>
            <div class="flex-1">
              <div class="mb-1 flex items-center justify-between gap-2">
                <span class="truncate text-sm text-slate-100">{{ asHotelName(hotel?.nom) }}</span>
                <span class="text-sm font-semibold text-emerald-300">{{ formatMoney(hotel?.revenu) }}</span>
              </div>
              <div class="h-2 overflow-hidden rounded-full bg-white/10">
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-blue-500" :style="{ width: `${normalizedPercent(hotel?.pct)}%` }"></div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <article class="panel-card">
        <h3 class="section-title">{{ t('overview.recentBookings') }}</h3>
        <div v-if="loading" class="space-y-3">
          <div v-for="n in 5" :key="`recent-${n}`" class="h-14 animate-pulse rounded bg-white/5"></div>
        </div>
        <div v-else class="space-y-3">
          <p v-if="loadFailed" class="empty-state">{{ t('overview.dataUnavailable') }}</p>
          <div v-for="reservation in recentReservations" :key="reservation.id" class="reservation-row">
            <img :src="reservation.thumbnail" :alt="t('overview.hotelAlt')" class="reservation-thumb" />
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-white">{{ reservation.guestName }}</p>
              <p class="truncate text-xs text-slate-300">{{ reservation.hotelName }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-amber-300">{{ formatMoney(reservation.price) }}</p>
              <span class="status-pill" :class="reservation.statusClass">{{ reservation.statusLabel }}</span>
            </div>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend } from 'chart.js'
import { useAuthStore } from '../../../stores/auth'
import api from '../../../api'
import { Building2, Briefcase, Contact2, Map, Compass, MessageSquare, Sparkles, Star } from 'lucide-vue-next'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend)

const auth = useAuthStore()
const { t, locale } = useI18n()

const loading = ref(true)
const loadFailed = ref(false)
const dashboard = ref({
  kpis: {},
  trends: {},
  reservationsMois: [],
  recentReservations: [],
  topHotels: [],
})
const barChartKey = ref(0)
const donutChartKey = ref(0)

const colors = ['#D4820A', '#8B4513', '#EFB84D', '#A86A3B', '#f43f5e']

const displayName = computed(() => {
  const first = String(auth.user?.prenom || '').trim()
  const last = String(auth.user?.nom || '').trim()
  return `${first} ${last}`.trim() || t('overview.adminFallback')
})

const formattedAdminName = computed(() => {
  const first = String(auth.user?.prenom || '').trim()
  const firstCapitalized = first.charAt(0).toUpperCase() + first.slice(1)
  const last = String(auth.user?.nom || '').trim()
  return `${firstCapitalized} ${last}`.trim() || t('overview.adminFallback')
})

const formattedDate = computed(() =>
  new Date().toLocaleDateString(
    locale.value === 'ar' ? 'ar-DZ' : locale.value === 'en' ? 'en-US' : 'fr-FR',
    { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  )
)

const uiLocale = computed(() => (locale.value === 'ar' ? 'ar-DZ' : locale.value === 'en' ? 'en-US' : 'fr-FR'))

const kpis = computed(() => dashboard.value.kpis || {})
const trends = computed(() => dashboard.value.trends || {})

const statsCards = computed(() => [
  {
    key: 'users',
    icon: Contact2,
    badgeIcon: MessageSquare,
    label: t('overview.activeUsers'),
    value: Number(kpis.value.activeUsers || 0).toLocaleString(uiLocale.value),
    trend: asNullableNumber(trends.value.users),
    cardStyle: themeCardStyle('#4F46E5', '#7C3AED'),
  },
  {
    key: 'revenue',
    icon: Building2,
    badgeIcon: Briefcase,
    label: t('overview.revenueMonth'),
    value: formatMoney(kpis.value.monthRevenue || 0),
    trend: asNullableNumber(trends.value.revenue),
    cardStyle: themeCardStyle('#10B981', '#14B8A6'),
  },
  {
    key: 'reservations',
    icon: Map,
    badgeIcon: Compass,
    label: t('overview.reservationsMonth'),
    value: Number(kpis.value.monthReservations || 0).toLocaleString(uiLocale.value),
    trend: asNullableNumber(trends.value.reservations),
    cardStyle: themeCardStyle('#8B5CF6', '#A855F7'),
  },
  {
    key: 'hotels',
    icon: Sparkles,
    badgeIcon: Star,
    label: t('overview.totalHotels'),
    value: Number(kpis.value.totalHotels || 0).toLocaleString(uiLocale.value),
    trend: asNullableNumber(trends.value.hotels),
    cardStyle: themeCardStyle('#F59E0B', '#FB923C'),
  },
])

function themeCardStyle(startColor, endColor) {
  return {
    '--stat-accent': '#F59E0B',
    '--stat-accent-soft': 'rgba(245, 158, 11, 0.12)',
    '--stat-icon-start': startColor,
    '--stat-icon-end': endColor,
    '--stat-icon-fg': '#FFFFFF',
    '--stat-shadow': `0 14px 28px ${startColor}20`,
  }
}

const reservationsChart = computed(() => ({
  labels: (dashboard.value.reservationsMois || []).map((entry) => entry.mois),
  datasets: [
    {
      label: t('overview.reservationsByMonth'),
      data: (dashboard.value.reservationsMois || []).map((entry) => Number(entry.count || 0)),
      backgroundColor: '#D4820A',
      borderRadius: 10,
      maxBarThickness: 28,
    },
  ],
}))

const revenueChart = computed(() => ({
  labels: (dashboard.value.topHotels || []).map((hotel) => asHotelName(hotel?.nom)),
  datasets: [
    {
      data: (dashboard.value.topHotels || []).map((hotel) => Number(hotel?.revenu || 0)),
      backgroundColor: colors,
      borderWidth: 0,
      hoverOffset: 4,
    },
  ],
}))

const donutLegend = computed(() => {
  const data = revenueChart.value.datasets?.[0]?.data || []
  const labels = revenueChart.value.labels || []
  const total = data.reduce((sum, value) => sum + Number(value || 0), 0)

  return labels.map((label, idx) => ({
    label,
    color: colors[idx % colors.length],
    percent: total > 0 ? ((Number(data[idx] || 0) / total) * 100).toFixed(1) : '0.0',
  }))
})

const topHotels = computed(() => dashboard.value.topHotels || [])

const recentReservations = computed(() =>
  (dashboard.value.recentReservations || []).slice(0, 5).map((reservation, idx) => {
    const hotel = reservation?.hotel || {}
    const client = reservation?.client || reservation?.user || {}
    const statusCode = String(reservation?.statut || '').toUpperCase()

    return {
      id: reservation?._id || reservation?.id || `recent-${idx}`,
      hotelName: asHotelName(hotel?.nom),
      guestName: asGuestName(client),
      price: Number(reservation?.prixTotal ?? reservation?.prix_par_nuit ?? reservation?.total ?? 0),
      thumbnail: asPhoto(hotel?.previewPhoto),
      statusLabel: asStatusLabel(statusCode),
      statusClass: asStatusClass(statusCode),
    }
  })
)

const barOpts = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.parsed?.y ?? 0} ${t('overview.reservationsUnit')}`,
      },
    },
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#A07040' } },
    y: { beginAtZero: true, ticks: { color: '#A07040', precision: 0 }, grid: { color: 'rgba(180,110,30,0.06)' } },
  },
}

const doughnutOpts = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  cutout: '62%',
}

function asNullableNumber(value) {
  const num = Number(value)
  return Number.isFinite(num) ? num : null
}

function asHotelName(nameValue) {
  if (typeof nameValue === 'string') {
    return nameValue
  }

  if (nameValue && typeof nameValue === 'object') {
    return nameValue.fr || nameValue.en || nameValue.ar || Object.values(nameValue)[0] || t('overview.hotelFallback')
  }

  return t('overview.hotelFallback')
}

function asGuestName(client) {
  const first = String(client?.prenom || '').trim()
  const last = String(client?.nom || '').trim()
  const full = `${first} ${last}`.trim()
  return full || t('overview.guestFallback')
}

function asPhoto(value) {
  if (Array.isArray(value) && value.length > 0) {
    return String(value[0])
  }

  if (typeof value === 'string' && value.length > 0) {
    return value
  }

  return 'https://via.placeholder.com/64x64.png?text=H'
}

function asStatusLabel(statusCode) {
  if (['CONFIRMEE', 'EN_COURS', 'CHECKIN'].includes(statusCode)) {
    return t('overview.status.confirmed')
  }

  if (['ANNULEE', 'CANCELLED'].includes(statusCode)) {
    return t('overview.status.cancelled')
  }

  return t('overview.status.completed')
}

function asStatusClass(statusCode) {
  if (['CONFIRMEE', 'EN_COURS', 'CHECKIN'].includes(statusCode)) {
    return 'status-green'
  }

  if (['ANNULEE', 'CANCELLED'].includes(statusCode)) {
    return 'status-red'
  }

  return 'status-gray'
}

function normalizedPercent(value) {
  const pct = Number(value || 0)
  return Math.max(8, Math.min(100, pct))
}

function formatMoney(value) {
  return `${Number(value || 0).toLocaleString(uiLocale.value, { maximumFractionDigits: 0 })}€`
}

function mapOverviewPayload(data) {
  const source = data || {}
  const sourceKpis = source.kpis || source.stats || source

  return {
    kpis: {
      totalHotels: Number(sourceKpis.total_hotels ?? sourceKpis.totalHotels ?? sourceKpis.hotelsActifs ?? 0),
      monthReservations: Number(sourceKpis.reservations_month ?? sourceKpis.monthReservations ?? sourceKpis.reservationsMois ?? 0),
      monthRevenue: Number(sourceKpis.revenue_month ?? sourceKpis.monthRevenue ?? sourceKpis.revenusMois ?? 0),
      activeUsers: Number(sourceKpis.active_users ?? sourceKpis.activeUsers ?? sourceKpis.utilisateurs ?? 0),
    },
    trends: {
      users: sourceKpis.users_trend ?? sourceKpis.usersTrend ?? null,
      revenue: sourceKpis.revenue_trend ?? sourceKpis.revenueTrend ?? null,
      reservations: sourceKpis.reservations_trend ?? sourceKpis.reservationsTrend ?? null,
      hotels: sourceKpis.hotels_trend ?? sourceKpis.hotelsTrend ?? null,
    },
    reservationsMois: source.reservationsMois || source.reservations_mois || source.reservationsByMonth || [],
    recentReservations: source.recentReservations || source.recent_reservations || [],
    topHotels: source.topHotels || source.top_hotels || [],
  }
}

async function loadDashboard() {
  loading.value = true
  loadFailed.value = false

  try {
    let data

    try {
      const overviewResponse = await api.get('/admin/overview')
      data = overviewResponse?.data
    } catch {
      const fallbackResponse = await api.get('/admin/statistiques')
      data = fallbackResponse?.data
    }

    dashboard.value = mapOverviewPayload(data)
  } catch {
    loadFailed.value = true
    dashboard.value = mapOverviewPayload({})
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)

watchEffect(() => {
  locale.value
  barChartKey.value += 1
  donutChartKey.value += 1
})
</script>

<style scoped>
.panel-card {
  padding: 24px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: var(--bg-card);
  box-shadow: var(--card-shadow);
}

.section-title {
  margin-bottom: 12px;
  font-size: 16px;
  font-weight: 600;
  color: var(--text-primary);
}

.stat-card {
  position: relative;
  overflow: hidden;
  min-height: 330px;
  padding: 30px 28px 28px;
  border-radius: 34px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 250, 250, 0.98) 100%);
  box-shadow: var(--stat-shadow, 0 12px 28px rgba(58, 26, 4, 0.08));
  transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
}

.stat-card > * {
  position: relative;
  z-index: 1;
}

.stat-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, color-mix(in srgb, var(--stat-icon-start) 14%, transparent) 0%, transparent 52%);
  pointer-events: none;
}

.stat-card::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4px;
  background: #F59E0B;
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 38px rgba(58, 26, 4, 0.12);
  border-color: rgba(245, 158, 11, 0.28);
}

.icon-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  border-radius: 28px;
  background: linear-gradient(135deg, color-mix(in srgb, var(--stat-icon-start) 10%, #ffffff) 0%, color-mix(in srgb, var(--stat-icon-end) 10%, #ffffff) 100%);
  margin-bottom: 28px;
  box-shadow: 0 12px 26px rgba(58, 26, 4, 0.08);
}

.stat-icon-main {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  color: var(--stat-icon-start, #f59e0b);
  font-size: 64px;
  line-height: 1;
}

.stat-icon-main :deep(svg) {
  display: block;
}

.icon-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 30px;
  height: 30px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #F9DDA8;
  color: #F59E0B;
  box-shadow: 0 8px 18px rgba(245, 158, 11, 0.22);
}

.stat-value {
  margin-top: 0;
  font-size: 38px;
  line-height: 1;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: #1F2D5A;
}

.stat-label {
  margin-top: 12px;
  font-size: 17px;
  line-height: 1.35;
  color: #8B9BB8;
}

.trend-badge {
  margin-top: 28px;
  align-self: flex-start;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.01em;
}

.trend-positive {
  background: rgba(34, 197, 94, 0.12);
  color: #16A34A;
}

.trend-negative {
  background: rgba(239, 68, 68, 0.12);
  color: #DC2626;
}

.trend-arrow {
  font-size: 16px;
  line-height: 1;
}

.legend-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

.legend-name {
  flex: 1;
  color: var(--text-soft);
}

.legend-pct {
  color: var(--text-primary);
  font-weight: 600;
}

.rank-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  background: rgba(212,130,10,0.08);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 700;
}

.reservation-row {
  margin-top: 28px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px;
  background: var(--bg-card);
}

.reservation-thumb {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  object-fit: cover;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.status-green {
  background: rgba(16, 185, 129, 0.2);
  color: #6ee7b7;
}

.status-gray {
  background: rgba(148, 163, 184, 0.2);
  color: #cbd5e1;
}

.status-red {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

.empty-state {
  margin-bottom: 12px;
  font-size: 13px;
  color: var(--text-soft);
}
</style>
