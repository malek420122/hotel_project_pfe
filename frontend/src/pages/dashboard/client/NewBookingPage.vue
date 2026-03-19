<template>
  <div class="max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ $t('dashboard.new_booking') }}</h2>
    <!-- Steps -->
    <div class="flex items-center gap-2 mb-8">
      <div v-for="(step, i) in steps" :key="i" class="flex items-center gap-2 flex-1">
        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 transition-all',
          currentStep > i+1 ? 'bg-green-500 text-white' : currentStep === i+1 ? 'bg-secondary text-white shadow-lg scale-110' : 'bg-gray-200 text-gray-500']">
          <span v-if="currentStep > i+1">✓</span>
          <span v-else>{{ i+1 }}</span>
        </div>
        <span :class="['text-sm font-medium hidden md:block', currentStep === i+1 ? 'text-secondary' : 'text-gray-400']">{{ step }}</span>
        <div v-if="i < steps.length-1" :class="['h-0.5 flex-1', currentStep > i+1 ? 'bg-green-500' : 'bg-gray-200']"></div>
      </div>
    </div>

    <!-- Step 1: Choose Room -->
    <div v-if="currentStep === 1" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.choose_room') }}</h3>
      <div class="space-y-4 mb-5">
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.destination') }}</label>
          <input v-model="bookForm.ville" type="text" :placeholder="$t('dashboard.city_placeholder')" class="input-field" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.checkin') }}</label>
            <input v-model="bookForm.dateArrivee" type="date" :min="today" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.checkout') }}</label>
            <input v-model="bookForm.dateDepart" type="date" :min="bookForm.dateArrivee || today" class="input-field" />
          </div>
        </div>
        <button @click="searchHotels" class="btn-primary">{{ $t('dashboard.search_rooms') }}</button>
      </div>
      <div v-if="selectedRoom" class="bg-blue-50 rounded-xl p-4 border border-secondary/20">
        <p class="font-semibold text-secondary">✅ Chambre sélectionnée: {{ selectedRoom.nom }}</p>
        <p class="text-sm text-gray-600">{{ selectedRoom.hotel?.nom }} · {{ selectedRoom.prix_base }}€/nuit</p>
      </div>
      <div v-else-if="hotelStore.hotels.length" class="mt-4">
        <HotelCard v-for="hotel in hotelStore.hotels.slice(0,3)" :key="hotel._id" :hotel="hotel" class="mb-3" />
      </div>
      <div class="flex justify-end mt-6">
        <button @click="nextStep" :disabled="!selectedRoom && !bookForm.ville" class="btn-primary disabled:opacity-50">{{ $t('dashboard.next') }}</button>
      </div>
    </div>

    <!-- Step 2: Stay Details -->
    <div v-if="currentStep === 2" class="card space-y-4">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.stay_details') }}</h3>
      <div class="bg-gray-50 rounded-xl p-4 mb-4">
        <p class="font-semibold">{{ booking.hotel?.nom }} · {{ booking.chambre?.nom }}</p>
        <p class="text-sm text-gray-500">{{ formatDate(booking.dateArrivee) }} → {{ formatDate(booking.dateDepart) }} ({{ nightCount }}  nuit(s))</p>
        <p class="text-lg font-bold text-secondary mt-1">{{ totalPrice }}€</p>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.guests_count') }}</label>
        <input v-model="booking.nbVoyageurs" type="number" min="1" :max="booking.chambre?.maxVoyageurs || 10" class="input-field" />
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.special_requests') }}</label>
        <textarea v-model="booking.demandesSpeciales" rows="3" class="input-field" placeholder="Lit bébé, chambre haute, préférences alimentaires..."></textarea>
      </div>
      <div class="flex gap-3 justify-between mt-4">
        <button @click="currentStep--" class="btn-outline">{{ $t('dashboard.back') }}</button>
        <button @click="nextStep" class="btn-primary">{{ $t('dashboard.next') }}</button>
      </div>
    </div>

    <!-- Step 3: Services -->
    <div v-if="currentStep === 3" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.additional_services') }}</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <label v-for="svc in availableServices" :key="svc.id" :class="['flex items-center gap-3 border-2 rounded-xl p-3 cursor-pointer transition-colors', booking.servicesChoisis?.includes(svc.id) ? 'border-secondary bg-blue-50' : 'border-gray-200 hover:border-gray-300']">
          <input type="checkbox" :value="svc.id" v-model="booking.servicesChoisis" class="hidden" />
          <span class="text-2xl">{{ svc.icon }}</span>
          <div class="flex-1">
            <p class="font-medium text-gray-800 text-sm">{{ svc.nom }}</p>
            <p class="text-secondary font-bold text-sm">{{ svc.prix }}€</p>
          </div>
          <div v-if="booking.servicesChoisis?.includes(svc.id)" class="text-secondary">✓</div>
        </label>
      </div>
      <div class="flex gap-3 justify-between mt-6">
        <button @click="currentStep--" class="btn-outline">{{ $t('dashboard.back') }}</button>
        <button @click="nextStep" class="btn-primary">{{ $t('dashboard.next') }}</button>
      </div>
    </div>

    <!-- Step 4: Payment -->
    <div v-if="currentStep === 4" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $t('dashboard.payment') }}</h3>
      <div class="bg-gray-50 rounded-xl p-5 mb-5">
        <h4 class="font-bold text-gray-800 mb-3">Récapitulatif</h4>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-600">Chambre ({{ nightCount }} nuit(s))</span><span>{{ chambreTotal }}€</span></div>
          <div v-if="booking.servicesChoisis?.length" class="flex justify-between"><span class="text-gray-600">Services</span><span>{{ servicesTotal }}€</span></div>
          <div v-if="promoDiscount > 0" class="flex justify-between text-green-600"><span>Code promo ({{ booking.codePromo }})</span><span>-{{ promoDiscount }}€</span></div>
          <div class="border-t pt-2 flex justify-between font-bold text-lg"><span>Total</span><span class="text-secondary">{{ totalPrice }}€</span></div>
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.promo_code') }}</label>
        <div class="flex gap-2">
          <input v-model="booking.codePromo" type="text" placeholder="PROMO2025" class="input-field flex-1" />
          <button @click="applyPromo" class="btn-outline px-4">{{ $t('dashboard.apply') }}</button>
        </div>
        <p v-if="promoMessage" :class="['text-xs mt-1', promoMessage.ok ? 'text-green-600' : 'text-red-500']">{{ promoMessage.text }}</p>
      </div>
      <button @click="confirmBooking" :disabled="bookingStore.loading" class="btn-primary w-full py-3 text-base">
        {{ bookingStore.loading ? 'Traitement...' : `💳 Confirmer et payer ${totalPrice}€` }}
      </button>
      <button @click="currentStep--" class="btn-outline w-full mt-3">{{ $t('dashboard.back') }}</button>
    </div>

    <!-- Success -->
    <div v-if="currentStep === 5" class="card text-center py-12">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-700 mb-2">Réservation confirmée !</h3>
      <p class="text-gray-600 mb-2">Votre réservation a été créée avec succès.</p>
      <p class="text-sm text-gray-500 mb-6">Réf: <strong>{{ createdRef }}</strong></p>
      <div class="flex gap-3 justify-center">
        <RouterLink to="/portal/reservations" class="btn-primary">Voir mes réservations</RouterLink>
        <RouterLink to="/portal/overview" class="btn-outline">Portail client</RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useBookingStore } from '../../../stores/booking'
import { useHotelStore } from '../../../stores/hotel'
import api from '../../../api'
import HotelCard from '../../../components/HotelCard.vue'

const bookingStore = useBookingStore()
const hotelStore = useHotelStore()

const steps = ['Choisir chambre', 'Détails séjour', 'Services', 'Paiement']
const currentStep = ref(1)
const createdRef = ref('')
const promoMessage = ref(null)
const promoDiscount = ref(0)

const today = new Date().toISOString().split('T')[0]
const bookForm = reactive({ ville: '', dateArrivee: '', dateDepart: '' })
const selectedRoom = ref(bookingStore.currentBooking.chambre)
const booking = bookingStore.currentBooking

const availableServices = ref([])

const nightCount = computed(() => {
  if (!booking.dateArrivee || !booking.dateDepart) return 1
  return Math.max(1, Math.ceil((new Date(booking.dateDepart) - new Date(booking.dateArrivee)) / 86400000))
})
const chambreTotal = computed(() => (booking.chambre?.prix_base || 0) * nightCount.value)
const servicesTotal = computed(() => (booking.servicesChoisis || []).reduce((sum, id) => sum + (availableServices.find(s => s.id === id)?.prix || 0), 0))
const totalPrice = computed(() => chambreTotal.value + servicesTotal.value - promoDiscount.value)

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

function nextStep() { currentStep.value++ }

async function searchHotels() { await hotelStore.fetchHotels({ ville: bookForm.ville, dateArrivee: bookForm.dateArrivee, dateDepart: bookForm.dateDepart }) }

async function applyPromo() {
  if (!booking.codePromo) return
  try {
    const { data } = await api.post('/promotions/validate', { code: booking.codePromo })
    const remisePourcent = data.remise_pourcent || 0
    promoDiscount.value = Math.round(totalPrice.value * remisePourcent / 100 * 100) / 100
    promoMessage.value = { ok: true, text: `Code valide ! -${remisePourcent}%` }
  } catch {
    promoDiscount.value = 0
    promoMessage.value = { ok: false, text: 'Code promo invalide ou expiré' }
  }
}

async function confirmBooking() {
  try {
    const servicesPayload = (booking.servicesChoisis || []).map(id => {
      const svc = availableServices.find(s => s.id === id)
      return { id, nom: svc?.nom || id, prix: svc?.prix || 0 }
    })
    const res = await bookingStore.createReservation({
      chambreId: booking.chambre?._id,
      hotelId: booking.hotel?._id,
      dateArrivee: booking.dateArrivee,
      dateDepart: booking.dateDepart,
      nbVoyageurs: booking.nbVoyageurs,
      demandesSpeciales: booking.demandesSpeciales,
      servicesChoisis: servicesPayload,
      codePromo: booking.codePromo,
      methodePaiement: 'carte',
    })

    // Try creating a Stripe payment intent; if Stripe isn't configured, backend returns stub mode.
    let paymentIntentId = null
    try {
      const { data: intentData } = await api.post('/payments/create-intent', { reservationId: res._id })
      paymentIntentId = intentData?.paymentIntentId || null
    } catch {
      paymentIntentId = null
    }

    await bookingStore.processPayment(res._id, 'carte', paymentIntentId)

    createdRef.value = res.reference || res._id
    currentStep.value = 5
    bookingStore.resetBooking()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors de la réservation')
  }
}

onMounted(async () => {
  if (bookingStore.currentBooking.chambre) {
    currentStep.value = 2
    try {
        const { data } = await api.get('/admin/services', { params: { hotelId: bookingStore.currentBooking.hotel?._id } })
        availableServices.value = data.map(s => ({
            id: s._id,
            nom: s.nom,
            icon: s.categorie === 'SPA' ? '🧖' : (s.categorie === 'RESTAURANT' ? '🍳' : '✨'),
            prix: s.prix
        }))
    } catch (e) {
        console.error("Failed to load services", e)
    }
  }
})
</script>
