<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('dashboard.loyaltyProgram') }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
      <KpiCard :icon="Medal" :label="t('dashboard.membersBronze')" :value="kpis.Bronze ?? 0" color="blue" />
      <KpiCard :icon="Medal" :label="t('dashboard.membersSilver')" :value="kpis.Argent ?? 0" color="green" />
      <KpiCard :icon="Medal" :label="t('dashboard.membersGold')" :value="kpis.Or ?? 0" color="gold" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ t('dashboard.levelDistribution') }}</h3>
        <Doughnut :data="chartData" :options="opts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.top_loyal_members') }}</h3>
        <div class="space-y-3">
          <div v-for="(m,i) in topMembers" :key="m.name" class="flex items-center gap-3">
            <span class="text-xl w-6">{{ ['🥇','🥈','🥉','4️⃣','5️⃣'][i] }}</span>
            <div class="w-9 h-9 bg-secondary rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">{{ m.name[0] }}</div>
            <div class="flex-1">
              <p class="font-semibold text-gray-800 text-sm">{{ m.name }}</p>
              <p class="text-xs text-gray-500">{{ m.pts }} {{ t('dashboard.points') }} · {{ m.sejours }} {{ t('dashboard.stays') }}</p>
            </div>
            <span :class="['text-xs font-bold px-2 py-1 rounded-full', m.level === 'Or' ? 'bg-yellow-100 text-yellow-700' : m.level === 'Argent' ? 'bg-gray-100 text-gray-600' : 'bg-orange-100 text-orange-600']">{{ m.level }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import api from '../../../api'
import KpiCard from '../../../components/KpiCard.vue'
import { Medal } from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend)

const { t } = useI18n()

const analytics = ref({ kpis: {}, levels: [], topMembers: [] })
const kpis = computed(() => analytics.value?.kpis || {})
const topMembers = computed(() => analytics.value?.topMembers || [])
const chartData = computed(() => ({
  labels: ['Bronze', 'Argent', 'Or'],
  datasets: [{ data: [kpis.value.Bronze || 0, kpis.value.Argent || 0, kpis.value.Or || 0], backgroundColor: ['#cd7f32', '#C0C0C0', '#FFD700'] }],
}))
const opts = { responsive: true, plugins: { legend: { position: 'bottom' } } }

async function loadAnalytics() {
  try {
    const { data } = await api.get('/marketing/loyalty')
    analytics.value = {
      kpis: data?.kpis || {},
      levels: data?.levels || [],
      topMembers: data?.topMembers || [],
    }
  } catch {
    analytics.value = { kpis: {}, levels: [], topMembers: [] }
  }
}

onMounted(loadAnalytics)
</script>
