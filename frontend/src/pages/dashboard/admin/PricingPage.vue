<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Prix Dynamiques</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Configuration tarifaire</h3>
        <form @submit.prevent="saveConfig" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Majoration haute saison (%)</label>
            <input v-model="config.majHauteSaison" type="number" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Réduction basse saison (%)</label>
            <input v-model="config.remiseBasseSaison" type="number" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Seuil occupation (%) pour majoration</label>
            <input v-model="config.seuilOccupation" type="number" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Majoration occupation (%) au delà du seuil</label>
            <input v-model="config.majOccupation" type="number" class="input-field" />
          </div>
          <button type="submit" class="btn-primary">Rafraichir depuis les donnees reelles</button>
        </form>
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Simulation de prix</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Prix de base (€/nuit)</label>
            <input v-model.number="sim.base" type="number" class="input-field" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Taux d'occupation (%)</label>
            <input v-model.number="sim.occupation" type="range" min="0" max="100" class="w-full accent-[#D4820A]" />
            <span class="text-sm text-gray-600">{{ sim.occupation }}%</span>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Saison</label>
            <select v-model="sim.saison" class="input-field">
              <option value="basse">Basse saison</option>
              <option value="normale">Saison normale</option>
              <option value="haute">Haute saison</option>
            </select>
          </div>
          <div class="rounded-xl p-4 text-center" style="background:#FDF3DC;color:#D4820A;">
            <p class="text-sm text-gray-500">Prix calculé</p>
            <p class="text-4xl font-extrabold" style="color:#D4820A;">{{ calculatedPrice }}€</p>
            <p class="text-xs text-gray-400">/nuit</p>
          </div>
          <p class="text-xs text-gray-500">Simulation basee sur les indicateurs reels du tableau de bord admin.</p>
        </div>
      </div>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Evolution des revenus (12 derniers mois)</h3>
      <Line :data="priceChart" :options="lineOpts" />
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import api from '../../../api'
import { Chart as ChartJS, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler } from 'chart.js'
ChartJS.register(LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler)

const stats = ref({ revenusMensuel: [], topHotels: [], kpis: {} })
const config = reactive({ majHauteSaison: 0, remiseBasseSaison: 0, seuilOccupation: 0, majOccupation: 0 })
const sim = reactive({ base: 0, occupation: 0, saison: 'normale' })

async function loadStats() {
  try {
    const { data } = await api.get('/admin/statistiques')
    stats.value = {
      revenusMensuel: Array.isArray(data?.revenusMensuel) ? data.revenusMensuel : [],
      topHotels: Array.isArray(data?.topHotels) ? data.topHotels : [],
      kpis: data?.kpis || {},
    }

    const hotelPcts = stats.value.topHotels.map((h) => Number(h?.pct || 0)).filter((n) => Number.isFinite(n))
    const avgOccupation = hotelPcts.length ? Math.round(hotelPcts.reduce((a, b) => a + b, 0) / hotelPcts.length) : 0

    const monthlyTotals = stats.value.revenusMensuel
      .map((r) => Number(r?.total || 0))
      .filter((n) => Number.isFinite(n))
    const maxTotal = monthlyTotals.length ? Math.max(...monthlyTotals) : 0
    const minTotal = monthlyTotals.length ? Math.min(...monthlyTotals) : 0
    const spreadPct = maxTotal > 0 ? Math.round(((maxTotal - minTotal) / maxTotal) * 100) : 0

    config.majHauteSaison = spreadPct
    config.remiseBasseSaison = Math.round(spreadPct / 2)
    config.seuilOccupation = avgOccupation
    config.majOccupation = Math.round(avgOccupation / 4)

    const reservationsMois = Number(stats.value.kpis?.reservationsMois || 0)
    const revenusMois = Number(stats.value.kpis?.revenusMois || 0)
    const basePrice = reservationsMois > 0 ? revenusMois / reservationsMois : 0
    sim.base = Math.round(basePrice)
    sim.occupation = avgOccupation
  } catch {
    stats.value = { revenusMensuel: [], topHotels: [], kpis: {} }
    config.majHauteSaison = 0
    config.remiseBasseSaison = 0
    config.seuilOccupation = 0
    config.majOccupation = 0
    sim.base = 0
    sim.occupation = 0
  }
}

const calculatedPrice = computed(() => {
  let p = sim.base
  if (sim.saison === 'haute') p *= (1 + config.majHauteSaison / 100)
  if (sim.saison === 'basse') p *= (1 - config.remiseBasseSaison / 100)
  if (sim.occupation >= config.seuilOccupation) p *= (1 + config.majOccupation / 100)
  return Math.round(p)
})

async function saveConfig() {
  await loadStats()
}

const priceChart = computed(() => ({
  labels: stats.value.revenusMensuel.map((r) => r?.mois || ''),
  datasets: [{
    label: 'Revenu mensuel (€)',
    data: stats.value.revenusMensuel.map((r) => Number(r?.total || 0)),
    borderColor: '#D4820A',
    backgroundColor: 'rgba(0,113,194,0.1)',
    fill: true,
    tension: 0.4,
  }],
}))
const lineOpts = { responsive: true, plugins: { legend: { position: 'bottom' } } }

onMounted(loadStats)
</script>
