<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des reservations</h2>
      <button @click="fetchReservations" class="btn-primary text-sm">Actualiser</button>
    </div>

    <div class="card flex flex-wrap gap-2">
      <button
        v-for="f in filters"
        :key="f.value"
        @click="setFilter(f.value)"
        :class="[
          'px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors',
          selectedFilter === f.value ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        {{ f.label }}
      </button>
    </div>

    <div v-if="loading" class="card text-gray-500">Chargement...</div>

    <div v-else class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Reference</th>
            <th class="p-2">Client</th>
            <th class="p-2">Hotel</th>
            <th class="p-2">{{ $t('dashboard.dates') }}</th>
            <th class="p-2">Demande</th>
            <th class="p-2">{{ $t('dashboard.status') }}</th>
            <th class="p-2">{{ $t('dashboard.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="res in reservations" :key="res._id" class="border-b last:border-b-0">
            <td class="p-2 font-semibold">{{ res.reference }}</td>
            <td class="p-2">{{ res.clientId }}</td>
            <td class="p-2">{{ res.hotelId }}</td>
            <td class="p-2">{{ res.dateArrivee }} -> {{ res.dateDepart }}</td>
            <td class="p-2 max-w-xs truncate">{{ res.demandesSpeciales || '-' }}</td>
            <td class="p-2"><StatusBadge :status="res.statut" /></td>
            <td class="p-2">
              <div class="flex flex-wrap gap-2">
                <button
                  v-if="['EN_ATTENTE','REJETE'].includes(res.statut)"
                  @click="confirmReservation(res._id)"
                  class="px-2 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200"
                >
                  Confirmer
                </button>
                <button
                  v-if="['EN_ATTENTE','CONFIRMEE'].includes(res.statut)"
                  @click="rejectReservation(res._id)"
                  class="px-2 py-1 rounded bg-red-50 text-red-700 border border-red-200"
                >
                  Refuser
                </button>
                <button
                  v-if="res.statut === 'CONFIRMEE'"
                  @click="checkinReservation(res._id)"
                  class="px-2 py-1 rounded bg-green-50 text-green-700 border border-green-200"
                >
                  Check-in
                </button>
                <button
                  v-if="res.statut === 'EN_COURS'"
                  @click="checkoutReservation(res._id)"
                  class="px-2 py-1 rounded bg-gray-100 text-gray-700 border border-gray-300"
                >
                  Check-out
                </button>
                <button @click="openDetails(res._id)" class="px-2 py-1 rounded bg-slate-100 text-slate-700 border border-slate-300">Voir</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <p v-if="!reservations.length" class="text-gray-500 py-4">Aucune reservation pour ce filtre.</p>
    </div>

    <div v-if="selected" class="card">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-gray-800">Detail reservation {{ selected.reference }}</h3>
        <button @click="selected = null" class="text-sm text-gray-500">Fermer</button>
      </div>
      <pre class="text-xs bg-gray-50 p-3 rounded-lg overflow-x-auto">{{ selected }}</pre>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'

const loading = ref(false)
const selectedFilter = ref('ALL')
const reservations = ref([])
const selected = ref(null)

const filters = [
  { value: 'ALL', label: 'Toutes' },
  { value: 'EN_ATTENTE', label: 'En attente' },
  { value: 'CONFIRMEE', label: 'Confirmees' },
  { value: 'EN_COURS', label: 'En cours' },
  { value: 'TERMINEE', label: 'Terminees' },
  { value: 'REJETE', label: 'Refusees' },
  { value: 'ANNULEE', label: 'Annulees' },
]

function setFilter(filter) {
  selectedFilter.value = filter
  fetchReservations()
}

async function fetchReservations() {
  loading.value = true
  try {
    const params = selectedFilter.value === 'ALL' ? {} : { statut: selectedFilter.value }
    const { data } = await api.get('/reservations', { params })
    reservations.value = data
  } finally {
    loading.value = false
  }
}

async function confirmReservation(id) {
  await api.put(`/reservations/${id}/confirmer`)
  await fetchReservations()
}

async function rejectReservation(id) {
  const motif = window.prompt('Motif de refus (optionnel):', '')
  await api.put(`/reservations/${id}/rejeter`, { motif: motif || '' })
  await fetchReservations()
}

async function checkinReservation(id) {
  await api.put(`/reservations/${id}/checkin`)
  await fetchReservations()
}

async function checkoutReservation(id) {
  await api.put(`/reservations/${id}/checkout`)
  await fetchReservations()
}

async function openDetails(id) {
  const { data } = await api.get(`/reservations/${id}`)
  selected.value = data
}

onMounted(fetchReservations)
</script>
