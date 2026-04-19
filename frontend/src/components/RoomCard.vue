<template>
  <div class="card flex gap-4 hover:shadow-lg transition-shadow">
    <div class="w-32 h-28 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
      <img :src="roomImage(room)" :alt="roomDisplayName" class="w-full h-full object-cover" />
    </div>
    <div class="flex-1">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-bold text-gray-800 text-lg">{{ roomDisplayName }}</h3>
          <p class="text-gray-500 text-sm">{{ roomMeta }}</p>
        </div>
        <span :class="['room-status-badge', roomStatus.className]">{{ roomStatus.label }}</span>
      </div>
      <p class="text-gray-600 text-sm mt-2 line-clamp-2">{{ localizedRoomDescription }}</p>
      <div v-if="room.equipements?.length" class="flex flex-wrap gap-1 mt-2">
        <span v-for="eq in room.equipements.slice(0,4)" :key="eq"
          class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ serviceLabel(eq) }}</span>
      </div>
      <div class="flex items-center justify-between mt-3">
        <div>
          <span class="text-2xl font-bold text-secondary">{{ room.prix_base }}</span>
          <span class="text-gray-400 text-xs">€/{{ t('hotels.perNight') }}</span>
        </div>
        <button v-if="!isUnavailable" @click="$emit('reserve', room)" class="btn-primary text-sm py-2 px-4">
          {{ t('room.bookThisRoom') }}
        </button>
        <span v-else class="text-red-500 font-semibold text-sm">{{ t('room.unavailable') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { getServiceLabel } from '../composables/useServiceLabel'

const props = defineProps({ room: { type: Object, required: true } })
defineEmits(['reserve'])
const { t, locale } = useI18n()

const roomFallbacks = [
  'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
]

function seedIndex(seed, length) {
  const value = String(seed || '')
  let hash = 0
  for (let i = 0; i < value.length; i++) hash = (hash * 31 + value.charCodeAt(i)) >>> 0
  return length ? hash % length : 0
}

function roomImage(item) {
  const photos = Array.isArray(item?.photos) ? item.photos.filter(Boolean) : []
  const pool = photos.length ? photos : roomFallbacks
  return pool[seedIndex(item?._id || item?.nom, pool.length)]
}

function getRoomTypeName(type) {
  const normalized = String(type || '').toLowerCase().trim()
  const types = {
    simple: { fr: 'Chambre Simple', en: 'Single Room', ar: 'غرفة فردية' },
    'chambre simple': { fr: 'Chambre Simple', en: 'Single Room', ar: 'غرفة فردية' },
    single: { fr: 'Chambre Simple', en: 'Single Room', ar: 'غرفة فردية' },
    double: { fr: 'Chambre Double', en: 'Double Room', ar: 'غرفة مزدوجة' },
    'chambre double': { fr: 'Chambre Double', en: 'Double Room', ar: 'غرفة مزدوجة' },
    suite: { fr: 'Suite', en: 'Suite', ar: 'جناح' },
    deluxe: { fr: 'Chambre Deluxe', en: 'Deluxe Room', ar: 'غرفة ديلوكس' },
    'chambre deluxe': { fr: 'Chambre Deluxe', en: 'Deluxe Room', ar: 'غرفة ديلوكس' },
  }
  return types[normalized]?.[locale.value] || types[normalized]?.fr || String(type || '')
}

function localizeText(value) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value[locale.value] || value.fr || value.en || value.ar || Object.values(value)[0] || ''
  }
  return String(value || '')
}

const roomDisplayName = computed(() => {
  const typeName = getRoomTypeName(props.room?.type)
  return typeName || localizeText(props.room?.nom)
})

const localizedRoomDescription = computed(() => {
  return localizeText(props.room?.description)
})

const roomMeta = computed(() => {
  const typeText = roomDisplayName.value.toUpperCase()
  const floorText = t('room.floor', { n: props.room?.etage ?? '-' })
  const capacity = Number(props.room?.maxVoyageurs || props.room?.capacite || 1)
  const personsText = t('room.maxPersons', { n: capacity })
  return `${typeText} · ${floorText} · ${personsText}`
})

const roomStatus = computed(() => {
  const status = String(props.room?.statut || '').toUpperCase()
  if (status === 'NETTOYAGE') {
    return { label: t('room.cleaning'), className: 'room-status-cleaning' }
  }
  if (status === 'LIBRE') {
    return { label: t('room.available'), className: 'room-status-available' }
  }
  return { label: t(`status.${status}`, status), className: 'room-status-default' }
})

const isUnavailable = computed(() => {
  const status = String(props.room?.statut || '').toUpperCase()
  return ['OCCUPEE', 'INDISPONIBLE'].includes(status)
})

function serviceLabel(service) {
  return getServiceLabel(service, locale.value)
}
</script>

<style scoped>
.room-status-badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 0.32rem 0.7rem;
  font-size: 0.72rem;
  font-weight: 700;
}

.room-status-cleaning {
  background: rgba(245, 158, 11, 0.18);
  color: #b45309;
}

.room-status-available {
  background: rgba(16, 185, 129, 0.18);
  color: #047857;
}

.room-status-default {
  background: rgba(100, 116, 139, 0.18);
  color: #334155;
}
</style>
