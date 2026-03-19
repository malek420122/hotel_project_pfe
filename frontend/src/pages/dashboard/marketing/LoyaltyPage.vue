<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Programme de Fidélité</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
      <KpiCard icon="🥉" label="Membres Bronze" value="842" color="blue" />
      <KpiCard icon="🥈" label="Membres Argent" value="324" color="green" />
      <KpiCard icon="🥇" label="Membres Or" value="82" color="gold" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Répartition des niveaux</h3>
        <Doughnut :data="chartData" :options="opts" />
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Top membres fidèles</h3>
        <div class="space-y-3">
          <div v-for="(m,i) in topMembers" :key="m.name" class="flex items-center gap-3">
            <span class="text-xl w-6">{{ ['🥇','🥈','🥉','4️⃣','5️⃣'][i] }}</span>
            <div class="w-9 h-9 bg-secondary rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">{{ m.name[0] }}</div>
            <div class="flex-1">
              <p class="font-semibold text-gray-800 text-sm">{{ m.name }}</p>
              <p class="text-xs text-gray-500">{{ m.pts }} pts · {{ m.sejours }} séjours</p>
            </div>
            <span :class="['text-xs font-bold px-2 py-1 rounded-full', m.level==='Or' ? 'bg-yellow-100 text-yellow-700' : m.level==='Argent' ? 'bg-gray-100 text-gray-600' : 'bg-orange-100 text-orange-600']">{{ m.level }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
ChartJS.register(ArcElement, Tooltip, Legend)
import KpiCard from '../../../components/KpiCard.vue'
const chartData = { labels:['Bronze','Argent','Or'], datasets:[{ data:[842,324,82], backgroundColor:['#cd7f32','#C0C0C0','#FFD700'] }] }
const opts = { responsive:true, plugins:{legend:{position:'bottom'}} }
const topMembers = [
  { name:'Sophie Martin', pts:8420, sejours:28, level:'Or' },
  { name:'Ahmed Benali', pts:6180, sejours:21, level:'Or' },
  { name:'Laura Dupont', pts:4920, sejours:16, level:'Argent' },
  { name:'Pierre Robert', pts:3840, sejours:13, level:'Argent' },
  { name:'Maria Santos', pts:2960, sejours:10, level:'Argent' },
]
</script>
