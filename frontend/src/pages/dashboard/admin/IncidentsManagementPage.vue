<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-gray-800">{{ t('incidents.management.title') }}</h2>
      <button class="btn-outline text-sm" @click="refreshData">{{ t('common.refresh') }}</button>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <div class="card">
        <p class="text-xs uppercase text-gray-500">{{ t('incidents.stats.total') }}</p>
        <p class="mt-1 text-2xl font-bold text-gray-800">{{ stats.total }}</p>
      </div>
      <div class="card">
        <p class="text-xs uppercase text-gray-500">{{ t('incidents.stats.open') }}</p>
        <p class="mt-1 text-2xl font-bold text-red-600">{{ stats.ouverts }}</p>
      </div>
      <div class="card">
        <p class="text-xs uppercase text-gray-500">{{ t('incidents.stats.inProgress') }}</p>
        <p class="mt-1 text-2xl font-bold text-amber-600">{{ stats.enCours }}</p>
      </div>
      <div class="card">
        <p class="text-xs uppercase text-gray-500">{{ t('incidents.status.resolved') }}</p>
        <p class="mt-1 text-2xl font-bold text-green-600">{{ stats.resolus }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
      <div class="card">
        <h3 class="text-sm font-bold text-gray-800">{{ t('incidents.stats.avgResolution') }}</h3>
        <div class="mt-3 grid grid-cols-3 gap-2 text-sm">
          <div v-for="level in ['basse', 'moyenne', 'haute']" :key="level" class="rounded-xl bg-gray-50 p-3">
            <p class="text-xs font-semibold text-gray-500">{{ severityLabel(level) }}</p>
            <p class="mt-1 font-bold text-gray-800">{{ resolutionText(stats.avgResolutionBySeverity[level]) }}</p>
          </div>
        </div>
      </div>
      <div class="card">
        <h3 class="text-sm font-bold text-gray-800">{{ t('incidents.stats.last30Days') }}</h3>
        <div class="mt-3 flex h-20 items-end gap-1">
          <div
            v-for="day in stats.byDay"
            :key="day.date"
            class="flex-1 rounded-t bg-orange-300"
            :title="`${day.date}: ${day.count}`"
            :style="{ height: `${dayHeight(day.count)}%` }"
          ></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <select v-model="filters.status" class="rounded-xl border border-gray-300 px-3 py-2" @change="fetchIncidents">
          <option value="">{{ t('incidents.filters.allStatus') }}</option>
          <option value="ouvert">{{ t('incidents.status.open') }}</option>
          <option value="en_cours">{{ t('incidents.status.inProgress') }}</option>
          <option value="résolu">{{ t('incidents.status.resolved') }}</option>
        </select>

        <select v-model="filters.hotelId" class="rounded-xl border border-gray-300 px-3 py-2" @change="fetchIncidents">
          <option value="">{{ t('incidents.filters.allHotels') }}</option>
          <option v-for="hotel in hotels" :key="hotel._id" :value="hotel._id">{{ hotel.nom }}</option>
        </select>

        <select v-model="filters.type" class="rounded-xl border border-gray-300 px-3 py-2" @change="fetchIncidents">
          <option value="">{{ t('incidents.filters.allTypes') }}</option>
          <option v-for="item in typeOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
        </select>

        <select v-model="filters.severity" class="rounded-xl border border-gray-300 px-3 py-2" @change="fetchIncidents">
          <option value="">{{ t('incidents.filters.allSeverity') }}</option>
          <option value="basse">{{ t('incidents.severity.low') }}</option>
          <option value="moyenne">{{ t('incidents.severity.medium') }}</option>
          <option value="haute">{{ t('incidents.severity.high') }}</option>
        </select>

        <button class="btn-outline" @click="resetFilters">{{ t('filters.reset') }}</button>
      </div>
    </div>

    <p v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</p>

    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b text-left">
            <th class="p-2">{{ t('incidents.columns.date') }}</th>
            <th class="p-2">{{ t('incidents.columns.room') }}</th>
            <th class="p-2">{{ t('incidents.columns.type') }}</th>
            <th class="p-2">{{ t('incidents.columns.severity') }}</th>
            <th class="p-2">{{ t('incidents.columns.status') }}</th>
            <th class="p-2">{{ t('incidents.fields.source') }}</th>
            <th class="p-2">{{ t('incidents.columns.description') }}</th>
            <th class="p-2">{{ t('incidents.columns.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="incident in incidents" :key="incident._id" class="border-b align-top last:border-b-0">
            <td class="p-2 whitespace-nowrap">{{ formatDate(incident.reportedAt) }}</td>
            <td class="p-2">{{ incident?.room?.number || '-' }}</td>
            <td class="p-2">
              <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="typeClass(incident.type)">
                {{ typeLabel(incident.type) }}
              </span>
            </td>
            <td class="p-2">
              <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="severityClass(incident.severity)">
                {{ severityLabel(incident.severity) }}
              </span>
            </td>
            <td class="p-2">
              <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="statusClass(incident.status)">
                {{ statusLabel(incident.status) }}
              </span>
            </td>
            <td class="p-2">{{ sourceLabel(incident.source) }}</td>
            <td class="p-2 max-w-md" :title="incident.description">{{ truncate(incident.description, 100) }}</td>
            <td class="p-2">
              <button class="btn-outline text-xs" @click="openStatusModal(incident)">
                {{ t('incidents.actions.changeStatus') }}
              </button>
            </td>
          </tr>
          <tr v-if="!loading && incidents.length === 0">
            <td class="p-3 text-gray-500" colspan="8">{{ t('incidents.messages.noData') }}</td>
          </tr>
          <tr v-if="loading">
            <td class="p-3 text-gray-500" colspan="8">{{ t('common.loading') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="closeModal">
        <div class="w-full max-w-md space-y-4 rounded-2xl bg-white p-5 shadow-2xl">
          <h3 class="text-lg font-bold text-gray-800">{{ t('incidents.actions.changeStatus') }}</h3>
          <p class="text-sm text-gray-500">#{{ modal.incident?.room?.number || '-' }} - {{ typeLabel(modal.incident?.type) }}</p>

          <div class="space-y-2">
            <button v-for="status in statusOptions" :key="status.value" class="w-full rounded-xl border px-3 py-2 text-left hover:bg-gray-50" @click="updateStatus(status.value)">
              {{ status.label }}
            </button>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button class="btn-outline" @click="closeModal">{{ t('common.cancel') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { useHotelStore } from '../../../stores/hotel'

const { t } = useI18n()
const hotelStore = useHotelStore()

const loading = ref(false)
const incidents = ref([])
const errorMsg = ref('')
let refreshInterval = null

const hotels = computed(() => hotelStore.hotels)

const filters = reactive({
  status: '',
  type: '',
  severity: '',
  hotelId: '',
})

const stats = reactive({
  total: 0,
  ouverts: 0,
  enCours: 0,
  resolus: 0,
  avgResolutionBySeverity: {
    basse: null,
    moyenne: null,
    haute: null,
  },
  byDay: [],
})

const modal = reactive({
  open: false,
  incident: null,
})

const typeOptions = computed(() => [
  { value: 'propreté', label: t('incidents.types.cleanliness') },
  { value: 'maintenance', label: t('incidents.types.maintenance') },
  { value: 'sécurité', label: t('incidents.types.security') },
  { value: 'bruit', label: t('incidents.types.noise') },
  { value: 'autre', label: t('incidents.types.other') },
])

const statusOptions = computed(() => [
  { value: 'ouvert', label: t('incidents.status.open') },
  { value: 'en_cours', label: t('incidents.status.inProgress') },
  { value: 'résolu', label: t('incidents.status.resolved') },
])

function truncate(text, max) {
  const value = String(text || '')
  if (value.length <= max) return value
  return `${value.slice(0, max)}...`
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString()
}

function typeLabel(type) {
  const found = typeOptions.value.find((item) => item.value === type)
  return found?.label || type || '-'
}

function sourceLabel(source) {
  return t(`incidents.sources.${source || 'reception'}`)
}

function severityLabel(severity) {
  if (severity === 'basse') return t('incidents.severity.low')
  if (severity === 'haute') return t('incidents.severity.high')
  return t('incidents.severity.medium')
}

function statusLabel(status) {
  if (status === 'ouvert') return t('incidents.status.open')
  if (status === 'résolu') return t('incidents.status.resolved')
  return t('incidents.status.inProgress')
}

function typeClass(type) {
  const map = {
    propreté: 'bg-sky-100 text-sky-700',
    maintenance: 'bg-indigo-100 text-indigo-700',
    sécurité: 'bg-purple-100 text-purple-700',
    bruit: 'bg-yellow-100 text-yellow-700',
    autre: 'bg-gray-100 text-gray-700',
  }
  return map[type] || map.autre
}

function severityClass(severity) {
  const map = {
    basse: 'bg-green-100 text-green-700',
    moyenne: 'bg-amber-100 text-amber-700',
    haute: 'bg-red-100 text-red-700',
  }
  return map[severity] || map.moyenne
}

function statusClass(status) {
  const map = {
    ouvert: 'bg-red-100 text-red-700',
    en_cours: 'bg-amber-100 text-amber-700',
    résolu: 'bg-green-100 text-green-700',
  }
  return map[status] || map.ouvert
}

function resolutionText(minutes) {
  if (minutes === null || minutes === undefined) return '-'
  if (minutes < 60) return `${minutes} min`
  return `${Math.round((minutes / 60) * 10) / 10} h`
}

function dayHeight(count) {
  const max = Math.max(1, ...stats.byDay.map((day) => Number(day.count || 0)))
  return Math.max(8, Math.round((Number(count || 0) / max) * 100))
}

function openStatusModal(incident) {
  modal.incident = incident
  modal.open = true
}

function closeModal() {
  modal.incident = null
  modal.open = false
}

async function fetchIncidents() {
  loading.value = true
  errorMsg.value = ''
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    if (filters.type) params.type = filters.type
    if (filters.severity) params.severity = filters.severity
    if (filters.hotelId) params.hotelId = filters.hotelId

    const { data } = await api.get('/incidents', { params })
    incidents.value = Array.isArray(data) ? data : []
  } catch {
    incidents.value = []
    errorMsg.value = t('incidents.messages.loadError')
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const { data } = await api.get('/incidents/stats')
    stats.total = Number(data?.total || 0)
    stats.ouverts = Number(data?.statuses?.ouvert ?? data?.ouverts ?? 0)
    stats.enCours = Number(data?.statuses?.en_cours ?? data?.enCours ?? 0)
    stats.resolus = Number(data?.statuses?.résolu ?? data?.resolus ?? 0)
    stats.avgResolutionBySeverity = data?.avgResolutionBySeverity || { basse: null, moyenne: null, haute: null }
    stats.byDay = Array.isArray(data?.byDay) ? data.byDay : []
  } catch {
    stats.total = 0
    stats.ouverts = 0
    stats.enCours = 0
    stats.resolus = 0
    stats.avgResolutionBySeverity = { basse: null, moyenne: null, haute: null }
    stats.byDay = []
  }
}

async function updateStatus(status) {
  if (!modal.incident?._id) return

  try {
    await api.patch(`/incidents/${modal.incident._id}`, { status })
    closeModal()
    await refreshData()
  } catch {
    errorMsg.value = t('incidents.messages.updateError')
  }
}

async function refreshData() {
  if (hotelStore.hotels.length === 0) {
    await hotelStore.fetchHotels()
  }
  await Promise.all([fetchIncidents(), fetchStats()])
}

function resetFilters() {
  filters.status = ''
  filters.type = ''
  filters.severity = ''
  filters.hotelId = ''
  fetchIncidents()
}

onMounted(async () => {
  await refreshData()
  refreshInterval = setInterval(refreshData, 45000)
})

onBeforeUnmount(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
})
</script>
