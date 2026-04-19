<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('checkout.title') }}</h2>
    <div class="card mb-6">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ t('checkout.searchClient') }}</h3>
      <p v-if="errorMsg" class="mb-3 text-sm text-red-600">{{ errorMsg }}</p>
      <div class="flex gap-3">
        <input v-model="search" :placeholder="t('checkout.searchPlaceholder')" class="input-field flex-1" />
        <button @click="doSearch" class="btn-primary">{{ t('checkout.searchButton') }}</button>
      </div>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ t('checkout.todayCheckouts') }}</h3>
      <div class="space-y-3">
        <div v-for="r in filteredCheckouts" :key="r.ref" class="flex items-center gap-4 p-4 rounded-xl border hover:shadow-sm">
          <div class="text-3xl">🚪</div>
          <div class="flex-1">
            <p class="font-bold text-gray-800">{{ r.client }}</p>
            <p class="text-sm text-gray-500">{{ r.hotel }} · {{ r.chambre }} · {{ t('checkout.time') }}: {{ r.heure }}</p>
          </div>
          <div class="flex gap-2 items-center">
            <span class="font-bold text-secondary">{{ r.prix }}€</span>
            <button @click="doCheckOut(r)" class="btn-accent text-xs py-1.5 px-4">{{ t('checkout.checkoutButton') }}</button>
          </div>
        </div>
        <div v-if="!filteredCheckouts.length" class="text-sm text-gray-400 text-center py-4">{{ t('checkout.empty') }}</div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'
const { t, locale } = useI18n()
const search = ref('')
const checkouts = ref([])
const errorMsg = ref('')

const translateRoomType = (backendValue, translate) => {
  const value = String(backendValue || '').trim()
  const lower = value.toLowerCase()
  const map = {
    'chambre double': translate('roomType.double'),
    'chambre simple': translate('roomType.simple'),
    'chambre standard': translate('roomType.standard'),
    'chambre deluxe': translate('roomType.deluxe'),
    'Chambre Double': translate('roomType.double'),
    'Chambre Simple': translate('roomType.simple'),
    'Chambre Standard': translate('roomType.standard'),
    'Chambre Deluxe': translate('roomType.deluxe'),
    DOUBLE: translate('roomType.double'),
    SIMPLE: translate('roomType.simple'),
    STANDARD: translate('roomType.standard'),
    DELUXE: translate('roomType.deluxe'),
    SUITE: translate('roomType.suite'),
  }
  return map[value] || map[lower] || map[value.toUpperCase()] || value
}

const normalizeRoomType = (roomValue) => {
  const value = String(roomValue || '').trim()
  if (!value) return t('checkout.roomFallback')

  const mapped = String(translateRoomType(value, t) || value).trim()
  return mapped.replace(/^chambre\s+chambre\s+/i, 'Chambre ').trim()
}

const filteredCheckouts = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return checkouts.value
  return checkouts.value.filter((c) =>
    c.client.toLowerCase().includes(q)
    || String(c.chambre).toLowerCase().includes(q)
    || String(c.ref).toLowerCase().includes(q),
  )
})

async function loadCheckOuts() {
  try {
    errorMsg.value = ''
    const { data } = await api.get('/reservations', { params: { statut: 'EN_COURS' } })
    const rows = Array.isArray(data) ? data : []
    checkouts.value = rows.map((r) => ({
      ref: String(r._id || ''),
      client: [r?.client?.prenom, r?.client?.nom].filter(Boolean).join(' ') || t('checkout.clientFallback'),
      hotel: r?.hotel?.nom || t('checkout.hotelFallback'),
      chambre: normalizeRoomType(r?.chambre?.type || r?.chambre?.nom || r?.chambre?.numero || r?.chambreId),
      heure: formatLocalizedDate(r?.dateDepart, locale.value),
      prix: Number(r?.prixTotal || 0),
    }))
  } catch {
    checkouts.value = []
    errorMsg.value = t('checkout.loadError')
  }
}

function doSearch() {
  // Filtering is computed from current search value.
}

async function doCheckOut(r) {
  try {
    errorMsg.value = ''
    await api.put(`/reservations/${encodeURIComponent(r.ref)}/checkout`)
    await loadCheckOuts()
  } catch {
    errorMsg.value = t('checkout.checkoutError', { name: r.client })
  }
}

onMounted(loadCheckOuts)
</script>
