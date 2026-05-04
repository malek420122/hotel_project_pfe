<template>
  <section class="marketing-page">
    <header class="marketing-head">
      <div>
        <h2 class="marketing-title">{{ t('dashboard.marketing_overview') }}</h2>
        <p class="marketing-subtitle">{{ t('marketing.subtitle') }}</p>
      </div>
    </header>

    <div class="marketing-grid-kpi">
      <article class="premium-card kpi-card">
        <p class="kpi-label">{{ t('marketing.conversionRate') }}</p>
        <p class="kpi-value">{{ Number(kpis.tauxConversion || 0).toFixed(1) }}%</p>
      </article>
      <article class="premium-card kpi-card">
        <p class="kpi-label">{{ t('marketing.avgRating') }}</p>
        <p class="kpi-value">{{ Number(kpis.noteMoyenne || 0).toFixed(1) }}/5</p>
      </article>
      <article class="premium-card kpi-card">
        <p class="kpi-label">{{ t('marketing.promoUsed') }}</p>
        <p class="kpi-value">{{ Number(kpis.codesPromoUtilises || 0) }}</p>
      </article>
      <article class="premium-card kpi-card">
        <p class="kpi-label">{{ t('marketing.activePromos') }}</p>
        <p class="kpi-value">{{ Number(kpis.promotionsActives || 0) }}</p>
      </article>
    </div>

    <div class="marketing-grid-charts">
      <article class="premium-card">
        <h3 class="chart-title">{{ t('marketing.topHotelsRevenue') }}</h3>
        <div class="chart-wrap"><Bar :data="hotelChart" :options="hotelChartOpts" :plugins="chartPlugins" /></div>
      </article>

      <article class="premium-card">
        <h3 class="chart-title">{{ t('marketing.promoEfficiency') }}</h3>
        <div class="chart-wrap"><Bar :data="promotionChart" :options="promotionChartOpts" /></div>
      </article>
    </div>

    <div class="marketing-grid-extra">
      <article class="premium-card stat-chip">
        <p class="chip-label">{{ t('marketing.reviewsThisWeek') }}</p>
        <p class="chip-value">{{ extraStats.reviewsThisWeek ?? 0 }}</p>
      </article>
      <article class="premium-card stat-chip">
        <p class="chip-label">{{ t('marketing.responseRate') }}</p>
        <p class="chip-value">{{ Number(extraStats.responseRate || 0).toFixed(1) }}%</p>
      </article>
      <article class="premium-card stat-chip">
        <p class="chip-label">{{ t('marketing.mostFrequentRating') }}</p>
        <p class="chip-value">{{ ratingStars(extraStats.modeRating) }}</p>
      </article>
      <article class="premium-card stat-chip">
        <p class="chip-label">{{ t('marketing.bestRatedHotel') }}</p>
        <p class="chip-value">{{ extraStats.bestRatedHotel || '—' }}</p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js'
import api from '../../../api'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const { t, locale } = useI18n()

const analytics = ref({ kpis: {}, topHotels: [], promotionEfficiency: [], extraStats: {} })

const formatEuro = (value) => {
  const amount = Number(value || 0)
  return `${amount.toLocaleString(locale.value === 'fr' ? 'fr-FR' : locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US', {
    maximumFractionDigits: 0,
  })}€`
}

const chartPlugins = [
  {
    id: 'hotel-count-label',
    afterDatasetsDraw(chart) {
      const { ctx } = chart
      const datasetMeta = chart.getDatasetMeta(0)
      const counts = (analytics.value?.topHotels || []).map((item) => item.reservationsCount || 0)

      ctx.save()
      ctx.fillStyle = '#A07040'
      ctx.font = '600 11px Inter, sans-serif'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'bottom'

      datasetMeta.data.forEach((bar, index) => {
        ctx.fillText(`${counts[index] || 0}`, bar.x, bar.y - 4)
      })
      ctx.restore()
    },
  },
]

const kpis = computed(() => analytics.value?.kpis || {})
const extraStats = computed(() => analytics.value?.extraStats || {})

const hotelChart = computed(() => ({
  labels: (analytics.value?.topHotels || []).map((hotel) => hotel.nom),
  datasets: [{
    label: t('marketing.revenueLabel'),
    data: (analytics.value?.topHotels || []).map((hotel) => Number(hotel.revenu || 0)),
    backgroundColor: '#D4820A',
    borderRadius: 10,
    maxBarThickness: 44,
  }],
}))

const promotionChart = computed(() => ({
  labels: (analytics.value?.promotionEfficiency || []).map((promo) => promo.label),
  datasets: [{
    label: t('marketing.promoUsed'),
    data: (analytics.value?.promotionEfficiency || []).map((promo) => Number(promo.used ?? promo.score ?? 0)),
    backgroundColor: '#f59e0b',
    borderRadius: 10,
    maxBarThickness: 42,
  }],
}))

const hotelChartOpts = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => `${t('marketing.revenueLabel')}: ${formatEuro(context.parsed.y)}`,
      },
    },
  },
  scales: {
    x: { ticks: { color: '#A07040' }, grid: { color: 'rgba(180,110,30,0.06)' } },
    y: {
      ticks: {
        color: '#A07040',
        callback: (value) => formatEuro(value),
      },
      grid: { color: 'rgba(180,110,30,0.06)' },
    },
  },
}))

const promotionChartOpts = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => `${t('marketing.promoUsed')}: ${Number(context.parsed.y || 0)}`,
      },
    },
  },
  scales: {
    x: { ticks: { color: '#A07040' }, grid: { color: 'rgba(180,110,30,0.06)' } },
    y: { ticks: { color: '#A07040' }, grid: { color: 'rgba(180,110,30,0.06)' } },
  },
}))

function ratingStars(note) {
  const safe = Math.max(0, Math.min(5, Number(note || 0)))
  return `${'★'.repeat(safe)}${'☆'.repeat(5 - safe)}`
}

async function loadAnalytics() {
  try {
    const [overviewRes, statsRes] = await Promise.all([
      api.get('/marketing/statistiques'),
      api.get('/marketing/stats'),
    ])

    const over = overviewRes.data || {}
    const stats = statsRes.data || {}

    analytics.value = {
      kpis: {
        promotionsActives: Number(stats.promotions_actives ?? over?.kpis?.promotionsActives ?? 0),
        codesPromoUtilises: Number(stats.codes_promo_utilises ?? over?.kpis?.codesPromoUtilises ?? 0),
        noteMoyenne: Number(stats.note_moyenne ?? over?.kpis?.noteMoyenne ?? 0),
        tauxConversion: Number(stats.taux_conversion ?? over?.kpis?.tauxConversion ?? 0),
      },
      topHotels: Array.isArray(over.topHotels) ? over.topHotels : [],
      promotionEfficiency: Array.isArray(over.promotionEfficiency) ? over.promotionEfficiency : [],
      extraStats: over.extraStats || {},
    }
  } catch {
    analytics.value = { kpis: {}, topHotels: [], promotionEfficiency: [], extraStats: {} }
  }
}

onMounted(loadAnalytics)
</script>

<style scoped>
.marketing-page {
  --bg-card: #FFFFFF;
  --text-main: #3A1A04;
  --text-soft: #A07040;
}

.marketing-head {
  margin-bottom: 1.2rem;
}

.marketing-title {
  color: var(--text-main);
  font-size: 1.6rem;
  font-weight: 800;
}

.marketing-subtitle {
  color: var(--text-soft);
  margin-top: 0.3rem;
  font-size: 0.92rem;
}

.marketing-grid-kpi,
.marketing-grid-extra {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 0.9rem;
  margin-bottom: 1rem;
}

.marketing-grid-charts {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 0.9rem;
  margin-bottom: 1rem;
}

@media (min-width: 768px) {
  .marketing-grid-kpi,
  .marketing-grid-extra {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1200px) {
  .marketing-grid-kpi,
  .marketing-grid-extra {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  .marketing-grid-charts {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.premium-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 20px;
  color: var(--text-main);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.premium-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(58,26,4,0.06);
}

.kpi-label,
.chip-label {
  color: var(--text-soft);
  font-size: 0.82rem;
  margin-bottom: 0.45rem;
}

.kpi-value,
.chip-value {
  font-size: 1.5rem;
  font-weight: 800;
}

.chart-title {
  color: var(--text-main);
  font-weight: 700;
  margin-bottom: 0.7rem;
}

.chart-wrap {
  height: 290px;
}
</style>
