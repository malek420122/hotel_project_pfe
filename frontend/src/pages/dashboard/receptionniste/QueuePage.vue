<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('reception.queue.title') }}</h2>
    <div v-if="loadingStats" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div v-for="n in 3" :key="n" class="h-24 rounded-2xl bg-white/5 animate-pulse border border-white/8"></div>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <KpiCard icon="⏳" :label="t('reception.queue.waiting')" :value="stats.pendingReservations" color="gold" />
      <KpiCard icon="🔑" :label="t('reception.queue.checkinsToday')" :value="stats.checkinsToday" color="blue" />
      <KpiCard icon="🚪" :label="t('reception.queue.checkoutsToday')" :value="stats.checkoutsToday" color="green" />
    </div>
    <p v-if="errorMsg" class="mb-4 text-sm text-red-600">{{ errorMsg }}</p>
    <div class="card">
      <h3 class="text-[16px] font-medium text-gray-800 mb-4">{{ t('reception.queue.realtimeQueue') }}</h3>
      <div v-if="loadingQueue" class="space-y-3">
        <div v-for="n in 4" :key="`sk-${n}`" class="h-24 rounded-2xl bg-white/5 animate-pulse border border-white/8"></div>
      </div>
      <div v-else class="space-y-3">
        <div v-for="(item, i) in queue" :key="item.id"
          :class="['flex items-center gap-4 p-4 rounded-2xl transition border', i===0 ? 'queue-row queue-row-highlight' : 'queue-row']">
          <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-white text-sm', i===0 ? 'bg-amber-500' : item.type==='checkin' ? 'bg-blue-500' : 'bg-emerald-500']">
            {{ i+1 }}
          </div>
          <div class="flex-1">
            <p class="font-semibold text-gray-800">{{ item.client }}</p>
            <p class="text-sm text-gray-500">{{ item.hotel }} · {{ item.chambre }}</p>
            <p class="text-xs text-gray-400">{{ item.whenLabel }}: {{ item.formattedDate }}</p>
          </div>
          <div class="flex flex-wrap justify-end gap-2">
            <span :class="['px-2 py-1 rounded-full text-xs font-bold', item.type==='checkin' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700']">
              {{ item.type==='checkin' ? t('reception.queue.checkinLabel') : t('reception.queue.checkoutLabel') }}
            </span>
            <button @click="process(item)" class="queue-btn queue-btn-process">{{ t('reception.queue.process') }}</button>
            <button v-if="item.type === 'checkin'" @click="process(item)" class="queue-btn queue-btn-checkin">{{ t('reception.queue.checkinAction') }}</button>
            <button v-else @click="process(item)" class="queue-btn queue-btn-checkout">{{ t('reception.queue.checkoutAction') }}</button>
          </div>
        </div>
        <div v-if="!queue.length" class="text-sm text-gray-400 text-center py-4">{{ t('reception.queue.empty') }}</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import KpiCard from '../../../components/KpiCard.vue'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const { t, locale } = useI18n()

const loadingStats = ref(true)
const loadingQueue = ref(true)
const stats = ref({ pendingReservations: 0, checkinsToday: 0, checkoutsToday: 0 })
const queue = ref([])
const errorMsg = ref('')

function toIsoDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const todayLocalIso = computed(() => toIsoDate(new Date()))

async function loadQueue() {
  try {
    errorMsg.value = ''
    loadingQueue.value = true
    const [confirmedRes, inProgressRes, hotelsRes, statsRes] = await Promise.all([
      api.get('/reservations', { params: { statut: 'CONFIRMEE' } }),
      api.get('/reservations', { params: { statut: 'EN_COURS' } }),
      api.get('/hotels', { params: { per_page: 200 } }),
      api.get('/receptionniste/queue/stats'),
    ])

    const confirmed = Array.isArray(confirmedRes.data) ? confirmedRes.data : []
    const inProgress = Array.isArray(inProgressRes.data) ? inProgressRes.data : []
    const hotels = Array.isArray(hotelsRes.data?.data) ? hotelsRes.data.data : []
    const hotelMap = new Map(hotels.map((h) => [String(h._id), h.nom]))

    stats.value = {
      pendingReservations: Number(statsRes.data?.pendingReservations ?? statsRes.data?.pending_reservations ?? confirmed.length),
      checkinsToday: Number(statsRes.data?.checkinsToday ?? statsRes.data?.checkins_today ?? 0),
      checkoutsToday: Number(statsRes.data?.checkoutsToday ?? statsRes.data?.checkouts_today ?? 0),
    }

    const checkins = confirmed.map((r) => ({
      id: String(r._id || ''),
      client: [r?.client?.prenom, r?.client?.nom].filter(Boolean).join(' ') || t('reception.common.clientFallback'),
      hotel: r?.hotel?.nom || hotelMap.get(String(r.hotelId || '')) || t('reception.common.hotelFallback'),
      chambre: r?.chambre?.numero || r?.chambre?.nom || String(r?.chambreId || t('reception.common.roomFallback')),
      formattedDate: formatLocalizedDate(r?.dateArrivee, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
      dateIso: toIsoDate(r?.dateArrivee),
      whenLabel: t('reception.queue.arrivalLabel'),
      type: 'checkin',
    }))

    const checkouts = inProgress.map((r) => ({
      id: String(r._id || ''),
      client: [r?.client?.prenom, r?.client?.nom].filter(Boolean).join(' ') || t('reception.common.clientFallback'),
      hotel: r?.hotel?.nom || hotelMap.get(String(r.hotelId || '')) || t('reception.common.hotelFallback'),
      chambre: r?.chambre?.numero || r?.chambre?.nom || String(r?.chambreId || t('reception.common.roomFallback')),
      formattedDate: formatLocalizedDate(r?.dateDepart, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
      dateIso: toIsoDate(r?.dateDepart),
      whenLabel: t('reception.queue.departureLabel'),
      type: 'checkout',
    }))

    queue.value = [...checkins, ...checkouts]
  } catch {
    queue.value = []
    errorMsg.value = t('reception.queue.loadError')
  } finally {
    loadingQueue.value = false
    loadingStats.value = false
  }
}

async function process(item) {
  try {
    errorMsg.value = ''
    if (item.type === 'checkin') {
      await api.put(`/reservations/${encodeURIComponent(item.id)}/checkin`)
    } else {
      await api.put(`/reservations/${encodeURIComponent(item.id)}/checkout`)
    }
    await loadQueue()
  } catch {
    errorMsg.value = t('reception.queue.processError', { name: item.client })
  }
}

onMounted(loadQueue)
</script>

<style scoped>
.queue-row {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 255, 255, 0.08);
}

.queue-row-highlight {
  border-left: 3px solid #f59e0b;
}

.queue-btn {
  padding: 0.55rem 0.8rem;
  border-radius: 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.queue-btn-process {
  background: rgba(245, 158, 11, 0.18);
  color: #f59e0b;
}

.queue-btn-checkin {
  background: rgba(59, 130, 246, 0.18);
  color: #60a5fa;
}

.queue-btn-checkout {
  background: rgba(16, 185, 129, 0.18);
  color: #34d399;
}
</style>
