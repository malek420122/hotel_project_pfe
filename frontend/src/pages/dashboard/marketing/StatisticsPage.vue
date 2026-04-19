<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('dashboard.statistics') }}</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.reservations_12_months') }}</h3>
        <Line :data="lineData" :options="lineOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.revenue_by_hotel') }}</h3>
        <Bar :data="barData" :options="barOpts" />
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.traffic_sources') }}</h3>
        <Doughnut :data="doughnutData" :options="doughnutOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.monthly_occupancy') }}</h3>
        <Bar :data="occData" :options="occOpts" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler } from 'chart.js'
import api from '../../../api'
ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Tooltip, Legend, Filler)

const analytics = ref({
  reservationsParMois: [],
  topHotels: [],
  promotionEfficiency: [],
})

const lineData = computed(() => ({
  labels: (analytics.value?.reservationsParMois || []).map((row) => row.label),
  datasets: [{
    label: 'Réservations',
    data: (analytics.value?.reservationsParMois || []).map((row) => Number(row.count || 0)),
    borderColor: '#0071c2',
    backgroundColor: 'rgba(0,113,194,0.1)',
    fill: true,
    tension: 0.4,
  }],
}))

const barData = computed(() => ({
  labels: (analytics.value?.topHotels || []).map((hotel) => hotel.nom),
  datasets: [{
    label: 'Revenus (€)',
    data: (analytics.value?.topHotels || []).map((hotel) => Number(hotel.revenu || 0)),
    backgroundColor: ['#003580', '#0071c2', '#FFB700', '#10b981', '#8b5cf6'],
    borderRadius: 6,
  }],
}))

const doughnutData = computed(() => ({
  labels: (analytics.value?.promotionEfficiency || []).map((promo) => promo.label),
  datasets: [{
    data: (analytics.value?.promotionEfficiency || []).map((promo) => Number(promo.score || 0)),
    backgroundColor: ['#003580', '#0071c2', '#FFB700', '#10b981', '#8b5cf6'],
  }],
}))

const occData = computed(() => ({
  labels: (analytics.value?.topHotels || []).map((hotel) => hotel.nom),
  datasets: [{
    label: 'Performance (%)',
    data: (analytics.value?.topHotels || []).map((hotel) => Number(hotel.pct || 0)),
    backgroundColor: '#10b981',
    borderRadius: 4,
  }],
}))

const lineOpts = { responsive:true, plugins:{legend:{position:'bottom'}} }
const barOpts = { responsive:true, plugins:{legend:{display:false}} }
const doughnutOpts = { responsive:true, plugins:{legend:{position:'bottom'}} }
const occOpts = { responsive:true, plugins:{legend:{display:false}}, scales:{y:{min:0,max:100}} }

async function loadStatistics() {
  try {
    const { data } = await api.get('/marketing/statistiques')
    analytics.value = {
      reservationsParMois: Array.isArray(data?.reservationsParMois) ? data.reservationsParMois : [],
      topHotels: Array.isArray(data?.topHotels) ? data.topHotels : [],
      promotionEfficiency: Array.isArray(data?.promotionEfficiency) ? data.promotionEfficiency : [],
    }
  } catch {
    analytics.value = { reservationsParMois: [], topHotels: [], promotionEfficiency: [] }
  }
}

onMounted(loadStatistics)
</script>
