<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('reception.checkin.title') }}</h2>
    <div class="card mb-4">
      <div v-if="loadingToday" class="space-y-3">
        <div class="h-5 w-40 rounded bg-gray-200 animate-pulse"></div>
        <div class="h-4 w-72 rounded bg-gray-200 animate-pulse"></div>
      </div>
      <div v-else class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h3 class="text-[16px] font-medium text-gray-800">{{ t('reception.checkin.summaryTitle') }}</h3>
          <p class="text-sm text-gray-500">{{ summaryLabel }}</p>
        </div>
        <div class="summary-pill">
          {{ summary.expected }} {{ t('reception.checkin.expected') }} · {{ summary.processed }} {{ t('reception.checkin.processed') }}
        </div>
      </div>
    </div>
    <div class="card mb-6">
      <h3 class="text-[16px] font-medium text-gray-800 mb-4">{{ t('reception.checkin.searchTitle') }}</h3>
      <p v-if="errorMsg" class="mb-3 text-sm text-red-600">{{ errorMsg }}</p>
      <div class="flex gap-3">
        <input v-model="search" :placeholder="t('reception.checkin.searchPlaceholder')" class="input-field flex-1" @keyup.enter="searchReservation" />
        <button @click="searchReservation" class="btn-primary">{{ t('dashboard.btn_search') }}</button>
      </div>
    </div>
    <div v-if="found" class="card border-2 border-secondary mb-6">
      <h3 class="text-[16px] font-medium text-secondary mb-4">{{ t('reception.checkin.reservationFound') }}</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.client') }}</p><p class="font-bold">{{ found.client }}</p></div>
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.hotelRoom') }}</p><p class="font-bold">{{ found.hotel }} · {{ found.chambre }}</p></div>
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.arrivalDeparture') }}</p><p class="font-bold">{{ found.arrivee }} → {{ found.depart }}</p></div>
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.travelers') }}</p><p class="font-bold">{{ found.nbVoyageurs }}</p></div>
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.amount') }}</p><p class="font-bold text-secondary">{{ found.prix }}€</p></div>
        <div><p class="text-xs text-gray-500">{{ t('reception.checkin.paymentStatus') }}</p><StatusBadge :status="found.paiement" /></div>
      </div>
      <div v-if="found.demandes" class="bg-yellow-50 rounded-xl p-3 mb-4">
        <p class="text-sm font-semibold text-yellow-800">📋 {{ t('reception.checkin.specialRequests') }}</p>
        <p class="text-sm text-yellow-700">{{ found.demandes }}</p>
      </div>
      <button @click="doCheckIn" class="btn-primary">🔑 {{ t('reception.checkin.performCheckIn') }}</button>
    </div>
    <div class="card">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-[16px] font-medium text-gray-800">{{ t('reception.checkin.todaySection') }}</h3>
        <span class="text-sm text-gray-500">{{ todayList.length }} {{ t('reception.checkin.expected') }}</span>
      </div>
      <div v-if="loadingToday" class="space-y-2">
        <div v-for="n in 3" :key="n" class="h-20 rounded-2xl bg-white/5 animate-pulse"></div>
      </div>
      <div v-else-if="!todayList.length" class="flex flex-col items-center justify-center py-10 text-center text-gray-400">
        <div class="text-3xl mb-2">📭</div>
        <p>{{ t('reception.checkin.noToday') }}</p>
      </div>
      <div v-else class="space-y-2">
        <div v-for="r in todayList" :key="r.ref" class="checkin-row">
          <div class="avatar">{{ r.initials }}</div>
          <div class="min-w-0 flex-1">
            <p class="font-medium text-gray-800">{{ r.client }}</p>
            <p class="text-sm text-gray-500 truncate">{{ r.hotel }} · {{ r.chambre }} · {{ r.arrivee }}</p>
            <p class="text-xs text-gray-400">{{ t('reception.checkin.reference') }}: {{ r.ref }}</p>
          </div>
          <div class="flex items-center gap-3">
            <StatusBadge :status="r.statut" />
            <button v-if="r.statut !== 'EN_COURS'" @click="found=r" class="btn-success text-xs py-1.5 px-3">{{ t('reception.checkin.performCheckIn') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const { t, locale } = useI18n()
const search = ref('')
const found = ref(null)
const todayList = ref([])
const errorMsg = ref('')
const loadingToday = ref(true)

function toIsoDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function initialsOf(name) {
  return String(name || '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('') || 'C'
}

const summary = computed(() => ({
  expected: todayList.value.length,
  processed: todayList.value.filter((row) => row.statut === 'EN_COURS').length,
}))

const summaryLabel = computed(() => `${summary.value.expected} ${t('reception.checkin.expectedToday')} · ${summary.value.processed} ${t('reception.checkin.processedToday')}`)

async function loadCheckIns() {
  try {
    errorMsg.value = ''
    loadingToday.value = true
    const { data } = await api.get('/receptionniste/checkin/today')
    const rows = Array.isArray(data) ? data : []

    todayList.value = rows.map(mapReservation)
  } catch {
    todayList.value = []
    errorMsg.value = t('reception.checkin.loadError')
  } finally {
    loadingToday.value = false
  }
}

async function searchReservation() {
  const q = search.value.trim().toLowerCase()
  if (!q) return

  try {
    errorMsg.value = ''
    const { data } = await api.get('/receptionniste/checkin/search', { params: { q } })
    const rows = Array.isArray(data) ? data : []
    found.value = rows[0] ? mapReservation(rows[0]) : null
    if (!found.value) {
      errorMsg.value = t('reception.checkin.noResult')
    }
  } catch {
    found.value = null
    errorMsg.value = t('reception.checkin.noResult')
  }
}

function mapReservation(r) {
  const fullName = [r?.client?.prenom, r?.client?.nom].filter(Boolean).join(' ')

  return {
    ref: String(r?._id || ''),
    client: fullName || t('reception.common.clientFallback'),
    initials: initialsOf(fullName || t('reception.common.clientFallback')),
    chambre: r?.chambre?.numero || r?.chambre?.nom || String(r?.chambreId || t('reception.common.roomFallback')),
    statut: r?.statut || 'CONFIRMEE',
    hotel: r?.hotel?.nom || t('reception.common.hotelFallback'),
    arrivee: formatLocalizedDate(r?.dateArrivee, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
    depart: formatLocalizedDate(r?.dateDepart, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
    nbVoyageurs: Number(r?.nbVoyageurs || 1),
    prix: Number(r?.prixTotal || 0),
    paiement: r?.paiement?.statut || 'INCONNU',
    demandes: r?.demandesSpeciales || '',
  }
}
async function doCheckIn() {
  if (!found.value?.ref) return
  try {
    errorMsg.value = ''
    await api.put(`/reservations/${encodeURIComponent(found.value.ref)}/checkin`)
    found.value = null
    await loadCheckIns()
  } catch {
    errorMsg.value = t('reception.checkin.checkInError')
  }
}

onMounted(loadCheckIns)
</script>

<style scoped>
.summary-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.55rem 0.9rem;
  border-radius: 999px;
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
  font-weight: 700;
  font-size: 0.8rem;
}

.checkin-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem;
  border-radius: 1rem;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.96);
}

.avatar {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
  color: white;
  font-weight: 800;
  flex-shrink: 0;
}

.btn-success {
  background: rgba(16, 185, 129, 0.14);
  color: #059669;
  border-radius: 0.7rem;
  font-weight: 700;
}
</style>
