<template>
  <div>
    <Navbar />
    <!-- Hero -->
    <section class="relative min-h-screen flex items-center" style="background: linear-gradient(135deg, #003580 0%, #0071c2 60%, #00509e 100%);">
      <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
          <div v-for="i in 5" :key="i" :class="`absolute rounded-full bg-white`"
            :style="`width:${200+i*100}px;height:${200+i*100}px;top:${10+i*15}%;left:${i*18}%;opacity:0.3`"></div>
        </div>
      </div>
      <div class="relative max-w-7xl mx-auto px-4 py-24 text-white w-full">
        <div class="max-w-3xl">
          <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2 mb-6">
            <span>🏆</span>
            <span class="text-sm font-medium">La plateforme N°1 de réservation hôtelière</span>
          </div>
          <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6">
            Votre séjour<br/><span class="text-accent">idéal</span> vous attend
          </h1>
          <p class="text-xl text-white/80 mb-10">Découvrez des hôtels d'exception partout dans le monde. Réservez en quelques clics, profitez d'un service 5 étoiles.</p>

          <!-- Search Widget -->
          <div class="bg-white rounded-2xl p-2 shadow-2xl flex flex-col md:flex-row gap-2">
            <div class="flex-1 flex items-center gap-3 px-4 py-2">
              <span class="text-2xl">📍</span>
              <div class="flex-1">
                <p class="text-xs font-semibold text-gray-500">Destination</p>
                <input v-model="search.ville" type="text" placeholder="Marrakech, Agadir..." class="w-full outline-none text-gray-800 font-medium" />
              </div>
            </div>
            <div class="hidden md:block w-px bg-gray-200 my-2"></div>
            <div class="flex items-center gap-3 px-4 py-2">
              <span class="text-2xl">📅</span>
              <div>
                <p class="text-xs font-semibold text-gray-500">Arrivée</p>
                <input v-model="search.dateArrivee" type="date" class="outline-none text-gray-800 font-medium" />
              </div>
            </div>
            <div class="hidden md:block w-px bg-gray-200 my-2"></div>
            <div class="flex items-center gap-3 px-4 py-2">
              <span class="text-2xl">📅</span>
              <div>
                <p class="text-xs font-semibold text-gray-500">Départ</p>
                <input v-model="search.dateDepart" type="date" class="outline-none text-gray-800 font-medium" />
              </div>
            </div>
            <div class="hidden md:block w-px bg-gray-200 my-2"></div>
            <div class="flex items-center gap-3 px-4 py-2">
              <span class="text-2xl">👥</span>
              <div>
                <p class="text-xs font-semibold text-gray-500">Voyageurs</p>
                <input v-model="search.nbVoyageurs" type="number" min="1" max="20" class="outline-none text-gray-800 font-medium w-16" />
              </div>
            </div>
            <button @click="doSearch" class="btn-accent px-8 py-3 text-base flex items-center gap-2 rounded-xl">
              🔍 Rechercher
            </button>
          </div>
        </div>
      </div>
      <!-- Stats bar -->
      <div class="absolute bottom-0 left-0 right-0">
        <div class="max-w-7xl mx-auto px-4">
          <div class="bg-white/10 backdrop-blur-sm border-t border-white/20 rounded-t-2xl grid grid-cols-2 md:grid-cols-4 divide-x divide-white/20">
            <div v-for="stat in stats" :key="stat.label" class="px-6 py-4 text-white text-center">
              <p class="text-2xl font-extrabold">{{ stat.value }}</p>
              <p class="text-xs text-white/70">{{ stat.label }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Hotels -->
    <section class="py-20 max-w-7xl mx-auto px-4">
      <div class="text-center mb-12">
        <p class="text-secondary font-semibold mb-2">Nos coups de cœur</p>
        <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Hôtels en vedette</h2>
        <p class="text-gray-500 max-w-2xl mx-auto">Sélectionnés pour leur qualité et leur rapport qualité-prix exceptionnel</p>
      </div>
      <div v-if="hotelStore.loading" class="flex justify-center py-16">
        <div class="w-12 h-12 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <HotelCard v-for="hotel in hotelStore.hotels.slice(0,6)" :key="hotel._id" :hotel="hotel" />
      </div>
      <div class="text-center mt-10">
        <RouterLink to="/hotels" class="btn-outline px-8 py-3 text-base">Voir tous les hôtels →</RouterLink>
      </div>
    </section>

    <!-- Why HotelEase -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-extrabold text-gray-800 mb-4">Pourquoi choisir HotelEase ?</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div v-for="feat in features" :key="feat.title" class="text-center">
            <div class="w-16 h-16 bg-secondary/10 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">{{ feat.icon }}</div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">{{ feat.title }}</h3>
            <p class="text-gray-500 text-sm">{{ feat.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer style="background: #003580;" class="text-white py-12">
      <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">🏨</span>
            <span class="text-xl font-extrabold">HotelEase</span>
          </div>
          <p class="text-white/60 text-sm">La plateforme de réservation hôtelière qui simplifie vos voyages.</p>
        </div>
        <div v-for="col in footerCols" :key="col.title">
          <h4 class="font-bold mb-3">{{ col.title }}</h4>
          <ul class="space-y-2">
            <li v-for="link in col.links" :key="link" class="text-white/60 text-sm hover:text-white cursor-pointer">{{ link }}</li>
          </ul>
        </div>
      </div>
      <div class="border-t border-white/10 mt-8 pt-8 max-w-7xl mx-auto px-4 flex justify-between items-center text-sm text-white/40">
        <p>© 2025 HotelEase. Tous droits réservés.</p>
        <LanguageSwitcher />
      </div>
    </footer>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useHotelStore } from '../stores/hotel'
import Navbar from '../components/Navbar.vue'
import HotelCard from '../components/HotelCard.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'

const router = useRouter()
const hotelStore = useHotelStore()

const search = reactive({ ville: '', dateArrivee: '', dateDepart: '', nbVoyageurs: 1 })
const stats = [
  { value: '500+', label: 'Hôtels partenaires' },
  { value: '50K+', label: 'Clients satisfaits' },
  { value: '30+', label: 'Villes couvertes' },
  { value: '4.8⭐', label: 'Note moyenne' },
]
const features = [
  { icon: '🔒', title: 'Paiement sécurisé', desc: 'Vos transactions sont protégées par un chiffrement SSL 256 bits.' },
  { icon: '⚡', title: 'Réservation rapide', desc: 'Réservez en moins de 3 minutes grâce à notre interface intuitive.' },
  { icon: '💰', title: 'Meilleurs prix', desc: 'Garantie du meilleur tarif ou remboursement de la différence.' },
  { icon: '🎁', title: 'Programme fidélité', desc: 'Accumulez des points à chaque séjour et profitez d\'avantages exclusifs.' },
]
const footerCols = [
  { title: 'Destinations', links: ['Marrakech', 'Casablanca', 'Agadir', 'Rabat', 'Fès'] },
  { title: 'Services', links: ['Réservation', 'Annulation flexible', 'Support 24/7', 'Programme fidélité'] },
  { title: 'Entreprise', links: ['À propos', 'Carrières', 'Partenariats', 'Blog', 'Presse'] },
]

function doSearch() {
  router.push({ path: '/hotels', query: { ...search } })
}

onMounted(() => hotelStore.fetchHotels({ per_page: 6 }))
</script>
