<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ t('dashboard.my_reservations') }}</h2>
      <RouterLink to="/hotels" class="btn-primary text-sm">{{ t('dashboard.new_booking') }}</RouterLink>
    </div>

    <div class="card mb-6 flex flex-wrap gap-2">
      <button
        v-for="item in filters"
        :key="item.key"
        @click="activeFilter = item.key"
        :class="['px-4 py-2 rounded-xl text-sm font-medium transition-colors', activeFilter === item.key ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']"
      >
        {{ item.label }}
      </button>
    </div>

    <p v-if="errorMsg" class="mb-4 text-sm text-red-600">{{ errorMsg }}</p>
    <p v-if="successMsg" class="mb-4 text-sm text-green-600">{{ successMsg }}</p>

    <div v-if="bookingStore.loading" class="flex justify-center py-16">
      <div class="w-10 h-10 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else-if="rows.length" class="card overflow-x-auto">
      <table class="w-full text-sm min-w-[980px]">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('reservation.reference') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('common.hotel') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('common.room') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('reservation.arrival') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('reservation.departure') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('reservation.travelers') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('reservation.totalPrice') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('common.status') }}</th>
            <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="res in rows" :key="res._id" class="hover:bg-gray-50">
            <td class="px-3 py-3">{{ res.reference || '-' }}</td>
            <td class="px-3 py-3">{{ res.hotel?.nom || '-' }}</td>
            <td class="px-3 py-3">{{ getRoomTypeName(res.chambre?.type) }}</td>
            <td class="px-3 py-3">{{ formatDisplayDate(res.dateArrivee) }}</td>
            <td class="px-3 py-3">{{ formatDisplayDate(res.dateDepart) }}</td>
            <td class="px-3 py-3">{{ res.nbVoyageurs || 1 }}</td>
            <td class="px-3 py-3 font-semibold text-secondary">{{ Number(res.prixTotal || 0).toFixed(2) }}€</td>
            <td class="px-3 py-3"><StatusBadge :status="res.statut" /></td>
            <td class="px-3 py-3">
              <div class="flex gap-2 flex-wrap">
                <button
                  v-if="canModify(res)"
                  class="btn-outline text-xs py-1.5 px-3"
                  @click="openModify(res)"
                >{{ t('reservations.modify') }}</button>

                <button
                  v-if="canCancel(res)"
                  class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-600 border border-red-200 hover:bg-red-100"
                  @click="openCancel(res)"
                >{{ t('reservations.cancel') }}</button>

                <button class="invoice-btn" @click="showInvoice(res)">📄 {{ t('reservations.invoice') }}</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="card text-center py-16">
      <p class="text-lg text-gray-500">{{ t('reservations.emptyTitle') }}</p>
      <RouterLink to="/hotels" class="btn-primary mt-4 inline-block text-sm">{{ t('dashboard.new_booking') }}</RouterLink>
    </div>

    <div v-if="modifyModal.show" class="fixed inset-0 z-50 modify-modal-backdrop flex items-center justify-center p-4" @click.self="closeModify">
      <div class="modify-modal w-full max-w-lg p-5 sm:p-6">
        <button class="modify-close-btn" @click="closeModify" :aria-label="t('common.close')">×</button>
        <h3 class="modify-title mb-3">{{ t('reservations.modifyTitle') }}</h3>
        <div class="modify-info-box mb-4">
          <span>⚠️</span>
          <span>{{ t('reservations.modifyHint') }}</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold modal-label">{{ t('reservations.newArrival') }}</label>
            <input v-model="modifyForm.dateArrivee" type="date" :min="today" class="modify-input" />
          </div>
          <div>
            <label class="text-xs font-semibold modal-label">{{ t('reservations.newDeparture') }}</label>
            <input v-model="modifyForm.dateDepart" type="date" :min="modifyForm.dateArrivee || today" class="modify-input" />
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button class="modal-cancel-btn" @click="closeModify">{{ t('common.cancel') }}</button>
          <button class="modal-save-btn" @click="submitModify">✓ {{ t('common.save') }}</button>
        </div>
      </div>
    </div>

    <div v-if="cancelModal.show" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-5">
        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ t('reservations.cancelTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-3">
          {{ t('dashboard.reference') }} {{ cancelModal.res?.reference }} · {{ cancelModal.res?.hotel?.nom || '-' }}
        </p>

        <div class="text-sm text-gray-600 rounded-xl bg-gray-50 p-3 border mb-4">
          <p class="font-semibold mb-1">{{ t('reservations.cancelPolicyTitle') }}</p>
          <p>{{ t('reservations.cancelPolicyFree') }}</p>
          <p>{{ t('reservations.cancelPolicyFee') }}</p>
        </div>

        <div class="flex justify-end gap-2">
          <button class="btn-outline" @click="cancelModal.show = false">{{ t('reservations.keepReservation') }}</button>
          <button class="px-4 py-2 rounded-xl bg-red-600 text-white font-semibold" @click="confirmCancel">{{ t('reservations.confirmCancel') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useBookingStore } from '../../../stores/booking'
import StatusBadge from '../../../components/StatusBadge.vue'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const bookingStore = useBookingStore()
const { locale, t } = useI18n()

const errorMsg = ref('')
const successMsg = ref('')
const activeFilter = ref('all')
const today = new Date().toISOString().split('T')[0]

const filters = computed(() => ([
  { key: 'all', label: t('reservations.filters.all') },
  { key: 'upcoming', label: t('reservations.filters.upcoming') },
  { key: 'running', label: t('reservations.filters.running') },
  { key: 'past', label: t('reservations.filters.past') },
  { key: 'cancelled', label: t('reservations.filters.cancelled') },
]))

const modifyModal = reactive({ show: false, res: null })
const modifyForm = reactive({ dateArrivee: '', dateDepart: '' })
const cancelModal = reactive({ show: false, res: null })
let refreshTimer = null

const rows = computed(() => {
  const list = bookingStore.reservations || []
  const now = new Date()
  now.setHours(0, 0, 0, 0)

  const asDayDate = (value) => {
    if (!value) return null
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return null
    d.setHours(0, 0, 0, 0)
    return d
  }

  if (activeFilter.value === 'upcoming') {
    return list.filter((r) => {
      const status = String(r.statut || '')
      const arrival = asDayDate(r.dateArrivee)
      return ['EN_ATTENTE', 'CONFIRMEE'].includes(status) && !!arrival && arrival > now
    })
  }

  if (activeFilter.value === 'running') {
    return list.filter((r) => {
      const status = String(r.statut || '')
      const arrival = asDayDate(r.dateArrivee)
      const departure = asDayDate(r.dateDepart)

      if (['EN_COURS', 'CHECKIN'].includes(status)) return true

      // Confirmed reservation should appear as running during its stay window.
      return status === 'CONFIRMEE' && !!arrival && !!departure && arrival <= now && departure >= now
    })
  }

  if (activeFilter.value === 'past') {
    return list.filter((r) => ['TERMINEE', 'CHECKOUT'].includes(String(r.statut || '')))
  }
  if (activeFilter.value === 'cancelled') {
    return list.filter((r) => ['ANNULEE', 'REJETE'].includes(String(r.statut || '')))
  }

  return list
})

function formatDisplayDate(d) {
  return formatLocalizedDate(d, locale.value, { day: '2-digit', month: '2-digit', year: 'numeric' }) || '—'
}

function getRoomTypeName(type) {
  const normalized = String(type || '').toLowerCase().trim()
  if (!normalized) return '-'

  const labels = {
    simple: { fr: 'Chambre Simple', en: 'Single Room', ar: 'غرفة فردية' },
    double: { fr: 'Chambre Double', en: 'Double Room', ar: 'غرفة مزدوجة' },
    suite: { fr: 'Suite', en: 'Suite', ar: 'جناح' },
    deluxe: { fr: 'Chambre Deluxe', en: 'Deluxe Room', ar: 'غرفة ديلوكس' },
  }

  const mapped = labels[normalized]
  if (!mapped) return String(type)
  return mapped[locale.value] || mapped.fr
}

function canCancel(res) {
  return ['EN_ATTENTE', 'CONFIRMEE'].includes(String(res.statut || ''))
}

function canModify(res) {
  if (String(res.statut || '') !== 'CONFIRMEE') return false
  const arrival = new Date(res.dateArrivee)
  const nowPlus48h = new Date(Date.now() + 48 * 60 * 60 * 1000)
  return arrival > nowPlus48h
}

function openModify(res) {
  modifyModal.show = true
  modifyModal.res = res
  modifyForm.dateArrivee = String(res.dateArrivee || '').slice(0, 10)
  modifyForm.dateDepart = String(res.dateDepart || '').slice(0, 10)
}

function closeModify() {
  modifyModal.show = false
  modifyModal.res = null
}

async function submitModify() {
  if (!modifyModal.res?._id) return

  const inDate = new Date(modifyForm.dateArrivee)
  const outDate = new Date(modifyForm.dateDepart)
  const now = new Date()
  now.setHours(0, 0, 0, 0)

  if (!(inDate > now)) {
    errorMsg.value = t('reservations.futureArrivalError')
    return
  }
  if (!(outDate > inDate)) {
    errorMsg.value = t('reservations.departureAfterArrivalError')
    return
  }

  try {
    errorMsg.value = ''
    successMsg.value = ''

    await api.put(`/reservations/${encodeURIComponent(modifyModal.res._id)}/reschedule`, {
      dateArrivee: modifyForm.dateArrivee,
      dateDepart: modifyForm.dateDepart,
    })

    await bookingStore.fetchMyReservations()
    successMsg.value = t('reservations.modifiedSuccess')
    closeModify()
  } catch (e) {
    errorMsg.value = e.response?.data?.error || e.response?.data?.message || t('reservations.modifyImpossible')
  }
}

function openCancel(res) {
  cancelModal.show = true
  cancelModal.res = res
}

async function confirmCancel() {
  if (!cancelModal.res?._id) return
  try {
    errorMsg.value = ''
    successMsg.value = ''
    await bookingStore.cancelReservation(cancelModal.res._id)
    await bookingStore.fetchMyReservations()
    successMsg.value = t('reservations.cancelledSuccess')
    cancelModal.show = false
  } catch (e) {
    errorMsg.value = e.response?.data?.error || t('reservations.cancelImpossible')
  }
}

async function showInvoice(res) {
  try {
    errorMsg.value = ''
    const response = await api.get(`/reservations/${encodeURIComponent(res._id)}/invoice`, {
      responseType: 'blob',
    })

    const contentDisposition = response.headers?.['content-disposition'] || ''
    const match = contentDisposition.match(/filename=\"?([^\";]+)\"?/i)
    const filename = (match?.[1] || `${t('reservations.invoiceFilename')}-${res.reference || res._id}.pdf`).trim()

    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    let backendMessage = ''

    const data = e?.response?.data
    if (data instanceof Blob) {
      try {
        const text = await data.text()
        const parsed = JSON.parse(text)
        backendMessage = parsed?.error || parsed?.message || ''
      } catch {
        backendMessage = ''
      }
    } else {
      backendMessage = e?.response?.data?.error || e?.response?.data?.message || ''
    }

    errorMsg.value = backendMessage || t('reservations.invoiceDownloadError')
  }
}

onMounted(async () => {
  try {
    await bookingStore.fetchMyReservations()
  } catch {
    errorMsg.value = t('reservations.loadError')
  }

  refreshTimer = setInterval(() => {
    bookingStore.fetchMyReservations({ silent: true }).catch(() => {})
  }, 4000)
})

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})
</script>

<style scoped>
.invoice-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid rgba(148, 163, 184, 0.6);
  background: transparent;
  color: #475569;
  border-radius: 0.75rem;
  padding: 0.35rem 0.7rem;
  font-size: 0.75rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

.invoice-btn:hover {
  border-color: rgba(30, 41, 59, 0.6);
  color: var(--text-primary);
  background: rgba(148, 163, 184, 0.08);
}

.modify-modal-backdrop {
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(6px);
}

.modify-modal {
  position: relative;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16px;
  color: var(--text-primary);
  box-shadow: var(--card-shadow);
}

.modify-close-btn {
  position: absolute;
  top: 0.85rem;
  right: 0.9rem;
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: var(--text-soft);
  background: rgba(255,255,255,0.98);
  font-size: 1.2rem;
  line-height: 1;
}

.modify-close-btn:hover {
  background: rgba(255, 255, 255, 0.14);
}

.modify-title {
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 700;
  padding-right: 2rem;
}

.modify-info-box {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  background: rgba(245, 158, 11, 0.1);
  border-left: 3px solid #f59e0b;
  color: #fbbf24;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 0.88rem;
}

.modal-label {
  color: var(--text-primary);
  display: inline-block;
  margin-bottom: 0.4rem;
}

.modify-input {
  width: 100%;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.96);
  color: var(--text-primary);
  padding: 0.6rem 0.75rem;
  outline: none;
}

.modify-input:focus {
  border-color: rgba(212,130,10,0.3);
  box-shadow: 0 0 0 2px rgba(212,130,10,0.12);
}

.modal-cancel-btn {
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--text-primary);
  background: transparent;
  padding: 0.58rem 1rem;
  font-weight: 600;
}

.modal-cancel-btn:hover {
  background: rgba(255, 255, 255, 0.08);
}

.modal-save-btn {
  border: none;
  border-radius: 12px;
  color: var(--text-primary);
  background: linear-gradient(135deg, #FDF3DC, #FCECCF);
  padding: 0.58rem 1rem;
  font-weight: 700;
}

.modal-save-btn:hover {
  filter: brightness(1.05);
}
</style>
