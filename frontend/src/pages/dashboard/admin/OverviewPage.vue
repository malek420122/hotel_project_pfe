<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Vue d'ensemble</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <KpiCard icon="🏨" label="Total Hôtels" value="12" color="blue" :trend="8" />
      <KpiCard icon="📅" label="Réservations (mois)" value="384" color="green" :trend="15" />
      <KpiCard icon="💰" label="Revenus (mois)" value="87,450€" color="gold" :trend="12" />
      <KpiCard icon="👥" label="Utilisateurs actifs" value="1,248" color="purple" :trend="-3" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Réservations par mois</h3>
        <Bar :data="bookingsChart" :options="chartOpts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Répartition revenus</h3>
        <Doughnut :data="revenueChart" :options="doughnutOpts" />
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Réservations récentes</h3>
        <div class="space-y-2">
          <div v-for="r in recentReservations" :key="r.ref" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div>
              <p class="text-sm font-medium text-gray-700">{{ r.client }} — {{ r.hotel }}</p>
              <p class="text-xs text-gray-400">{{ r.date }}</p>
            </div>
            <div class="text-right">
              <p class="font-bold text-secondary text-sm">{{ r.prix }}</p>
              <StatusBadge :status="r.statut" />
            </div>
          </div>
        </div>
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Meilleurs hôtels</h3>
        <div class="space-y-3">
          <div v-for="(h, i) in topHotels" :key="h.nom" class="flex items-center gap-3">
            <span class="text-lg font-bold w-6">{{ i+1 }}</span>
            <div class="flex-1">
              <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">{{ h.nom }}</span>
                <span class="text-sm font-bold text-secondary">{{ h.revenu }}</span>
              </div>
              <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div :style="`width:${h.pct}%`" class="h-full bg-secondary rounded-full"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend, Title } from 'chart.js'
ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend, Title)
import KpiCard from '../../../components/KpiCard.vue'
import StatusBadge from '../../../components/StatusBadge.vue'

const bookingsChart = {
  labels: ['Oct','Nov','Dec','Jan','Feb','Mar'],
  datasets: [{ label: 'Réservations', data: [210,258,310,280,330,384], backgroundColor: '#0071c2', borderRadius: 6 }]
}
const revenueChart = {
  labels: ['Hôtels','Services','Spa','Navette'],
  datasets: [{ data: [65,15,12,8], backgroundColor: ['#003580','#0071c2','#FFB700','#10b981'] }]
}
const chartOpts = { responsive: true, plugins: { legend: { display: false } } }
const doughnutOpts = { responsive: true, plugins: { legend: { position: 'bottom' } } }
const recentReservations = [
  { ref:'R001', client:'Sophie Martin', hotel:'Atlas', date:'12 Mar', prix:'450€', statut:'CONFIRMEE' },
  { ref:'R002', client:'Ahmed Benali', hotel:'Riad', date:'13 Mar', prix:'280€', statut:'EN_ATTENTE' },
  { ref:'R003', client:'Laura Dupont', hotel:'Ibis', date:'11 Mar', prix:'190€', statut:'TERMINEE' },
]
const topHotels = [
  { nom: 'Hôtel Atlas ⭐⭐⭐⭐⭐', revenu:'28,400€', pct:90 },
  { nom: 'Riad Al Baraka ⭐⭐⭐⭐', revenu:'21,200€', pct:67 },
  { nom: 'Ibis Marrakech ⭐⭐⭐', revenu:'18,800€', pct:60 },
]
</script>
