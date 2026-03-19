<template>
  <div>
    <Navbar />
    <div class="pt-20">
      <div v-if="hotelStore.loading" class="flex justify-center py-32">
        <div class="w-12 h-12 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
      </div>
      <div v-else-if="hotel">
        <!-- Hero -->
        <div class="relative h-80 overflow-hidden">
          <img :src="hotel.photos?.[0] || '/placeholder-hotel.jpg'" :alt="hotel.nom"
            class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
          <div class="absolute bottom-6 left-8 text-white">
            <h1 class="text-4xl font-extrabold mb-1">{{ hotel.nom }}</h1>
            <p class="text-lg text-white/80">📍 {{ hotel.adresse }}, {{ hotel.ville }}</p>
            <div class="flex items-center gap-3 mt-2">
              <div class="flex gap-0.5">
                <span v-for="i in 5" :key="i" :class="i <= hotel.etoiles ? 'text-accent' : 'text-white/40'" class="text-xl">★</span>
              </div>
              <span class="bg-secondary text-white px-3 py-1 rounded-lg font-bold">⭐ {{ hotel.noteMoyenne?.toFixed(1) || '—' }}/5</span>
            </div>
          </div>
        </div>

        <!-- Photo Gallery -->
        <div v-if="hotel.photos?.length > 1" class="max-w-7xl mx-auto px-6 mt-4 flex gap-2 overflow-x-auto pb-2">
          <img v-for="(photo, i) in hotel.photos.slice(1,6)" :key="i" :src="photo"
            class="h-24 w-40 object-cover rounded-xl cursor-pointer hover:opacity-90 flex-shrink-0"
            @click="activePhoto = photo" />
        </div>

        <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Description -->
            <div class="card">
              <h2 class="text-xl font-bold text-primary mb-3">À propos</h2>
              <p class="text-gray-600">{{ hotel.description }}</p>
            </div>

            <!-- Amenities -->
            <div v-if="hotel.equipements?.length" class="card">
              <h2 class="text-xl font-bold text-primary mb-4">Équipements</h2>
              <div class="flex flex-wrap gap-2">
                <span v-for="eq in hotel.equipements" :key="eq"
                  class="bg-secondary/10 text-secondary px-3 py-1 rounded-full text-sm font-medium">
                  {{ eq }}
                </span>
              </div>
            </div>

            <!-- Map -->
            <div v-if="hotel.latitude && hotel.longitude" class="card">
              <h2 class="text-xl font-bold text-primary mb-4">Localisation</h2>
              <HotelMap :hotel="hotel" />
            </div>

            <!-- Rooms -->
            <div>
              <h2 class="text-2xl font-bold text-primary mb-4">Chambres disponibles</h2>
              <div class="space-y-4">
                <RoomCard v-for="room in hotelStore.chambres" :key="room._id" :room="room"
                  @reserve="selectRoom(room)" />
              </div>
              <div v-if="!hotelStore.chambres.length" class="card text-center text-gray-400">
                <p class="text-3xl mb-2">🛏️</p>
                <p>Aucune chambre disponible pour vos dates</p>
              </div>
            </div>

            <!-- Reviews -->
            <div v-if="hotel.avis?.length" class="card">
              <h2 class="text-xl font-bold text-primary mb-4">Avis clients ({{ hotel.avis.length }})</h2>
              <div class="space-y-4">
                <div v-for="avis in hotel.avis.slice(0,5)" :key="avis._id" class="border-b pb-4 last:border-0">
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 bg-secondary rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                      {{ avis.client?.prenom?.[0] || 'A' }}
                    </div>
                    <div class="flex-1">
                      <div class="flex justify-between">
                        <p class="font-semibold text-gray-800">{{ avis.client?.prenom }} {{ avis.client?.nom }}</p>
                        <div class="flex gap-0.5">
                          <span v-for="i in 5" :key="i" :class="i <= avis.note ? 'text-accent' : 'text-gray-200'" class="text-sm">★</span>
                        </div>
                      </div>
                      <p class="text-gray-600 text-sm mt-1">{{ avis.commentaire }}</p>
                      <p class="text-xs text-gray-400 mt-1">{{ formatDate(avis.createdAt) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Booking Widget -->
          <div class="lg:col-span-1">
            <div class="card sticky top-24">
              <h3 class="text-lg font-bold text-primary mb-4">Réserver votre séjour</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1">Arrivée</label>
                  <input v-model="bookForm.dateArrivee" type="date" :min="today" class="input-field" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1">Départ</label>
                  <input v-model="bookForm.dateDepart" type="date" :min="bookForm.dateArrivee || today" class="input-field" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1">Voyageurs</label>
                  <input v-model="bookForm.nbVoyageurs" type="number" min="1" max="20" class="input-field" />
                </div>
              </div>
              <button @click="loadRooms" class="btn-primary w-full mt-4">Voir les chambres disponibles</button>
              <div v-if="hotel.prix_min" class="mt-4 p-3 bg-blue-50 rounded-xl text-center">
                <p class="text-sm text-gray-500">À partir de</p>
                <p class="text-2xl font-bold text-secondary">{{ hotel.prix_min }}€<span class="text-sm font-normal text-gray-500">/nuit</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHotelStore } from '../stores/hotel'
import { useBookingStore } from '../stores/booking'
import Navbar from '../components/Navbar.vue'
import HotelMap from '../components/HotelMap.vue'
import RoomCard from '../components/RoomCard.vue'

const route = useRoute()
const router = useRouter()
const hotelStore = useHotelStore()
const bookingStore = useBookingStore()

const hotel = computed(() => hotelStore.currentHotel)
const today = new Date().toISOString().split('T')[0]

const bookForm = reactive({ dateArrivee: '', dateDepart: '', nbVoyageurs: 1 })

function loadRooms() {
  hotelStore.fetchChambres(route.params.id, bookForm.dateArrivee, bookForm.dateDepart)
}

function selectRoom(room) {
  bookingStore.updateBooking({ chambre: room, hotel: hotel.value, ...bookForm })
  router.push('/dashboard/client/new-booking')
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(async () => {
  await hotelStore.fetchHotel(route.params.id)
  await hotelStore.fetchChambres(route.params.id)
})
</script>
