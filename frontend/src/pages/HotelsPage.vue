<template>
  <div>
    <Navbar />
    <div class="pt-20">
      <!-- Search header -->
      <div style="background: linear-gradient(135deg, #003580, #0071c2);" class="py-12 text-white">
        <div class="max-w-7xl mx-auto px-4">
          <h1 class="text-3xl font-extrabold mb-6">Trouvez votre hôtel idéal</h1>
          <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 flex flex-col md:flex-row gap-3">
            <input v-model="filters.ville" type="text" placeholder="🏙️ Ville ou destination" class="input-field flex-1 bg-white" @keyup.enter="search" />
            <input v-model="filters.dateArrivee" type="date" class="input-field bg-white md:w-44" />
            <input v-model="filters.dateDepart" type="date" class="input-field bg-white md:w-44" />
            <select v-model="filters.etoiles" class="input-field bg-white md:w-36">
              <option value="">⭐ Étoiles</option>
              <option v-for="i in 5" :key="i" :value="i">{{ '★'.repeat(i) }}</option>
            </select>
            <button @click="search" class="btn-accent px-8">🔍 Rechercher</button>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-6">
          <p class="text-gray-600">
            <span v-if="!hotelStore.loading">{{ hotelStore.hotels.length }} hôtels trouvés</span>
            <span v-else>Recherche en cours...</span>
          </p>
          <div class="flex items-center gap-3">
            <label class="text-sm text-gray-600">Trier par:</label>
            <select v-model="sortBy" @change="sortHotels" class="input-field py-1.5 text-sm w-40">
              <option value="recommande">Recommandé</option>
              <option value="prix_asc">Prix croissant</option>
              <option value="prix_desc">Prix décroissant</option>
              <option value="note">Meilleures notes</option>
            </select>
          </div>
        </div>

        <div v-if="hotelStore.loading" class="flex justify-center py-20">
          <div class="w-12 h-12 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
        </div>
        <div v-else-if="sortedHotels.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <HotelCard v-for="hotel in sortedHotels" :key="hotel._id" :hotel="hotel" />
        </div>
        <div v-else class="text-center py-20">
          <p class="text-5xl mb-4">🔍</p>
          <p class="text-xl text-gray-600 mb-2">Aucun hôtel trouvé</p>
          <p class="text-gray-400">Essayez avec d'autres critères de recherche</p>
          <button @click="resetFilters" class="btn-primary mt-6">Réinitialiser les filtres</button>
        </div>

        <!-- Pagination -->
        <div v-if="hotelStore.pagination.last_page > 1" class="flex justify-center gap-2 mt-10">
          <button v-for="p in hotelStore.pagination.last_page" :key="p" @click="goToPage(p)"
            :class="['w-10 h-10 rounded-xl font-semibold text-sm transition-colors', p === hotelStore.pagination.current_page ? 'bg-secondary text-white' : 'bg-white border text-gray-700 hover:bg-gray-50']">
            {{ p }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useHotelStore } from '../stores/hotel'
import Navbar from '../components/Navbar.vue'
import HotelCard from '../components/HotelCard.vue'

const route = useRoute()
const router = useRouter()
const hotelStore = useHotelStore()
const sortBy = ref('recommande')

const filters = reactive({
  ville: route.query.ville || '',
  dateArrivee: route.query.dateArrivee || '',
  dateDepart: route.query.dateDepart || '',
  etoiles: route.query.etoiles || '',
})

const sortedHotels = computed(() => {
  const hotels = [...hotelStore.hotels]
  if (sortBy.value === 'prix_asc') return hotels.sort((a, b) => (a.prix_min || 0) - (b.prix_min || 0))
  if (sortBy.value === 'prix_desc') return hotels.sort((a, b) => (b.prix_min || 0) - (a.prix_min || 0))
  if (sortBy.value === 'note') return hotels.sort((a, b) => (b.noteMoyenne || 0) - (a.noteMoyenne || 0))
  return hotels
})

function search() {
  hotelStore.fetchHotels({ ...filters })
  router.replace({ query: { ...filters } })
}

function resetFilters() {
  filters.ville = ''
  filters.dateArrivee = ''
  filters.dateDepart = ''
  filters.etoiles = ''
  search()
}

function sortHotels() { /* sortedHotels computed handles it */ }

function goToPage(p) {
  hotelStore.fetchHotels({ ...filters, page: p })
}

onMounted(() => {
  search()
})
</script>
