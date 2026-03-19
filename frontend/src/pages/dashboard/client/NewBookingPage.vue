<template>
  <div class="max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Nouvelle réservation</h2>
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
      <h3 class="text-lg font-bold text-gray-800 mb-4">Choisir un hôtel et une chambre</h3>
      <div class="space-y-4 mb-5">
        <div>
          <label class="block text-sm font-semibold text-gray-600 mb-1">Destination</label>
          <input v-model="bookForm.ville" type="text" placeholder="Ville..." class="input-field" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Arrivée</label>
            <input v-model="bookForm.dateArrivee" type="date" :min="today" class="input-field" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-600 mb-1">Départ</label>
            <input v-model="bookForm.dateDepart" type="date" :min="bookForm.dateArrivee || today" class="input-field" />
          </div>
        </div>
        <button @click="searchHotels" class="btn-primary">Rechercher les chambres</button>
      </div>
      <div v-if="selectedRoom" class="bg-blue-50 rounded-xl p-4 border border-secondary/20">
        <p class="font-semibold text-secondary">✅ Chambre sélectionnée: {{ selectedRoom.nom }}</p>
        <p class="text-sm text-gray-600">{{ selectedRoom.hotel?.nom }} · {{ selectedRoom.prix_base }}€/nuit</p>
      </div>
      <div v-else-if="hotelStore.hotels.length" class="mt-4">
        <HotelCard v-for="hotel in hotelStore.hotels.slice(0,3)" :key="hotel._id" :hotel="hotel" class="mb-3" />
      </div>
      <div class="flex justify-end mt-6">
        <button @click="nextStep" :disabled="!selectedRoom && !bookForm.ville" class="btn-primary disabled:opacity-50">
          Suivant →
        </button>
      </div>
    </div>

    <!-- Step 2: Stay Details -->
    <div v-if="currentStep === 2" class="card space-y-4">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Détails du séjour</h3>
      <div class="bg-gray-50 rounded-xl p-4 mb-4">
        <p class="font-semibold">{{ booking.hotel?.nom }} · {{ booking.chambre?.nom }}</p>
        <p class="text-sm text-gray-500">{{ formatDate(booking.dateArrivee) }} → {{ formatDate(booking.dateDepart) }} ({{ nightCount }}  nuit(s))</p>
        <p class="text-lg font-bold text-secondary mt-1">{{ totalPrice }}€</p>
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">Nombre de voyageurs</label>
        <input v-model="booking.nbVoyageurs" type="number" min="1" :max="booking.chambre?.maxVoyageurs || 10" class="input-field" />
      </div>
      <div>
        <label class="block text-sm font-semibold text-gray-600 mb-1">Demandes spéciales</label>
        <textarea v-model="booking.demandesSpeciales" rows="3" class="input-field" placeholder="Lit bébé, chambre haute, préférences alimentaires..."></textarea>
      </div>
      <div class="flex gap-3 justify-between mt-4">
        <button @click="currentStep--" class="btn-outline">← Retour</button>
        <button @click="nextStep" class="btn-primary">Suivant →</button>
      </div>
    </div>

    <!-- Step 3: Services -->
    <div v-if="currentStep === 3" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Services supplémentaires</h3>
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
        <button @click="currentStep--" class="btn-outline">← Retour</button>
        <button @click="nextStep" class="btn-primary">Suivant →</button>
      </div>
    </div>

    <!-- Step 4: Payment -->
    <div v-if="currentStep === 4" class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Paiement</h3>
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
        <label class="block text-sm font-semibold text-gray-600 mb-1">Code promo</label>
        <div class="flex gap-2">
          <input v-model="booking.codePromo" type="text" placeholder="PROMO2025" class="input-field flex-1" />
          <button @click="applyPromo" class="btn-outline px-4">Appliquer</button>
        </div>
        <p v-if="promoMessage" :class="['text-xs mt-1', promoMessage.ok ? 'text-green-600' : 'text-red-500']">{{ promoMessage.text }}</p>
      </div>
      <button @click="confirmBooking" :disabled="bookingStore.loading" class="btn-primary w-full py-3 text-base">
        {{ bookingStore.loading ? 'Traitement...' : `💳 Confirmer et payer ${totalPrice}€` }}
      </button>
      <button @click="currentStep--" class="btn-outline w-full mt-3">← Retour</button>
    </div>

    <!-- Success -->
    <div v-if="currentStep === 5" class="card text-center py-12">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">✅</div>
      <h3 class="text-2xl font-bold text-green-700 mb-2">Réservation confirmée !</h3>
      <p class="text-gray-600 mb-2">Votre réservation a été créée avec succès.</p>
      <p class="text-sm text-gray-500 mb-6">Réf: <strong>{{ createdRef }}</strong></p>
      <div class="flex gap-3 justify-center">
        <RouterLink to="/dashboard/client/reservations" class="btn-primary">Voir mes réservations</RouterLink>
        <RouterLink to="/dashboard/client/overview" class="btn-outline">Tableau de bord</RouterLink>
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

const availableServices = [
  { id: 'petit_dejeuner', nom: 'Petit-déjeuner', icon: '🍳', prix: 15 },
  { id: 'navette', nom: 'Navette aéroport', icon: '🚗', prix: 25 },
  { id: 'spa', nom: 'Accès spa', icon: '🧖', prix: 30 },
  { id: 'parking', nom: 'Parking', icon: '🚗', prix: 12 },
  { id: 'late_checkout', nom: 'Late checkout', icon: '⏰', prix: 20 },
]

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
    const { data } = await api.post('/codes-promo/valider', { code: booking.codePromo, montant: totalPrice.value })
    promoDiscount.value = data.remise || 0
    promoMessage.value = { ok: true, text: `Code valide ! -${data.remise}€` }
  } catch {
    promoDiscount.value = 0
    promoMessage.value = { ok: false, text: 'Code promo invalide ou expiré' }
  }
}

async function confirmBooking() {
  try {
    const res = await bookingStore.createReservation({
      chambre_id: booking.chambre?._id,
      hotel_id: booking.hotel?._id,
      dateArrivee: booking.dateArrivee,
      dateDepart: booking.dateDepart,
      nbVoyageurs: booking.nbVoyageurs,
      demandesSpeciales: booking.demandesSpeciales,
      services: booking.servicesChoisis,
      codePromo: booking.codePromo,
      prixTotal: totalPrice.value,
    })
    createdRef.value = res.reference || res._id
    currentStep.value = 5
    bookingStore.resetBooking()
  } catch (e) {
    alert(e.response?.data?.message || 'Erreur lors de la réservation')
  }
}

onMounted(() => {
  if (bookingStore.currentBooking.chambre) currentStep.value = 2
})
</script>
