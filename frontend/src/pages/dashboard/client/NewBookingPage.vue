<template>
  <div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('dashboard.new_booking') }}</h2>
    <p v-if="bookingError" class="mb-4 text-sm text-red-600">{{ bookingError }}</p>
    <p v-if="bookingSuccess" class="mb-4 text-sm text-green-600">{{ bookingSuccess }}</p>

    <div class="flex items-center gap-2 mb-8">
      <div v-for="(step, i) in steps" :key="i" class="flex items-center gap-2 flex-1">
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 transition-all', isStepCompleted(i) ? 'bg-green-500 text-white' : isStepActive(i) ? 'bg-secondary text-white shadow-lg scale-110' : 'bg-gray-200 text-gray-500']">
          <span v-if="isStepCompleted(i)">✓</span>
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span :class="['text-sm font-medium hidden md:block', isStepActive(i) ? 'text-secondary' : 'text-gray-400']">{{ step }}</span>
        <div v-if="i < steps.length - 1" :class="['h-0.5 flex-1', isStepCompleted(i) ? 'bg-green-500' : 'bg-gray-200']"></div>
      </div>
    </div>

    <div v-if="isRoomPreselected && booking.hotel && booking.chambre" class="rounded-2xl border border-secondary/20 bg-white p-4 shadow-sm mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <img :src="hotelPhoto" :alt="booking.hotel.nom" class="w-full sm:w-32 h-24 object-cover rounded-xl" />
        <div class="flex-1">
          <p class="font-bold text-gray-900 text-lg">{{ booking.hotel.nom }} <span class="text-amber-500">{{ '★'.repeat(Number(booking.hotel.etoiles || 0)) }}</span></p>
          <p class="text-sm text-gray-600">{{ booking.hotel.ville }}</p>
          <div class="my-2 h-px bg-gray-200"></div>
          <p class="text-sm text-gray-700">🛏️ {{ booking.chambre.nom }} · {{ booking.chambre.maxVoyageurs || booking.chambre.capacite || 1 }} {{ t('booking.travelersMax') }}</p>
          <p class="text-sm font-semibold text-secondary">{{ booking.chambre.prix_base }}€/{{ $t('hotels.perNight') }}</p>
          <button type="button" class="mt-2 text-xs text-secondary hover:underline" @click="changeRoom">{{ t('booking.changeRoom') }}</button>
        </div>
      </div>
    </div>

    <div v-if="currentStep === 2" class="card space-y-4">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.stay_details') }}</h3>
      <div class="bg-gray-50 rounded-xl p-4 mb-4">
        <p class="font-semibold">{{ booking.hotel?.nom }} · {{ booking.chambre?.nom }}</p>
        <p class="text-sm text-gray-500">{{ formatDisplayDate(booking.dateArrivee) }} → {{ formatDisplayDate(booking.dateDepart) }} ({{ $t('booking.nightsCount', { count: nightCount }) }})</p>
        <p class="text-lg font-bold text-secondary mt-1">{{ totalPrice }}€</p>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.checkin') }}</label>
          <input v-model="booking.dateArrivee" type="date" :min="today" class="input-field" />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.checkout') }}</label>
          <input v-model="booking.dateDepart" type="date" :min="booking.dateArrivee || today" class="input-field" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.guests_count') }}</label>
        <input v-model="booking.nbVoyageurs" type="number" min="1" :max="booking.chambre?.maxVoyageurs || 10" class="input-field" />
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.special_requests') }}</label>
        <textarea v-model="booking.demandesSpeciales" rows="3" class="input-field" :placeholder="$t('booking.specialRequestsPlaceholder')"></textarea>
      </div>
      <div class="flex gap-3 justify-between mt-4">
        <button @click="goBackFromDetails" class="btn-outline">{{ $t('common.back') }}</button>
        <button @click="nextStep" class="btn-primary">{{ $t('dashboard.next') }}</button>
      </div>
    </div>

    <div v-if="currentStep === 3" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('booking.services') }}</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label v-for="svc in availableServices" :key="svc.id" :class="['flex items-center gap-3 border-2 rounded-xl p-3 cursor-pointer transition-colors', booking.servicesChoisis?.includes(svc.id) ? 'border-secondary bg-blue-50' : 'border-gray-200 hover:border-gray-300']">
          <input type="checkbox" :value="svc.id" v-model="booking.servicesChoisis" class="hidden" />
          <span class="text-2xl">{{ svc.icon }}</span>
          <div class="flex-1">
            <p class="font-medium text-gray-800 text-sm">{{ serviceDisplayName(svc) }}</p>
            <p class="text-secondary font-bold text-sm">{{ svc.prix }}€</p>
          </div>
          <div v-if="booking.servicesChoisis?.includes(svc.id)" class="text-secondary">✓</div>
        </label>
      </div>
      <div class="flex gap-3 justify-between mt-6">
        <button @click="currentStep--" class="btn-outline">{{ $t('common.back') }}</button>
        <button @click="nextStep" class="btn-primary">{{ $t('dashboard.next') }}</button>
      </div>
    </div>

    <div v-if="currentStep === 4" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('booking.stepPayment') }}</h3>
      <div class="booking-summary-card rounded-xl p-5 mb-5">
        <h4 class="booking-summary-title mb-3">{{ $t('booking.summary') }}</h4>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="booking-summary-muted">{{ $t('booking.roomNights', { count: nightCount }) }}</span><span class="booking-summary-text">{{ chambreTotal }}€</span></div>
          <div v-if="booking.servicesChoisis?.length" class="flex justify-between"><span class="booking-summary-muted">{{ $t('booking.services') }}</span><span class="booking-summary-text">{{ servicesTotal }}€</span></div>
          <div v-if="promoDiscount > 0" class="flex justify-between text-green-600"><span>{{ $t('booking.promoCode') }} ({{ booking.codePromo }})</span><span>-{{ promoDiscount }}€</span></div>
          <div class="border-t pt-2 flex justify-between font-bold text-lg booking-summary-total"><span>{{ $t('booking.total') }}</span><span>{{ totalPrice }}€</span></div>
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('booking.promoCode') }}</label>
        <div class="flex gap-2">
          <input v-model="booking.codePromo" type="text" placeholder="PROMO2025" class="input-field flex-1" />
          <button @click="applyPromo" class="btn-outline px-4">{{ $t('common.apply') }}</button>
        </div>
        <p v-if="promoMessage" :class="['text-xs mt-1', promoMessage.ok ? 'text-green-600' : 'text-red-500']">{{ promoMessage.text }}</p>
      </div>

      <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
        <input v-model="paymentForm.cardNumber" class="input-field" placeholder="4242 4242 4242 4242" />
        <input v-model="paymentForm.expiry" class="input-field" placeholder="12/26" />
        <input v-model="paymentForm.cvc" class="input-field" placeholder="123" />
      </div>
      <button @click="confirmBooking" :disabled="bookingStore.loading" class="btn-primary w-full py-3 text-base">
        {{ bookingStore.loading ? $t('booking.processing') : `${$t('booking.confirmAndPay')} ${totalPrice}€` }}
      </button>
      <button @click="currentStep--" class="btn-outline w-full mt-3">{{ $t('common.back') }}</button>
    </div>

    <div v-if="currentStep === 5" class="card text-center py-12">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-700 mb-2">{{ $t('booking.confirmedTitle') }}</h3>
      <p class="text-gray-600 mb-2">{{ $t('booking.confirmedSubtitle') }}</p>
      <p class="text-sm text-gray-500 mb-6">{{ $t('booking.reference') }}: <strong>{{ createdRef }}</strong></p>
      <div class="flex gap-3 justify-center">
        <RouterLink to="/dashboard/client/reservations" class="btn-primary">{{ $t('booking.viewMyReservations') }}</RouterLink>
        <RouterLink to="/dashboard/client/overview" class="btn-outline">{{ $t('nav.dashboard') }}</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBookingStore } from '../../../stores/booking'
import { useHotelStore } from '../../../stores/hotel'
import api from '../../../api'
import { getServiceLabel } from '../../../composables/useServiceLabel'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const route = useRoute()
const router = useRouter()
const bookingStore = useBookingStore()
const hotelStore = useHotelStore()
const { t, locale } = useI18n()
const isRoomPreselected = ref(false)

const steps = computed(() => [
  t('booking.stepRoom'),
  t('booking.stepDetails'),
  t('booking.services'),
  t('booking.stepPayment'),
])
const currentStep = ref(2)
const createdRef = ref('')
const promoMessage = ref(null)
const promoDiscount = ref(0)
const bookingError = ref('')
const bookingSuccess = ref('')
const paymentForm = reactive({ cardNumber: '', expiry: '', cvc: '' })

const today = new Date().toISOString().split('T')[0]
const booking = bookingStore.currentBooking
const availableServices = ref([])

const nightCount = computed(() => {
  if (!booking.dateArrivee || !booking.dateDepart) return 1
  return Math.max(1, Math.ceil((new Date(booking.dateDepart) - new Date(booking.dateArrivee)) / 86400000))
})
const chambreTotal = computed(() => (booking.chambre?.prix_base || 0) * nightCount.value)
const servicesTotal = computed(() => {
  return (booking.servicesChoisis || []).reduce((sum, id) => {
    const service = availableServices.value.find((s) => s.id === id)
    if (!service) return sum
    return sum + (service.perNight ? (service.prix * nightCount.value) : service.prix)
  }, 0)
})
const totalPrice = computed(() => chambreTotal.value + servicesTotal.value - promoDiscount.value)

const hotelPhoto = computed(() => {
  const photos = Array.isArray(booking.hotel?.photos) ? booking.hotel.photos.filter(Boolean) : []
  return photos[0] || 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
})

function formatDisplayDate(d) {
  return formatLocalizedDate(d, locale.value, { day: 'numeric', month: 'short' }) || '-'
}

function serviceDisplayName(service) {
  const raw = String(service?.id || service?.nom || '').toLowerCase()
  const translated = getServiceLabel(raw, locale.value)
  if (translated && translated !== raw) return translated
  return service?.nom || service?.id || ''
}

function isStepCompleted(index) {
  if (index === 0 && isRoomPreselected.value) return true
  return currentStep.value > index + 1
}

function isStepActive(index) {
  if (index === 0 && isRoomPreselected.value) return false
  return currentStep.value === index + 1
}

function nextStep() {
  bookingError.value = ''

  if (currentStep.value === 2) {
    const inDate = new Date(booking.dateArrivee)
    const outDate = new Date(booking.dateDepart)
    const now = new Date()
    now.setHours(0, 0, 0, 0)

    if (!(inDate >= now)) {
      bookingError.value = t('booking.arrivalMustBeFuture')
      return
    }
    if (!(outDate > inDate)) {
      bookingError.value = t('booking.departureAfterArrival')
      return
    }

    const capacity = Number(booking.chambre?.maxVoyageurs || booking.chambre?.capacite || 1)
    if (Number(booking.nbVoyageurs || 1) > capacity) {
      bookingError.value = t('booking.roomCapacityMax', { count: capacity })
      return
    }
  }

  currentStep.value++
}

function addDays(isoDate, days) {
  const date = new Date(isoDate)
  if (Number.isNaN(date.getTime())) return isoDate
  date.setDate(date.getDate() + days)
  return date.toISOString().split('T')[0]
}

function changeRoom() {
  const hotelId = String(booking.hotel?._id || '')
  if (!hotelId) return

  router.push({
    path: `/hotels/${hotelId}`,
    query: {
      dateArrivee: booking.dateArrivee || undefined,
      dateDepart: booking.dateDepart || undefined,
      nbVoyageurs: String(booking.nbVoyageurs || 1),
    },
  })
}

function goBackFromDetails() {
  if (isRoomPreselected.value) {
    changeRoom()
    return
  }
  currentStep.value = 1
}

async function loadServices(hotelId = '') {
  const defaultServices = [
    { id: 'breakfast', nom: t('booking.serviceBreakfast'), prix: 15, icon: '🍳', perNight: true },
    { id: 'spa', nom: t('booking.serviceSpaAccess'), prix: 50, icon: '🧖', perNight: false },
    { id: 'airport_transfer', nom: t('booking.serviceAirportShuttle'), prix: 30, icon: '🚗', perNight: false },
    { id: 'room_service', nom: t('booking.serviceRoomService'), prix: 20, icon: '🛎️', perNight: false },
  ]

  try {
    const { data } = await api.get('/services', { params: hotelId ? { hotelId } : {} })
    const rows = Array.isArray(data) ? data : []
    const mapped = rows.map((service) => ({
      id: String(service._id || service.nom),
      nom: service.nom,
      prix: Number(service.prix || 0),
      icon: service.categorie === 'SPA' ? '🧖' : service.categorie === 'RESTAURANT' ? '🍳' : service.categorie === 'ACTIVITE' ? '🚗' : '🛎️',
      perNight: String(service.categorie || '').toUpperCase() === 'RESTAURANT',
    }))

    const merged = [...defaultServices]
    for (const item of mapped) {
      if (!merged.some((s) => s.id === item.id || s.nom.toLowerCase() === String(item.nom || '').toLowerCase())) {
        merged.push(item)
      }
    }

    availableServices.value = merged
  } catch {
    availableServices.value = defaultServices
  }
}

async function applyPromo() {
  if (!booking.codePromo) return

  try {
    const { data } = await api.post('/promotions/validate', { code: booking.codePromo })
    const discountPercent = data.remise_pourcent || 0
    promoDiscount.value = Math.round((chambreTotal.value + servicesTotal.value) * discountPercent) / 100
    promoMessage.value = { ok: true, text: `${t('booking.promoValid')} -${promoDiscount.value}€` }
  } catch {
    promoDiscount.value = 0
    promoMessage.value = { ok: false, text: t('booking.promoInvalid') }
  }
}

async function confirmBooking() {
  try {
    bookingError.value = ''

    const card = String(paymentForm.cardNumber || '').replace(/\s+/g, '')
    if (!card) {
      bookingError.value = t('booking.enterPaymentCard')
      return
    }

    const res = await bookingStore.createReservation({
      chambreId: booking.chambre?._id,
      hotelId: booking.hotel?._id,
      dateArrivee: booking.dateArrivee,
      dateDepart: booking.dateDepart,
      nbVoyageurs: booking.nbVoyageurs,
      demandesSpeciales: booking.demandesSpeciales,
      servicesChoisis: (booking.servicesChoisis || []).map((id) => {
        const service = availableServices.value.find((s) => s.id === id)
        return { id, nom: service?.nom || '', prix: service?.prix || 0 }
      }),
      methodePaiement: 'carte',
      codePromo: booking.codePromo,
    })

    await api.post('/payments/process', { reservationId: res._id, methode: 'carte' })

    createdRef.value = res.reference || res._id
    bookingSuccess.value = t('booking.paymentConfirmed')
    currentStep.value = 5
    bookingStore.resetBooking()
  } catch (e) {
    bookingError.value = e.response?.data?.message || t('booking.reservationError')
  }
}

function getHotelIdFromQuery() {
  const fromHotelId = typeof route.query.hotel_id === 'string' ? route.query.hotel_id : ''
  const fromLegacy = typeof route.query.hotelId === 'string' ? route.query.hotelId : ''
  const pendingRaw = sessionStorage.getItem('pendingBooking') || localStorage.getItem('pendingBooking')
  let pending = null
  try {
    pending = pendingRaw ? JSON.parse(pendingRaw) : null
  } catch {
    pending = null
  }
  const pendingHotelId = String(pending?.hotel_id || pending?.hotelId || '')
  return fromHotelId || fromLegacy || pendingHotelId || String(bookingStore.currentBooking.hotel?._id || '')
}

function getRoomIdFromQuery() {
  const fromRoomId = typeof route.query.room_id === 'string' ? route.query.room_id : ''
  const fromLegacy = typeof route.query.roomId === 'string' ? route.query.roomId : ''
  const pendingRaw = sessionStorage.getItem('pendingBooking') || localStorage.getItem('pendingBooking')
  let pending = null
  try {
    pending = pendingRaw ? JSON.parse(pendingRaw) : null
  } catch {
    pending = null
  }
  const pendingRoomId = String(pending?.room_id || pending?.roomId || '')
  return fromRoomId || fromLegacy || pendingRoomId || String(bookingStore.currentBooking.chambre?._id || '')
}

async function initUnifiedFlow() {
  const hotelId = getHotelIdFromQuery()
  const roomId = getRoomIdFromQuery()

  if (!hotelId) {
    router.replace({ path: '/hotels', query: { booking_message: t('booking.selectHotelFirst') } })
    return
  }

  if (!roomId) {
    router.replace({ path: '/hotels', query: { booking_message: t('booking.selectRoomFirst') } })
    return
  }

  const dateArrivee = typeof route.query.dateArrivee === 'string' ? route.query.dateArrivee : (booking.dateArrivee || '')
  const dateDepartRaw = typeof route.query.dateDepart === 'string' ? route.query.dateDepart : (booking.dateDepart || '')
  const dateDepart = (!dateArrivee || (dateDepartRaw && new Date(dateDepartRaw) > new Date(dateArrivee)))
    ? dateDepartRaw
    : addDays(dateArrivee, 1)
  const nbVoyageursRaw = route.query.nbVoyageurs ?? booking.nbVoyageurs ?? 1
  const nbVoyageurs = Number(nbVoyageursRaw || 1)
  await hotelStore.fetchHotel(hotelId)
  await hotelStore.fetchChambres(hotelId, dateArrivee || undefined, dateDepart || undefined)
  await loadServices(hotelId)

  bookingStore.updateBooking({
    hotel: hotelStore.currentHotel,
    chambre: null,
    dateArrivee,
    dateDepart,
    nbVoyageurs: Number.isFinite(nbVoyageurs) && nbVoyageurs > 0 ? nbVoyageurs : 1,
    demandesSpeciales: booking.demandesSpeciales || '',
    servicesChoisis: booking.servicesChoisis || [],
    codePromo: booking.codePromo || '',
  })

  const selected = hotelStore.chambres.find((room) => String(room._id) === roomId)
  if (!selected) {
    router.replace({ path: '/hotels', query: { booking_message: t('booking.selectRoomFirst') } })
    return
  }

  isRoomPreselected.value = true
  bookingStore.updateBooking({ chambre: selected })

  sessionStorage.removeItem('pendingBooking')
  localStorage.removeItem('pendingBooking')
  currentStep.value = 2
}

onMounted(async () => {
  await initUnifiedFlow()
})
</script>

<style scoped>
.booking-summary-card {
  background: var(--bg-card);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.booking-summary-title {
  color: var(--text-primary);
  font-weight: 700;
}

.booking-summary-text {
  color: var(--text-primary);
}

.booking-summary-muted {
  color: var(--text-secondary);
}

.booking-summary-total {
  color: var(--gold);
  font-weight: 700;
}
</style>
