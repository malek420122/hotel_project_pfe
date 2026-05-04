<template>
  <div>
    <teleport to="head">
      <title>HotelEase | {{ localizedHotelName }} - {{ localizedHotelCity }}</title>
      <meta name="description" :content="localizedHotelDescription" />
    </teleport>
    <Navbar />
    <div class="pt-20">
      <div v-if="hotelStore.loading" class="max-w-7xl mx-auto px-4 py-10 space-y-8 animate-pulse">
        <div class="h-80 bg-gray-200 rounded-2xl w-full"></div>
        <div class="flex gap-2">
          <div v-for="i in 4" :key="i" class="h-24 w-40 bg-gray-200 rounded-xl"></div>
        </div>
        <div class="h-32 bg-gray-200 rounded-2xl w-full"></div>
        <div class="h-64 bg-gray-200 rounded-2xl w-full"></div>
      </div>
      <div v-else-if="hotel">
        <!-- Hero -->
        <div class="relative h-80 overflow-hidden">
          <img :src="mainPhoto" :alt="localizedHotelName"
            class="w-full h-full object-cover" @error="onImageError" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          <div class="absolute bottom-6 left-8 text-white">
            <h1 class="text-4xl font-extrabold mb-1">{{ localizedHotelName }}</h1>
            <p class="text-lg text-white/80">📍 {{ hotel.adresse }}, {{ localizedHotelCity }}</p>
            <div class="flex items-center gap-3 mt-2">
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" :class="i <= hotel.etoiles ? 'text-accent' : 'text-white/40'" class="text-xl">★</span>
              </div>
              <span class="bg-secondary text-white px-3 py-1 rounded-lg font-bold">{{ ratingBadgeText }}</span>
            </div>
          </div>
        </div>

        <!-- Photo Gallery -->
        <div v-if="galleryPhotos.length > 1" class="max-w-7xl mx-auto px-6 mt-4 flex gap-2 overflow-x-auto pb-2">
          <img
            v-for="(photo, i) in galleryPhotos"
            :key="`${photo}-${i}`"
            :src="photo || hotelImage(hotel)"
            @error="onImageError"
            loading="lazy"
            referrerpolicy="no-referrer"
            :class="[
              'h-24 w-40 object-cover rounded-xl cursor-pointer hover:opacity-95 flex-shrink-0 border-2 transition-all',
              photo === mainPhoto ? 'border-secondary ring-2 ring-secondary/30' : 'border-transparent',
            ]"
            @click="activePhoto = photo"
          />
        </div>

        <div class="max-w-7xl mx-auto px-4 py-10">
          <!-- Main -->
          <div class="space-y-8">
            <!-- Description -->
            <div class="card">
              <h2 class="text-xl font-bold text-primary mb-3">{{ t('hotel.about') }}</h2>
              <p class="text-gray-600">{{ localizedHotelDescription }}</p>
            </div>

            <!-- Amenities -->
            <div v-if="hotel.equipements?.length" class="card">
              <h2 class="text-xl font-bold text-primary mb-4">{{ t('hotel.amenities') }}</h2>
              <div class="flex flex-wrap gap-2">
                <span v-for="eq in hotel.equipements" :key="eq"
                  class="bg-secondary/10 text-secondary px-3 py-1 rounded-full text-sm font-medium">
                  {{ serviceLabel(eq) }}
                </span>
              </div>
            </div>

            <!-- Map -->
            <div v-if="hotel.latitude && hotel.longitude" class="card">
              <h2 class="text-xl font-bold text-primary mb-4">{{ t('hotel.location') }}</h2>
              <HotelMap :hotel="hotel" />
            </div>

            <!-- Rooms -->
            <div>
              <h2 class="text-2xl font-bold text-primary mb-4">{{ t('hotel.availableRooms') }}</h2>
              <div class="space-y-4">
                <RoomCard v-for="room in hotelStore.chambres" :key="room._id" :room="room"
                  @reserve="selectRoom(room)" />
              </div>
              <div v-if="!hotelStore.chambres.length" class="card text-center text-gray-400">
                <p class="text-3xl mb-2">🛏️</p>
                <p>{{ t('hotel.noRooms') }}</p>
              </div>
            </div>

            <!-- Reviews Section (Guestbook style) -->
            <div v-if="hotelReviews.length" class="card overflow-hidden !p-0">
              <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
                <div>
                  <h2 class="text-2xl font-serif font-bold text-[#2D1B08]">{{ t('hotel.customerReviews', { count: hotelReviews.length }) }}</h2>
                  <p class="text-slate-500 text-sm mt-1">Expériences partagées par nos hôtes</p>
                </div>
                <div class="text-right">
                  <p class="text-3xl font-serif font-bold text-[#D4820A]">{{ Number(hotel.noteMoyenne || 0).toFixed(1) }}</p>
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Note Globale</p>
                </div>
              </div>
              <div class="divide-y divide-gray-100">
                <div v-for="avis in hotelReviews.slice(0,5)" :key="avis._id" class="p-8 hover:bg-slate-50/30 transition-colors">
                  <div class="flex items-start gap-6">
                    <div class="w-12 h-12 bg-[#2D1B08] rounded-full flex items-center justify-center text-[#D4820A] font-bold flex-shrink-0 shadow-lg shadow-[#2D1B08]/10 text-lg">
                      {{ avis.client?.prenom?.[0] || 'A' }}
                    </div>
                    <div class="flex-1">
                      <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                        <p class="font-bold text-[#2D1B08] text-lg">{{ avis.client?.prenom }} {{ avis.client?.nom }}</p>
                        <div class="flex gap-0.5">
                          <Star v-for="i in 5" :key="i" :size="14" :fill="i <= avis.note ? '#D4820A' : 'none'" :class="i <= avis.note ? 'text-[#D4820A]' : 'text-gray-200'" />
                        </div>
                      </div>
                      <p class="text-slate-600 leading-relaxed italic text-lg">"{{ avis.commentaire }}"</p>
                      <p class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-400 mt-4">{{ formatDisplayDate(avis.createdAt) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHotelStore } from '../stores/hotel'
import { useBookingStore } from '../stores/booking'
import { useAuthStore } from '../stores/auth'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import HotelMap from '../components/HotelMap.vue'
import RoomCard from '../components/RoomCard.vue'
import { Star, MapPin, Building2, Users, Calendar, ShieldCheck, Sparkles, Trophy } from 'lucide-vue-next'
import { getServiceLabel } from '../composables/useServiceLabel'
import { formatDate as formatLocalizedDate } from '../utils/formatDate'

const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const hotelStore = useHotelStore()
const bookingStore = useBookingStore()
const authStore = useAuthStore()

const hotel = computed(() => hotelStore.currentHotel)
const hotelReviews = ref([])
const activePhoto = ref('')
const today = new Date().toISOString().split('T')[0]
const activityTracked = ref(false)

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

function hotelImage(item) {
  const photos = Array.isArray(item?.photos) ? item.photos.filter(Boolean) : []
  const pool = photos.length ? photos : hotelFallbacks
  return pool[seedIndex(item?._id || item?.nom, pool.length)]
}

function onImageError(event) {
  if (!event?.target) return
  event.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
}

const galleryPhotos = computed(() => {
  const photos = Array.isArray(hotel.value?.photos) ? hotel.value.photos.filter(Boolean) : []
  if (!photos.length) {
    return [hotelImage(hotel.value)]
  }
  return photos.slice(0, 6)
})

const mainPhoto = computed(() => {
  if (activePhoto.value) {
    return activePhoto.value
  }
  return galleryPhotos.value[0] || hotelImage(hotel.value)
})

const ratingBadgeText = computed(() => {
  const rating = Number(hotel.value?.noteMoyenne || 0)
  if (rating > 0) {
    return `⭐ ${rating.toFixed(1)}/5`
  }

  const stars = Math.max(1, Math.min(5, Number(hotel.value?.etoiles || 0)))
  return stars > 0 ? `${'★'.repeat(stars)} · ${t('hotel.noReviewsYet')}` : t('hotel.noReviewsYet')
})

function localize(field) {
  if (!field) return ''
  if (typeof field === 'string') return field
  if (typeof field === 'object' && !Array.isArray(field)) {
    return field[locale.value] || field.fr || field.en || field.ar || Object.values(field)[0] || ''
  }
  return String(field || '')
}

const localizedHotelName = computed(() => localize(hotel.value?.nom))
const localizedHotelCity = computed(() => localize(hotel.value?.ville))

const localizedHotelDescription = computed(() => localize(hotel.value?.description))

function serviceLabel(service) {
  return getServiceLabel(service, locale.value)
}

const bookForm = reactive({ dateArrivee: '', dateDepart: '', nbVoyageurs: 1 })

async function recordHotelActivity(actionType, room = null) {
  if (!authStore.isAuthenticated || authStore.user?.role !== 'client') {
    return
  }

  const hotelId = String(hotel.value?._id || route.params.id || '')
  if (!hotelId) {
    return
  }

  const categoryTags = [
    String(hotel.value?.ville || ''),
    ...(Array.isArray(hotel.value?.equipements) ? hotel.value.equipements : []),
    ...(Array.isArray(hotel.value?.services) ? hotel.value.services : []),
    room?.type ? String(room.type) : '',
  ].filter(Boolean)

  try {
    await api.post('/record-activity', {
      hotel_id: hotelId,
      action_type: actionType,
      category_tags: categoryTags,
    })
  } catch {
    // Tracking must not block the UX.
  }
}

function selectRoom(room) {
  recordHotelActivity('click', room)
  bookingStore.updateBooking({ chambre: room, hotel: hotel.value, ...bookForm })
  if (!authStore.isAuthenticated || authStore.user?.role !== 'client') {
    const redirectQuery = new URLSearchParams({
      hotel_id: String(hotel.value?._id || ''),
      room_id: String(room?._id || ''),
      dateArrivee: bookForm.dateArrivee || '',
      dateDepart: bookForm.dateDepart || '',
      nbVoyageurs: String(bookForm.nbVoyageurs || 1),
    })

    const redirectUrl = `/dashboard/client/new-booking?${redirectQuery.toString()}`
    const pendingBooking = {
      hotel_id: String(hotel.value?._id || ''),
      room_id: String(room?._id || ''),
      dateArrivee: bookForm.dateArrivee || '',
      dateDepart: bookForm.dateDepart || '',
      nbVoyageurs: Number(bookForm.nbVoyageurs || 1),
    }

    sessionStorage.setItem('postLoginRedirect', redirectUrl)
    sessionStorage.setItem('pendingBooking', JSON.stringify(pendingBooking))
    localStorage.setItem('pendingBooking', JSON.stringify(pendingBooking))

    router.push({ path: '/login', query: { redirect: redirectUrl } })
    return
  }
  router.push({
    path: '/dashboard/client/new-booking',
    query: {
      hotel_id: String(hotel.value?._id || ''),
      room_id: String(room?._id || ''),
      dateArrivee: bookForm.dateArrivee || undefined,
      dateDepart: bookForm.dateDepart || undefined,
      nbVoyageurs: String(bookForm.nbVoyageurs || 1),
    },
  })
}

async function loadReviews() {
  const hotelId = String(route.params.id || '')
  if (!hotelId) {
    hotelReviews.value = []
    return
  }

  try {
    const { data } = await api.get(`/hotels/${hotelId}/reviews`)
    hotelReviews.value = Array.isArray(data) ? data : []
  } catch {
    hotelReviews.value = []
  }
}

function formatDisplayDate(d) {
  return formatLocalizedDate(d, locale.value)
}

watch(
  () => hotel.value?._id,
  () => {
    activePhoto.value = ''
  },
)

onMounted(async () => {
  bookForm.dateArrivee = typeof route.query.dateArrivee === 'string' ? route.query.dateArrivee : ''
  bookForm.dateDepart = typeof route.query.dateDepart === 'string' ? route.query.dateDepart : ''
  const guests = Number(route.query.nbVoyageurs || 1)
  bookForm.nbVoyageurs = Number.isFinite(guests) && guests > 0 ? guests : 1

  await hotelStore.fetchHotel(route.params.id)
  await hotelStore.fetchChambres(route.params.id, bookForm.dateArrivee || undefined, bookForm.dateDepart || undefined)
  await loadReviews()

  if (!activityTracked.value) {
    activityTracked.value = true
    recordHotelActivity('view')
  }
})
</script>

