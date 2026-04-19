<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('reception.specialRequests.title') }}</h2>

    <div class="card mb-5 flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="tab-btn"
        :class="selectedTab === tab.value ? 'tab-btn-active' : 'tab-btn-idle'"
        @click="selectedTab = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="n in 4" :key="n" class="h-28 rounded-2xl bg-white/5 animate-pulse"></div>
    </div>

    <div v-else class="space-y-4">
      <div v-for="req in filteredRequests" :key="req.id" :class="['card border-l-4', req.priority === 'URGENT' ? 'border-red-400' : req.priority === 'LOW' ? 'border-emerald-400' : 'border-amber-400']">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span :class="['status-pill', req.status === 'TRAITE' ? 'status-pill-green' : 'status-pill-amber']">
                {{ req.status === 'TRAITE' ? t('reception.specialRequests.processed') : t('reception.specialRequests.pending') }}
              </span>
              <span :class="['status-pill', req.priority === 'URGENT' ? 'status-pill-red' : req.priority === 'LOW' ? 'status-pill-green' : 'status-pill-amber']">
                {{ priorityLabel(req.priority) }}
              </span>
            </div>
            <p class="font-bold text-gray-800 truncate">{{ req.client }} — {{ req.roomLabel }}</p>
            <p class="text-gray-600 text-sm mt-1 line-clamp-2">{{ req.demande }}</p>
            <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-gray-500 sm:grid-cols-2 lg:grid-cols-4">
              <div><span class="font-semibold text-gray-700">{{ t('reception.specialRequests.hotel') }}:</span> {{ req.hotel }}</div>
              <div><span class="font-semibold text-gray-700">{{ t('reception.specialRequests.roomNumber') }}:</span> {{ req.roomNumber }}</div>
              <div><span class="font-semibold text-gray-700">{{ t('reception.specialRequests.arrival') }}:</span> {{ req.arrivee }}</div>
              <div><span class="font-semibold text-gray-700">{{ t('reception.specialRequests.departure') }}:</span> {{ req.depart }}</div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ req.heure }}</p>
          </div>
          <div class="flex flex-wrap gap-2 lg:ml-4">
            <button @click="openDetails(req)" class="action-btn action-btn-blue">{{ t('reception.specialRequests.viewDetails') }}</button>
            <button v-if="req.status !== 'TRAITE'" @click="markDone(req)" class="action-btn action-btn-green">{{ t('reception.specialRequests.markProcessed') }}</button>
            <button class="action-btn action-btn-gray">{{ t('dashboard.reply') }}</button>
          </div>
        </div>
      </div>

      <div v-if="!filteredRequests.length" class="card text-center py-10 text-gray-400">
        <p class="text-3xl mb-2">📭</p>
        <p>{{ t('reception.specialRequests.noRequests') }}</p>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 bg-black/50 flex items-end justify-center lg:items-center" @click.self="selected = null">
        <div class="w-full max-w-2xl bg-white rounded-t-3xl lg:rounded-3xl p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h3 class="text-xl font-bold text-gray-800">{{ t('reception.specialRequests.details') }}</h3>
              <p class="text-sm text-gray-500">{{ selected.client }} — {{ selected.roomLabel }}</p>
            </div>
            <button class="text-gray-400 hover:text-gray-700" @click="selected = null">✕</button>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-4 text-sm text-gray-700">
            <div><span class="font-semibold">{{ t('reception.specialRequests.hotel') }}:</span> {{ selected.hotel }}</div>
            <div><span class="font-semibold">{{ t('reception.specialRequests.roomNumber') }}:</span> {{ selected.roomNumber }}</div>
            <div><span class="font-semibold">{{ t('reception.specialRequests.arrival') }}:</span> {{ selected.arrivee }}</div>
            <div><span class="font-semibold">{{ t('reception.specialRequests.departure') }}:</span> {{ selected.depart }}</div>
            <div><span class="font-semibold">{{ t('reception.specialRequests.priority') }}:</span> {{ priorityLabel(selected.priority) }}</div>
            <div><span class="font-semibold">{{ t('reception.specialRequests.status') }}:</span> {{ selected.status === 'TRAITE' ? t('reception.specialRequests.processed') : t('reception.specialRequests.pending') }}</div>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700 whitespace-pre-line">
            {{ selected.demande }}
          </div>

          <div class="flex justify-end gap-2 mt-5">
            <button v-if="selected.status !== 'TRAITE'" class="action-btn action-btn-green" @click="markDone(selected)">{{ t('reception.specialRequests.markProcessed') }}</button>
            <button class="action-btn action-btn-gray" @click="selected = null">{{ t('common.close') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const { t, locale } = useI18n()
const requests = ref([])
const loading = ref(true)
const selectedTab = ref('pending')
const selected = ref(null)

const tabs = computed(() => [
  { value: 'all', label: t('reception.specialRequests.all') },
  { value: 'pending', label: t('reception.specialRequests.pending') },
  { value: 'processed', label: t('reception.specialRequests.processed') },
])

const filteredRequests = computed(() => {
  if (selectedTab.value === 'pending') {
    return requests.value.filter((req) => req.status !== 'TRAITE')
  }

  if (selectedTab.value === 'processed') {
    return requests.value.filter((req) => req.status === 'TRAITE')
  }

  return requests.value
})

function priorityLabel(priority) {
  if (priority === 'URGENT') return '🔴 ' + t('reception.specialRequests.priorityUrgent')
  if (priority === 'LOW') return '🟢 ' + t('reception.specialRequests.priorityLow')
  return '🟡 ' + t('reception.specialRequests.priorityNormal')
}

function mapRequest(req) {
  return {
    id: String(req.id || req._id || ''),
    client: String(req.client || t('reception.specialRequests.guestFallback')),
    roomLabel: String(req.chambre || req.roomNumber || t('reception.specialRequests.roomFallback')),
    roomNumber: String(req.roomNumber || req.chambre || t('reception.specialRequests.roomFallback')),
    hotel: String(req.hotel || t('reception.specialRequests.hotelFallback')),
    demande: String(req.details || req.demande || ''),
    heure: String(req.heure || '-'),
    arrivee: formatLocalizedDate(req.arrivee, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
    depart: formatLocalizedDate(req.depart, locale.value, { day: '2-digit', month: 'short', year: 'numeric' }) || '-',
    priority: req.priority || 'NORMAL',
    status: req.status || 'EN_ATTENTE',
  }
}

async function fetchRequests() {
  try {
    loading.value = true
    const { data } = await api.get('/reservations/special-requests')
    requests.value = Array.isArray(data) ? data.map(mapRequest) : []
    if (!requests.value.some((req) => req.status !== 'TRAITE')) {
      selectedTab.value = 'processed'
    }
  } catch {
    requests.value = []
  } finally {
    loading.value = false
  }
}

function openDetails(req) {
  selected.value = req
}

async function markDone(req) {
  try {
    await api.put(`/reservations/${req.id}/special-request/done`)
    selected.value = null
    await fetchRequests()
  } catch {
    // keep state unchanged on failure
  }
}

onMounted(fetchRequests)
</script>

<style scoped>
.tab-btn {
  padding: 0.6rem 0.95rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 700;
}

.tab-btn-active {
  background: #0f172a;
  color: #fff;
}

.tab-btn-idle {
  background: rgba(15, 23, 42, 0.08);
  color: #0f172a;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.3rem 0.7rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.status-pill-amber {
  background: rgba(245, 158, 11, 0.16);
  color: #d97706;
}

.status-pill-green {
  background: rgba(16, 185, 129, 0.16);
  color: #059669;
}

.status-pill-red {
  background: rgba(239, 68, 68, 0.16);
  color: #dc2626;
}

.action-btn {
  padding: 0.55rem 0.85rem;
  border-radius: 0.8rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.action-btn-blue {
  background: rgba(59, 130, 246, 0.14);
  color: #2563eb;
}

.action-btn-green {
  background: rgba(16, 185, 129, 0.14);
  color: #059669;
}

.action-btn-gray {
  background: rgba(100, 116, 139, 0.12);
  color: #475569;
}
</style>
