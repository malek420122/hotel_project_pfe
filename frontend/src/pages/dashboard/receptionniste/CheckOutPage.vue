<template>
  <div class="checkout-page">
    <div class="checkout-head">
      <div>
        <h1 class="dashboard-title text-2xl font-bold text-gray-800 mb-6">{{ t('checkout.title') }}</h1>
        <p class="checkout-subtitle">{{ t('checkout.todayCheckouts') }}</p>
      </div>
      <div class="checkout-date-pill">{{ currentDateLabel }}</div>
    </div>

    <div class="checkout-stats-grid mb-6">
      <article class="checkout-stat-card checkout-stat-highlight">
        <p class="checkout-stat-value">{{ stats.departuresToday }}</p>
        <p class="checkout-stat-label">{{ t('checkout.todayCheckouts') }}</p>
      </article>
      <article class="checkout-stat-card">
        <p class="checkout-stat-value">{{ stats.totalToCollect }}€</p>
        <p class="checkout-stat-label">Total à encaisser</p>
      </article>
      <article class="checkout-stat-card">
        <p class="checkout-stat-value">{{ stats.delayedDepartures }}</p>
        <p class="checkout-stat-label">Départ en retard</p>
      </article>
    </div>

    <div class="card checkout-panel mb-6">
      <h3 class="checkout-section-title">{{ t('checkout.searchClient') }}</h3>
      <p v-if="errorMsg" class="checkout-error">{{ errorMsg }}</p>
      <div class="checkout-search-row">
        <input v-model="search" :placeholder="t('checkout.searchPlaceholder')" class="input-field flex-1" />
        <button @click="doSearch" class="btn-primary checkout-search-btn">{{ t('checkout.searchButton') }}</button>
      </div>
    </div>
    <div class="card checkout-panel">
      <h3 class="checkout-section-title">{{ t('checkout.todayCheckouts') }}</h3>
      <div class="space-y-3">
        <div v-for="r in filteredCheckouts" :key="r.ref" class="checkout-row">
          <div class="checkout-avatar">{{ r.initials }}</div>
          <div class="flex-1 min-w-0">
            <p class="checkout-client">{{ r.client }}</p>
            <p class="checkout-meta">{{ r.hotel }} · Réf. {{ r.ref }}</p>
            <div class="checkout-tags">
              <span class="checkout-tag checkout-tag-room">{{ r.chambre }}</span>
              <span class="checkout-tag">{{ r.nights }} nuits</span>
              <span v-if="r.isDelayed" class="checkout-tag checkout-tag-delay">Départ en retard</span>
            </div>
          </div>
          <div class="checkout-actions">
            <div class="checkout-price-wrap">
              <span class="checkout-price">{{ r.prix }}€</span>
              <span class="checkout-price-sub">À régler</span>
            </div>
            <button @click="doCheckOut(r)" class="btn-accent checkout-button">{{ t('checkout.checkoutButton') }}</button>
          </div>
        </div>
        <div v-if="!filteredCheckouts.length" class="checkout-empty">{{ t('checkout.empty') }}</div>
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

const currentDateLabel = new Intl.DateTimeFormat('fr-FR', {
  weekday: 'long',
  day: '2-digit',
  month: 'long',
  year: 'numeric',
}).format(new Date())

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

const toDayStart = (value) => {
  if (!value) return null
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return null
  date.setHours(0, 0, 0, 0)
  return date
}

const diffDays = (start, end) => {
  const ms = 24 * 60 * 60 * 1000
  return Math.max(1, Math.round((end.getTime() - start.getTime()) / ms))
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

const stats = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return checkouts.value.reduce((acc, checkout) => {
    acc.departuresToday += 1
    acc.totalToCollect += Number(checkout.prix || 0)

    const departureDate = toDayStart(checkout.dateDepart)
    if (departureDate && departureDate < today) acc.delayedDepartures += 1
    return acc
  }, {
    departuresToday: 0,
    totalToCollect: 0,
    delayedDepartures: 0,
  })
})

function getResId(r) {
  if (!r) return ''
  const val = r._id || r.id
  if (!val) return ''
  if (typeof val === 'string') return val
  if (typeof val === 'object' && val.$oid) return val.$oid
  return String(val)
}

async function loadCheckOuts() {
  try {
    errorMsg.value = ''
    const { data } = await api.get('/reservations', { params: { statut: 'EN_COURS' } })
    const rows = Array.isArray(data) ? data : []
    checkouts.value = rows.map((r) => ({
      ref: getResId(r),
      client: [r?.client?.prenom, r?.client?.nom].filter(Boolean).join(' ') || t('checkout.clientFallback'),
      hotel: r?.hotel?.nom || t('checkout.hotelFallback'),
      chambre: normalizeRoomType(r?.chambre?.type || r?.chambre?.nom || r?.chambre?.numero || r?.chambreId),
      initials: [r?.client?.prenom, r?.client?.nom]
        .filter(Boolean)
        .map((part) => String(part).trim()[0]?.toUpperCase())
        .join('') || 'CL',
      nights: diffDays(toDayStart(r?.dateArrivee) || new Date(), toDayStart(r?.dateDepart) || new Date()),
      isDelayed: !!(toDayStart(r?.dateDepart) && toDayStart(r?.dateDepart) < new Date(new Date().setHours(0, 0, 0, 0))),
      heure: formatLocalizedDate(r?.dateDepart, locale.value),
      prix: Number(r?.prixTotal || 0),
      dateDepart: r?.dateDepart,
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

<style scoped>
.checkout-page {
  color: var(--text-primary);
}

.checkout-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.checkout-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--text-primary);
}

.checkout-subtitle {
  margin-top: 0.2rem;
  color: var(--text-soft);
  font-size: 0.92rem;
}

.checkout-date-pill {
  align-self: center;
  padding: 0.45rem 0.9rem;
  border-radius: 999px;
  background: #3A1A04;
  color: #f8d79c;
  font-size: 0.82rem;
  font-weight: 700;
}

.checkout-stats-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 0.9rem;
}

.checkout-stat-card {
  border: 1px solid var(--border);
  border-radius: 16px;
  background: rgba(255,255,255,0.96);
  padding: 1rem 1.1rem;
  box-shadow: var(--card-shadow);
}

.checkout-stat-highlight {
  background: linear-gradient(135deg, #D4820A, #e49b31);
  color: #fff7ed;
  border-color: rgba(212,130,10,0.25);
}

.checkout-stat-value {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  line-height: 1;
  font-weight: 800;
}

.checkout-stat-label {
  margin-top: 0.35rem;
  font-size: 0.9rem;
  color: inherit;
}

@media (min-width: 900px) {
  .checkout-stats-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.checkout-panel {
  border: 1px solid var(--border);
  border-radius: 16px;
  background: var(--bg-card);
  box-shadow: var(--card-shadow);
}

.checkout-section-title {
  margin-bottom: 1rem;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text-primary);
}

.checkout-error {
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  color: #b91c1c;
}

.checkout-search-row {
  display: flex;
  gap: 0.75rem;
}

.checkout-search-btn {
  min-width: 120px;
}

.checkout-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 14px;
  border-left: 4px solid #D4820A;
  border: 1px solid var(--border);
  background: rgba(255, 255, 255, 0.95);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.checkout-row:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(58, 26, 4, 0.05);
}

.checkout-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(212,130,10,0.12), rgba(139,69,19,0.08));
  color: #3A1A04;
  font-weight: 800;
  font-size: 0.95rem;
}

.checkout-client {
  font-weight: 800;
  color: var(--text-primary);
}

.checkout-meta {
  margin-top: 0.2rem;
  font-size: 0.9rem;
  color: var(--text-soft);
}

.checkout-tags {
  margin-top: 0.55rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.checkout-tag {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.25rem 0.55rem;
  background: rgba(212,130,10,0.12);
  color: #8B4513;
  font-size: 0.75rem;
  font-weight: 700;
}

.checkout-tag-room {
  background: rgba(248, 213, 150, 0.34);
}

.checkout-tag-delay {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
}

.checkout-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}

.checkout-price-wrap {
  text-align: right;
}

.checkout-price {
  display: block;
  font-family: 'Playfair Display', serif;
  font-weight: 800;
  color: #3A1A04;
  font-size: 1.45rem;
  line-height: 1;
}

.checkout-price-sub {
  display: block;
  margin-top: 0.2rem;
  font-size: 0.74rem;
  color: var(--text-soft);
}

.checkout-button {
  min-width: 92px;
  background: #3A1A04;
  color: #f8d79c;
  box-shadow: 0 8px 18px rgba(58,26,4,0.16);
}

.checkout-button:hover {
  background: #4a2410;
  color: #fdf3dc;
}

.checkout-empty {
  padding: 1rem 0;
  text-align: center;
  font-size: 0.9rem;
  color: var(--text-soft);
}

@media (max-width: 768px) {
  .checkout-head,
  .checkout-search-row,
  .checkout-row {
    flex-direction: column;
    align-items: stretch;
  }

  .checkout-date-pill {
    align-self: flex-start;
  }

  .checkout-actions {
    justify-content: space-between;
  }
}
</style>
