<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('dashboard.marketing_overview') }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <KpiCard icon="🎯" label="Promotions actives" value="5" color="purple" />
      <KpiCard icon="🏷️" label="Codes promo utilisés" value="142" color="blue" :trend="23" />
      <KpiCard icon="⭐" label="Note moyenne" value="4.6" color="gold" />
      <KpiCard icon="🔄" label="Taux conversion" value="12.4%" color="green" :trend="5" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.conversions_channel') }}</h3>
        <Bar :data="convChart" :options="chartOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.promotions_efficiency') }}</h3>
        <Radar :data="radarChart" :options="radarOpts" />
      </div>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.recent_reviews') }}</h3>
      <div class="space-y-3">
        <div v-for="avis in recentAvis" :key="avis.id" class="flex items-start gap-3 border-b pb-3 last:border-0">
          <div class="w-9 h-9 bg-secondary rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">{{ avis.client[0] }}</div>
          <div class="flex-1">
            <div class="flex justify-between">
              <p class="font-semibold text-sm text-gray-800">{{ avis.client }}</p>
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" :class="i <= avis.note ? 'text-accent' : 'text-gray-200'" class="text-sm">★</span>
              </div>
            </div>
            <p class="text-gray-500 text-sm">{{ avis.hotel }}</p>
            <p class="text-sm text-gray-700 mt-1 italic">"{{ avis.comment }}"</p>
          </div>
          <StatusBadge :status="avis.statut" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { Bar, Radar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, RadialLinearScale, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler } from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, BarElement, RadialLinearScale, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler)
import KpiCard from '../../../components/KpiCard.vue'
import StatusBadge from '../../../components/StatusBadge.vue'

const convChart = {
  labels: ['Email','SEO','Réseaux sociaux','Partenaires','Direct'],
  datasets: [{ label: 'Conversions', data: [42, 35, 28, 20, 17], backgroundColor: '#0071c2', borderRadius: 6 }]
}
const radarChart = {
  labels: ['Clics', 'Conversions', 'Revenus', 'Satisfaction', 'ROI'],
  datasets: [
    { label: 'Promo Été', data: [80, 65, 75, 90, 70], borderColor: '#003580', backgroundColor: 'rgba(0,53,128,0.1)' },
    { label: 'Promo Ramadan', data: [60, 80, 85, 95, 88], borderColor: '#FFB700', backgroundColor: 'rgba(255,183,0,0.1)' },
  ]
}
const chartOpts = { responsive: true, plugins: { legend: { display: false } } }
const radarOpts = { responsive: true, plugins: { legend: { position: 'bottom' } } }
const recentAvis = [
  { id:1, client:'Sophie M.', hotel:'Atlas', note:5, comment:'Service 5 étoiles !', statut:'PUBLIE' },
  { id:2, client:'Ahmed B.', hotel:'Riad', note:4, comment:'Très bonne expérience globale.', statut:'EN_ATTENTE' },
  { id:3, client:'Laura D.', hotel:'Ibis', note:3, comment:'Chambre propre mais bruyante.', statut:'EN_ATTENTE' },
]
</script>
