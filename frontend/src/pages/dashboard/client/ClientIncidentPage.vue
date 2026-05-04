<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ t('incidents.client.title') }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ t('incidents.client.subtitle') }}</p>
      </div>
      <button class="btn-outline text-sm" @click="refreshHistory">{{ t('common.refresh') }}</button>
    </div>

    <div class="card max-w-3xl">
      <div v-if="checkingReservation" class="space-y-3">
        <div class="h-5 w-40 rounded bg-gray-200 animate-pulse"></div>
        <div class="h-24 w-full rounded bg-gray-200 animate-pulse"></div>
      </div>
      <div v-else-if="!hasActiveReservation" class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
        <p class="font-semibold">📌 {{ t('incidents.client.noActiveStay') }}</p>
        <p class="mt-2">{{ t('incidents.client.contextLoadError') }}</p>
      </div>
      <form v-else class="space-y-5" @submit.prevent="submitSignal">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">
            {{ t('incidents.client.messageLabel') }}
          </label>
          <textarea
            v-model="message"
            rows="6"
            class="w-full rounded-xl border border-gray-300 px-3 py-2"
            :placeholder="t('incidents.client.messagePlaceholder')"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">{{ message.length }}/500</p>
          <p v-if="errorMsg" class="text-red-600 text-xs mt-1">{{ errorMsg }}</p>
        </div>

        <div v-if="feedback.message" class="rounded-xl px-4 py-2 text-sm" :class="feedback.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
          {{ feedback.message }}
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-60"
            :disabled="submitting"
          >
            <TriangleAlert :size="16" />
            <span v-if="submitting">{{ t('incidents.actions.submitting') }}</span>
            <span v-else>{{ t('incidents.client.sendButton') }}</span>
          </button>
        </div>
      </form>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800">{{ t('incidents.client.signalsHistory') }}</h3>
        <div class="mt-4 space-y-3">
          <div v-if="loadingHistory" class="text-sm text-gray-500">{{ t('common.loading') }}</div>
          <div v-else-if="!signals.length" class="text-sm text-gray-500">{{ t('incidents.client.noHistory') }}</div>
          <div v-else class="space-y-2">
            <div v-for="signal in signals" :key="signal._id" class="rounded-xl border border-gray-200 p-3">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-gray-800">#{{ signal.room?.number || '-' }}</p>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="signalStatusClass(signal.status)">
                  {{ signalStatusLabel(signal.status) }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">{{ formatDate(signal.createdAt) }}</p>
              <p class="mt-2 text-sm text-gray-700">{{ signal.message }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <h3 class="text-lg font-bold text-gray-800">{{ t('incidents.client.incidentsHistory') }}</h3>
        <div class="mt-4 space-y-3">
          <div v-if="loadingHistory" class="text-sm text-gray-500">{{ t('common.loading') }}</div>
          <div v-else-if="!incidents.length" class="text-sm text-gray-500">{{ t('incidents.client.noIncidents') }}</div>
          <div v-else class="space-y-2">
            <div v-for="incident in incidents" :key="incident._id" class="rounded-xl border border-gray-200 p-3">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-gray-800">{{ typeLabel(incident.type) }} - #{{ incident.room?.number || '-' }}</p>
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="incidentStatusClass(incident.status)">
                  {{ incident.clientStatus || clientStatusLabel(incident.status) }}
                </span>
              </div>
              <p class="mt-1 text-xs text-gray-500">{{ formatDate(incident.reportedAt) }}</p>
              <p class="mt-2 text-sm text-gray-700">{{ incident.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { TriangleAlert } from 'lucide-vue-next'
import api from '../../../api'

const { t, locale } = useI18n()

const submitting = ref(false)
const loadingHistory = ref(false)
const checkingReservation = ref(true)
const hasActiveReservation = ref(false)
const message = ref('')
const signals = ref([])
const incidents = ref([])
const errorMsg = ref('')
const feedback = reactive({ type: '', message: '' })

const incidentTypes = computed(() => [
  { value: 'propreté', label: t('incidents.types.cleanliness') },
  { value: 'maintenance', label: t('incidents.types.maintenance') },
  { value: 'sécurité', label: t('incidents.types.security') },
  { value: 'bruit', label: t('incidents.types.noise') },
  { value: 'autre', label: t('incidents.types.other') },
])

function validateMessage() {
  errorMsg.value = ''
  const value = String(message.value || '').trim()

  if (!value) {
    errorMsg.value = t('incidents.validation.required')
  } else if (value.length < 5) {
    errorMsg.value = t('incidents.validation.descriptionMin')
  }

  return !errorMsg.value
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString(locale.value)
}

function typeLabel(type) {
  const found = incidentTypes.value.find((item) => item.value === type)
  return found?.label || type || '-'
}

function signalStatusLabel(status) {
  return status === 'traité' ? t('incidents.signals.processed') : t('incidents.signals.pending')
}

function signalStatusClass(status) {
  return status === 'traité' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'
}

function clientStatusLabel(status) {
  if (status === 'résolu') return t('incidents.status.resolved')
  if (status === 'en_cours') return t('incidents.status.inProgress')
  return t('incidents.client.waiting')
}

function incidentStatusClass(status) {
  if (status === 'résolu') return 'bg-green-100 text-green-700'
  if (status === 'en_cours') return 'bg-amber-100 text-amber-700'
  return 'bg-red-100 text-red-700'
}

async function checkActiveReservation() {
  checkingReservation.value = true
  try {
    const { data } = await api.get('/client/reservations')
    const active = Array.isArray(data) ? data.find(r => r.statut === 'EN_COURS' && r.checkinAt && !r.checkoutAt && r.chambreId) : null
    hasActiveReservation.value = !!active
  } catch {
    hasActiveReservation.value = false
  } finally {
    checkingReservation.value = false
  }
}

async function refreshHistory() {
  loadingHistory.value = true
  try {
    const [signalsResponse, incidentsResponse] = await Promise.all([
      api.get('/clients/me/signals'),
      api.get('/clients/me/incidents'),
    ])
    signals.value = Array.isArray(signalsResponse.data) ? signalsResponse.data : []
    incidents.value = Array.isArray(incidentsResponse.data) ? incidentsResponse.data : []
  } catch {
    signals.value = []
    incidents.value = []
  } finally {
    loadingHistory.value = false
  }
}

async function submitSignal() {
  feedback.type = ''
  feedback.message = ''

  if (!validateMessage()) return

  submitting.value = true
  try {
    await api.post('/clients/signal', {
      message: String(message.value || '').trim(),
    })

    feedback.type = 'success'
    feedback.message = t('incidents.client.signalConfirmation')
    message.value = ''
    await refreshHistory()
  } catch (error) {
    const backendMessage = error?.response?.data?.message
    feedback.type = 'error'
    feedback.message = backendMessage || t('incidents.client.signalError')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await checkActiveReservation()
  await refreshHistory()
})
</script>
