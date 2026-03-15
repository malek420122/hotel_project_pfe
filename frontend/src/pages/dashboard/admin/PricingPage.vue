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
          <button type="submit" class="btn-primary">Enregistrer la configuration</button>
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
            <input v-model.number="sim.occupation" type="range" min="0" max="100" class="w-full accent-secondary" />
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
          <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-sm text-gray-500">Prix calculé</p>
            <p class="text-4xl font-extrabold text-secondary">{{ calculatedPrice }}€</p>
            <p class="text-xs text-gray-400">/nuit</p>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Évolution des prix (30 derniers jours)</h3>
      <Line :data="priceChart" :options="lineOpts" />
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed } from 'vue'
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler } from 'chart.js'
ChartJS.register(LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, Filler)

const config = reactive({ majHauteSaison: 30, remiseBasseSaison: 15, seuilOccupation: 80, majOccupation: 20 })
const sim = reactive({ base: 150, occupation: 60, saison: 'normale' })

const calculatedPrice = computed(() => {
  let p = sim.base
  if (sim.saison === 'haute') p *= (1 + config.majHauteSaison / 100)
  if (sim.saison === 'basse') p *= (1 - config.remiseBasseSaison / 100)
  if (sim.occupation >= config.seuilOccupation) p *= (1 + config.majOccupation / 100)
  return Math.round(p)
})

function saveConfig() { alert('Configuration sauvegardée !') }

const days = Array.from({ length: 30 }, (_, i) => `${i+1}/03`)
const priceChart = {
  labels: days,
  datasets: [{ label: 'Prix moyen (€)', data: days.map((_, i) => 120 + Math.sin(i / 4) * 30 + Math.random() * 20), borderColor: '#0071c2', backgroundColor: 'rgba(0,113,194,0.1)', fill: true, tension: 0.4 }]
}
const lineOpts = { responsive: true, plugins: { legend: { position: 'bottom' } } }
</script>
