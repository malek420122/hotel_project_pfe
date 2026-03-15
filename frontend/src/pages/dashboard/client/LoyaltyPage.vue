<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Programme de Fidélité</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-1">
        <div class="card text-center border-2 border-accent">
          <div class="text-6xl mb-3">{{ currentLevel.icon }}</div>
          <h3 class="text-2xl font-extrabold text-primary">Niveau {{ level }}</h3>
          <p class="text-4xl font-bold text-accent mt-2">{{ points }}</p>
          <p class="text-gray-500">points</p>
          <div class="mt-4">
            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
              <div class="h-full bg-gradient-to-r from-accent to-yellow-500 rounded-full transition-all"
                :style="`width:${progressPercent}%`"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ pointsToNext }} points pour le niveau suivant</p>
          </div>
        </div>
        <div class="card mt-4">
          <h4 class="font-bold text-gray-800 mb-3">Avantages du niveau</h4>
          <ul class="space-y-2">
            <li v-for="b in currentLevel.benefits" :key="b" class="flex items-center gap-2 text-sm text-gray-600">
              <span class="text-green-500">✓</span>{{ b }}
            </li>
          </ul>
        </div>
      </div>
      <div class="lg:col-span-2">
        <div class="card mb-4">
          <h4 class="font-bold text-gray-800 mb-4">Niveaux de fidélité</h4>
          <div class="space-y-3">
            <div v-for="lvl in levels" :key="lvl.name" :class="['flex items-center gap-4 p-3 rounded-xl border-2', level === lvl.name ? 'border-accent bg-yellow-50' : 'border-gray-100']">
              <span class="text-3xl">{{ lvl.icon }}</span>
              <div class="flex-1">
                <p class="font-bold text-gray-800">{{ lvl.name }}</p>
                <p class="text-sm text-gray-500">{{ lvl.minPts }} - {{ lvl.maxPts || '∞' }} points</p>
              </div>
              <span v-if="level === lvl.name" class="text-accent font-bold text-sm">Votre niveau</span>
            </div>
          </div>
        </div>
        <div class="card">
          <h4 class="font-bold text-gray-800 mb-4">Historique des points</h4>
          <div class="space-y-2">
            <div v-for="h in history" :key="h.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
              <div>
                <p class="text-sm font-medium text-gray-700">{{ h.description }}</p>
                <p class="text-xs text-gray-400">{{ h.date }}</p>
              </div>
              <span :class="['font-bold text-sm', h.pts > 0 ? 'text-green-600' : 'text-red-600']">
                {{ h.pts > 0 ? '+' : '' }}{{ h.pts }} pts
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
const points = ref(1250)
const level = ref('Bronze')
const levels = [
  { name: 'Bronze', icon: '🥉', minPts: 0, maxPts: 1999, benefits: ['5% remise', 'Check-in prioritaire'] },
  { name: 'Argent', icon: '🥈', minPts: 2000, maxPts: 4999, benefits: ['10% remise', 'Surclassement gratuit', 'Petit-dej offert'] },
  { name: 'Or', icon: '🥇', minPts: 5000, maxPts: null, benefits: ['20% remise', 'Surclassement automatique', 'Accès VIP lounge', 'Late checkout'] },
]
const currentLevel = computed(() => levels.find(l => l.name === level.value) || levels[0])
const nextLevel = computed(() => { const i = levels.findIndex(l => l.name === level.value); return i < levels.length - 1 ? levels[i+1] : null })
const pointsToNext = computed(() => nextLevel.value ? nextLevel.value.minPts - points.value : 0)
const progressPercent = computed(() => {
  if (!nextLevel.value) return 100
  const prev = currentLevel.value.minPts
  return Math.min(100, ((points.value - prev) / (nextLevel.value.minPts - prev)) * 100)
})
const history = [
  { id: 1, description: 'Séjour Hôtel Atlas - 3 nuits', pts: 300, date: '10 Mar 2025' },
  { id: 2, description: 'Bonus inscription', pts: 100, date: '1 Jan 2025' },
  { id: 3, description: 'Avis déposé', pts: 50, date: '15 Jan 2025' },
  { id: 4, description: 'Parrainage ami', pts: 200, date: '20 Feb 2025' },
]
</script>
