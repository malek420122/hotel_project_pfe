<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">File d'attente</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <KpiCard icon="⏳" label="En attente" :value="queue.filter(r=>r.type==='checkin').length" color="gold" />
      <KpiCard icon="🔑" label="Check-in aujourd'hui" :value="todayCheckins" color="blue" />
      <KpiCard icon="🚪" label="Check-out aujourd'hui" :value="todayCheckouts" color="green" />
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">File d'attente en temps réel</h3>
      <div class="space-y-3">
        <div v-for="(item, i) in queue" :key="item.id"
          :class="['flex items-center gap-4 p-4 rounded-xl border-2', i===0 ? 'border-secondary bg-blue-50' : 'border-gray-100']">
          <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-white text-sm', i===0 ? 'bg-secondary' : 'bg-gray-400']">
            {{ i+1 }}
          </div>
          <div class="flex-1">
            <p class="font-semibold text-gray-800">{{ item.client }}</p>
            <p class="text-sm text-gray-500">{{ item.hotel }} · {{ item.chambre }}</p>
            <p class="text-xs text-gray-400">Arrivée: {{ item.heure }}</p>
          </div>
          <div class="flex gap-2">
            <span :class="['px-2 py-1 rounded-full text-xs font-bold', item.type==='checkin' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700']">
              {{ item.type==='checkin' ? '🔑 Check-in' : '🚪 Check-out' }}
            </span>
            <button @click="process(item)" class="btn-primary text-xs py-1.5 px-3">Traiter</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import KpiCard from '../../../components/KpiCard.vue'
const todayCheckins = 8
const todayCheckouts = 5
const queue = ref([
  { id: 1, client: 'Sophie Martin', hotel: 'Hôtel Atlas', chambre: '201', heure: '14:00', type: 'checkin' },
  { id: 2, client: 'Ahmed Benali', hotel: 'Atlas', chambre: '305', heure: '14:30', type: 'checkin' },
  { id: 3, client: 'Laura Dupont', hotel: 'Atlas', chambre: '112', heure: '12:00', type: 'checkout' },
  { id: 4, client: 'Pierre Robert', hotel: 'Atlas', chambre: '208', heure: '15:00', type: 'checkin' },
])
function process(item) {
  queue.value = queue.value.filter(q => q.id !== item.id)
  alert(`${item.type === 'checkin' ? 'Check-in' : 'Check-out'} de ${item.client} traité !`)
}
</script>
