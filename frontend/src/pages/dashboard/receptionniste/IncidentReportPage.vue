<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ t('incidents.report.title') }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ t('incidents.report.subtitle') }}</p>
      </div>
      <button @click="refreshAll" class="btn-outline text-sm">{{ t('common.refresh') }}</button>
    </div>

    <div class="card">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h3 class="text-lg font-bold text-gray-800">{{ t('incidents.signals.title') }}</h3>
          <p class="text-sm text-gray-500">{{ t('incidents.signals.subtitle') }}</p>
        </div>
        <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
          {{ pendingSignals.length }}
        </span>
      </div>

      <div class="mt-4 space-y-3">
        <div v-if="signalsLoading" class="text-sm text-gray-500">{{ t('common.loading') }}</div>
        <div v-else-if="!pendingSignals.length" class="text-sm text-gray-500">{{ t('incidents.signals.empty') }}</div>
        <div v-else class="space-y-2">
          <div
            v-for="signal in pendingSignals"
            :key="signal._id"
            class="rounded-xl border border-orange-100 bg-orange-50/50 p-3"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div>
                <p class="text-sm font-semibold text-gray-800">
                  {{ signal.room?.number || '-' }} - {{ signal.client?.name || t('reception.common.clientFallback') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">{{ formatDate(signal.createdAt) }}</p>
                <p class="mt-2 text-sm text-gray-700">{{ signal.message }}</p>
              </div>
              <button class="btn-outline whitespace-nowrap text-sm" @click="prefillFromSignal(signal)">
                {{ t('incidents.signals.createIncident') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card max-w-3xl">
      <div v-if="activeSignal" class="mb-4 rounded-xl border border-orange-200 bg-orange-50 p-3 text-sm text-orange-800">
        {{ t('incidents.signals.prefilled') }} #{{ activeSignal.room?.number || '-' }}
        <button class="ml-2 font-semibold underline" type="button" @click="clearActiveSignal">
          {{ t('common.cancel') }}
        </button>
      </div>

      <form class="space-y-5" @submit.prevent="submitIncident">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">{{ t('incidents.fields.room') }}</label>
          <select v-model="form.room" class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
            <option value="" disabled>{{ t('incidents.placeholders.selectRoom') }}</option>
            <optgroup v-for="group in roomsByFloor" :key="group.floor" :label="floorLabel(group.floor)">
              <option v-for="room in group.rooms" :key="room._id" :value="room._id">
                {{ roomLabel(room) }}
              </option>
            </optgroup>
          </select>
          <p v-if="submitted && errors.room" class="text-red-600 text-xs mt-1">{{ errors.room }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">{{ t('incidents.fields.type') }}</label>
          <select v-model="form.type" class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white">
            <option value="">{{ t('incidents.placeholders.selectType') }}</option>
            <option v-for="type in incidentTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
          <p v-if="submitted && errors.type" class="text-red-600 text-xs mt-1">{{ errors.type }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">{{ t('incidents.fields.severity') }}</label>
          <div class="flex flex-wrap gap-2">
            <label v-for="level in severityLevels" :key="level.value" class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 cursor-pointer" :class="form.severity === level.value ? level.activeClass : 'border-gray-300 text-gray-700'">
              <input v-model="form.severity" type="radio" class="hidden" :value="level.value" />
              <span class="text-sm font-medium">{{ level.label }}</span>
            </label>
          </div>
          <p v-if="submitted && errors.severity" class="text-red-600 text-xs mt-1">{{ errors.severity }}</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">{{ t('incidents.fields.source') }}</label>
          <select v-model="form.source" class="w-full rounded-xl border border-gray-300 px-3 py-2 bg-white" :disabled="Boolean(activeSignal)">
            <option value="reception">{{ t('incidents.sources.reception') }}</option>
            <option value="menage">{{ t('incidents.sources.menage') }}</option>
            <option value="appel">{{ t('incidents.sources.appel') }}</option>
            <option value="client">{{ t('incidents.sources.client') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1">{{ t('incidents.fields.description') }}</label>
          <textarea
            v-model="form.description"
            rows="5"
            class="w-full rounded-xl border border-gray-300 px-3 py-2"
            :placeholder="t('incidents.placeholders.description')"
          ></textarea>
          <p class="text-xs text-gray-500 mt-1">{{ form.description.length }}/500</p>
          <p v-if="submitted && errors.description" class="text-red-600 text-xs mt-1">{{ errors.description }}</p>
        </div>

        <div v-if="feedback.message" class="rounded-xl px-4 py-2 text-sm" :class="feedback.type === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
          {{ feedback.message }}
        </div>

        <div class="flex justify-end">
          <button class="btn-primary" type="submit" :disabled="submitting">
            <span v-if="submitting">{{ t('incidents.actions.submitting') }}</span>
            <span v-else>{{ t('incidents.actions.report') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'

const { t, locale } = useI18n()

const rooms = ref([])
const pendingSignals = ref([])
const signalsLoading = ref(false)
const submitting = ref(false)
const submitted = ref(false)
const activeSignal = ref(null)
const feedback = reactive({ type: '', message: '' })

const form = reactive({
  room: '',
  type: '',
  severity: '',
  source: 'reception',
  description: '',
})

const errors = reactive({
  room: '',
  type: '',
  severity: '',
  description: '',
})

const incidentTypes = computed(() => [
  { value: 'propreté', label: t('incidents.types.cleanliness') },
  { value: 'maintenance', label: t('incidents.types.maintenance') },
  { value: 'sécurité', label: t('incidents.types.security') },
  { value: 'bruit', label: t('incidents.types.noise') },
  { value: 'autre', label: t('incidents.types.other') },
])

const severityLevels = computed(() => [
  { value: 'basse', label: t('incidents.severity.low'), activeClass: 'border-green-500 bg-green-50 text-green-700' },
  { value: 'moyenne', label: t('incidents.severity.medium'), activeClass: 'border-amber-500 bg-amber-50 text-amber-700' },
  { value: 'haute', label: t('incidents.severity.high'), activeClass: 'border-red-500 bg-red-50 text-red-700' },
])

const sortedRooms = computed(() => {
  return [...rooms.value].sort((a, b) => {
    const aNumber = roomSortValue(a)
    const bNumber = roomSortValue(b)
    if (aNumber !== bNumber) return aNumber - bNumber
    return roomNumber(a).localeCompare(roomNumber(b), undefined, { numeric: true })
  })
})

const roomsByFloor = computed(() => {
  const groups = new Map()

  sortedRooms.value.forEach((room) => {
    const floor = roomFloor(room)
    if (!groups.has(floor)) {
      groups.set(floor, [])
    }
    groups.get(floor).push(room)
  })

  return [...groups.entries()]
    .sort(([a], [b]) => Number(a) - Number(b))
    .map(([floor, groupRooms]) => ({ floor, rooms: groupRooms }))
})

function roomLabel(room) {
  return `Chambre ${roomNumber(room)} - ${roomType(room)}`
}

function roomNumber(room) {
  return String(room?.number || room?.numero || room?.room_number || deriveRoomNumber(room) || '-').trim()
}

function roomType(room) {
  const value = String(room?.type || '').trim()
  if (!value) return 'Standard'
  return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase()
}

function roomFloor(room) {
  const explicit = Number(room?.floor ?? room?.etage)
  if (Number.isFinite(explicit) && explicit >= 0) return explicit

  const numeric = roomSortValue(room)
  if (numeric !== Number.MAX_SAFE_INTEGER) return Math.floor(numeric / 100)
  return 0
}

function floorLabel(floor) {
  return Number(floor) > 0 ? `Étage ${floor}` : 'Étage non défini'
}

function deriveRoomNumber(room) {
  const name = String(room?.nom || '').trim()
  const match = name.match(/\d+/)
  return match?.[0] || ''
}

function roomSortValue(room) {
  const match = roomNumber(room).match(/\d+/)
  return match ? Number(match[0]) : Number.MAX_SAFE_INTEGER
}

function formatDate(value) {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return date.toLocaleString(locale.value)
}

function clearErrors() {
  errors.room = ''
  errors.type = ''
  errors.severity = ''
  errors.description = ''
}

function validateForm() {
  clearErrors()

  if (!form.room) errors.room = t('incidents.validation.required')
  if (!form.type) errors.type = t('incidents.validation.required')
  if (!form.severity) errors.severity = t('incidents.validation.required')

  const desc = String(form.description || '').trim()
  if (!desc) {
    errors.description = t('incidents.validation.required')
  } else if (desc.length < 5) {
    errors.description = t('incidents.validation.descriptionMin')
  }

  return !errors.room && !errors.type && !errors.severity && !errors.description
}

function resetForm() {
  form.room = ''
  form.type = ''
  form.severity = ''
  form.source = 'reception'
  form.description = ''
  submitted.value = false
  activeSignal.value = null
}

function prefillFromSignal(signal) {
  activeSignal.value = signal
  form.room = String(signal?.room?._id || '')
  form.source = 'client'
  form.description = String(signal?.message || '')
  submitted.value = false
  feedback.type = ''
  feedback.message = ''
}

function clearActiveSignal() {
  activeSignal.value = null
}

async function fetchRooms() {
  try {
    const { data } = await api.get('/rooms')
    rooms.value = Array.isArray(data) ? data : []
  } catch {
    rooms.value = []
    feedback.type = 'error'
    feedback.message = t('incidents.messages.roomsLoadError')
  }
}

async function fetchSignals() {
  signalsLoading.value = true
  try {
    const { data } = await api.get('/reception/signals')
    pendingSignals.value = Array.isArray(data) ? data : []
  } catch {
    pendingSignals.value = []
  } finally {
    signalsLoading.value = false
  }
}

async function refreshAll() {
  await Promise.all([fetchRooms(), fetchSignals()])
}

async function submitIncident() {
  feedback.type = ''
  feedback.message = ''
  submitted.value = true

  if (!validateForm()) return

  submitting.value = true
  try {
    const payload = {
      room: form.room,
      type: form.type,
      severity: form.severity,
      source: form.source,
      description: form.description.trim(),
    }

    if (activeSignal.value?._id) {
      payload.signalId = activeSignal.value._id
    }

    await api.post('/incidents', payload)

    feedback.type = 'success'
    feedback.message = t('incidents.messages.reportSuccess')
    resetForm()
    await fetchSignals()
  } catch (error) {
    const backendMessage = error?.response?.data?.message
    feedback.type = 'error'
    feedback.message = backendMessage || t('incidents.messages.reportError')
  } finally {
    submitting.value = false
  }
}

onMounted(refreshAll)
</script>
