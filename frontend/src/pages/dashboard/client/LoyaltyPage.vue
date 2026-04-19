<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('dashboard.loyaltyProgram') }}</h2>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-1">
        <div class="card text-center border-2 border-accent">
          <div class="text-6xl mb-3">{{ currentLevel.icon }}</div>
          <h3 class="text-2xl font-extrabold text-primary">{{ t('loyalty.levelPrefix') }} {{ level }}</h3>
          <p class="text-4xl font-bold text-accent mt-2">{{ points }}</p>
          <p class="text-gray-500">{{ t('dashboard.points') }}</p>
          <div class="mt-4">
            <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
              <div :class="['h-full bg-gradient-to-r from-accent to-yellow-500 rounded-full transition-all', toPctClass(progressPercent)]"></div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ t('loyalty.pointsToNext', { points: pointsToNext }) }}</p>
          </div>
        </div>
        <div class="card mt-4">
          <h4 class="font-bold text-gray-800 mb-3">{{ t('loyalty.levelBenefits') }}</h4>
          <ul class="space-y-2">
            <li v-for="b in currentLevel.benefits" :key="b" class="flex items-center gap-2 text-sm text-gray-600">
              <span class="text-green-500">✓</span>{{ b }}
            </li>
          </ul>
        </div>
      </div>
      <div class="lg:col-span-2">
        <div class="card mb-4">
          <h4 class="font-bold text-gray-800 mb-4">{{ t('loyalty.levelsTitle') }}</h4>
          <div class="space-y-3">
            <div v-for="lvl in levels" :key="lvl.name" :class="['flex items-center gap-4 p-3 rounded-xl border-2', level === lvl.name ? 'border-accent bg-yellow-50' : 'border-gray-100']">
              <span class="text-3xl">{{ lvl.icon }}</span>
              <div class="flex-1">
                <p class="font-bold text-gray-800">{{ lvl.name }}</p>
                <p class="text-sm text-gray-500">{{ t('loyalty.pointsRange', { min: lvl.minPts, max: lvl.maxPts || '∞' }) }}</p>
              </div>
              <span v-if="level === lvl.name" class="text-accent font-bold text-sm">{{ t('loyalty.yourLevel') }}</span>
            </div>
          </div>
        </div>
        <div class="card">
          <h4 class="font-bold text-gray-800 mb-4">{{ t('loyalty.historyTitle') }}</h4>
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

        <div class="card mt-4">
          <h4 class="font-bold text-gray-800 mb-4">{{ t('loyalty.rewardsTitle') }}</h4>
          <div class="space-y-3">
            <div v-for="reward in rewards" :key="reward.key" class="flex items-center justify-between rounded-xl border border-gray-100 p-3">
              <div>
                <p class="font-semibold text-gray-800">{{ reward.label }}</p>
                <p class="text-xs text-gray-500">{{ reward.points }} points</p>
              </div>
              <button
                class="btn-primary text-sm"
                :disabled="points < reward.points || redeeming"
                @click="redeem(reward)"
              >
                {{ t('loyalty.usePoints') }}
              </button>
            </div>
          </div>
          <p v-if="redeemMessage" :class="['text-sm mt-3', redeemMessage.ok ? 'text-green-600' : 'text-red-600']">
            {{ redeemMessage.text }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'

const { t } = useI18n()

const points = ref(0)
const level = ref('Bronze')
const levels = ref([])
const currentLevel = computed(() => levels.value.find(l => l.name === level.value) || { icon: '⭐', benefits: [] })
const nextLevel = computed(() => {
  const i = levels.value.findIndex(l => l.name === level.value)
  return i < levels.value.length - 1 ? levels.value[i + 1] : null
})
const pointsToNext = computed(() => nextLevel.value ? nextLevel.value.minPts - points.value : 0)
const progressPercent = computed(() => {
  if (!nextLevel.value) return 100
  const prev = currentLevel.value.minPts
  return Math.min(100, ((points.value - prev) / (nextLevel.value.minPts - prev)) * 100)
})

function toPctClass(value) {
  const normalized = Math.max(0, Math.min(100, Math.round((Number(value || 0) / 5)) * 5))
  return `w-pct-${normalized}`
}
const history = ref([])
const redeeming = ref(false)
const redeemMessage = ref(null)

const rewards = [
  { key: 'discount10', label: t('loyalty.rewardDiscount'), points: 500 },
  { key: 'free_breakfast', label: t('loyalty.rewardBreakfast'), points: 1000 },
  { key: 'free_upgrade', label: t('loyalty.rewardUpgrade'), points: 2000 },
]

async function loadLoyalty() {
  try {
    const { data } = await api.get('/client/loyalty')

    levels.value = Array.isArray(data?.niveaux) ? data.niveaux : []

    points.value = Number(data?.points ?? data?.points_fidelite ?? 0)

    const niveauRaw = String(data?.niveau ?? data?.niveau_fidelite ?? 'Bronze')
    const niveau = niveauRaw.charAt(0).toUpperCase() + niveauRaw.slice(1).toLowerCase()
    level.value = levels.value.some((l) => l.name === niveau) ? niveau : (levels.value[0]?.name || 'Bronze')

    history.value = Array.isArray(data?.historique) ? data.historique.slice(0, 8).map((r) => ({
      id: String(r._id || ''),
      description: `${t('loyalty.stay')} ${r.reference}`,
      pts: Number(r.points || 0),
      date: r.date || '-',
    })) : []
  } catch {
    history.value = []
  }
}

async function redeem(reward) {
  try {
    redeeming.value = true
    redeemMessage.value = null
    const { data } = await api.post('/client/loyalty/redeem', { reward: reward.key })
    redeemMessage.value = { ok: true, text: t('loyalty.promoGenerated', { code: data.codePromo }) }
    await loadLoyalty()
  } catch (e) {
    redeemMessage.value = { ok: false, text: e.response?.data?.error || t('loyalty.redeemError') }
  } finally {
    redeeming.value = false
  }
}

onMounted(loadLoyalty)
</script>
