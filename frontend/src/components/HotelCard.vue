<template>
  <RouterLink :to="`/hotels/${hotel._id}`" class="block group">
    <div class="card overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer p-0">
      <div class="relative h-48 overflow-hidden">
        <div v-if="imageLoading" class="image-skeleton absolute inset-0"></div>
        <img :src="hotelImage(hotel)" :alt="hotel.nom"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          @load="onImageLoad"
          @error="onImageError" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        <div class="absolute top-3 left-3 flex gap-2">
          <span class="bg-white/90 text-xs font-bold text-gray-800 px-2 py-1 rounded-lg">
            {{ '★'.repeat(hotel.etoiles || 0) }}
          </span>
        </div>
        <div v-if="hotel.noteMoyenne" class="absolute top-3 right-3">
          <span class="bg-secondary text-white text-sm font-bold px-2 py-1 rounded-lg">
            ⭐ {{ hotel.noteMoyenne?.toFixed(1) }}
          </span>
        </div>
        <div class="absolute bottom-3 left-3 text-white">
          <p class="font-bold text-lg leading-tight">{{ hotel.nom }}</p>
          <p class="text-white/80 text-xs">📍 {{ getVille(hotel) }}</p>
        </div>
      </div>
      <div class="p-4">
        <p class="text-gray-500 text-sm line-clamp-2 mb-3">{{ getDescription(hotel) }}</p>
        <div v-if="hotel.equipements?.length" class="flex flex-wrap gap-1 mb-3">
          <span v-for="eq in hotel.equipements.slice(0,3)" :key="eq"
            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ serviceLabel(eq) }}</span>
          <span v-if="hotel.equipements.length > 3" class="text-xs text-gray-400">+{{ hotel.equipements.length - 3 }}</span>
        </div>
        <div class="flex justify-between items-center">
          <div>
            <span class="text-2xl font-bold text-secondary">{{ hotel.prix_min || '—' }}</span>
            <span v-if="hotel.prix_min" class="text-gray-400 text-xs">€/{{ t('hotels.perNight') }}</span>
          </div>
          <span class="btn-primary text-sm py-1.5 px-4">{{ t('hotelsSection.viewButton') }}</span>
        </div>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ref } from 'vue'
import { getServiceLabel } from '../composables/useServiceLabel'

const props = defineProps({ hotel: { type: Object, required: true } })
const { t, locale } = useI18n()
const imageLoading = ref(true)
const FALLBACK_IMAGE = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'

const hotelFallbacks = [
  'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1400&q=80',
]

function seedIndex(seed, length) {
  const value = String(seed || '')
  let hash = 0
  for (let i = 0; i < value.length; i++) hash = (hash * 31 + value.charCodeAt(i)) >>> 0
  return length ? hash % length : 0
}

function hotelImage(hotel) {
  if (hotel?.previewPhoto) return hotel.previewPhoto
  const photos = Array.isArray(hotel?.photos) ? hotel.photos.filter(Boolean) : []
  const pool = photos.length ? photos : hotelFallbacks
  return pool[seedIndex(hotel?._id || hotel?.nom || props.hotel?.nom, pool.length)]
}

function getDescription(hotel) {
  const lang = locale.value
  if (hotel?.description && typeof hotel.description === 'object' && !Array.isArray(hotel.description)) {
    return hotel.description[lang] || hotel.description.fr || Object.values(hotel.description)[0] || ''
  }
  return hotel?.description || ''
}

function getVille(hotel) {
  const lang = locale.value
  if (hotel?.ville && typeof hotel.ville === 'object' && !Array.isArray(hotel.ville)) {
    return hotel.ville[lang] || hotel.ville.fr || Object.values(hotel.ville)[0] || ''
  }
  return hotel?.ville || ''
}

function serviceLabel(service) {
  return getServiceLabel(service, locale.value)
}

function onImageLoad() {
  imageLoading.value = false
}

function onImageError(event) {
  if (!event?.target) return
  event.target.src = FALLBACK_IMAGE
  imageLoading.value = false
}
</script>

<style scoped>
.image-skeleton {
  background: linear-gradient(90deg, #1e3a5f 25%, #2a4a7f 50%, #1e3a5f 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
