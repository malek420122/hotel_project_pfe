<template>
  <div class="home-premium bg-slate-50 text-slate-900">
    <teleport to="head">
      <title>HotelEase | {{ t('home.heroLine1') }} {{ t('home.heroAccent') }}</title>
      <meta name="description" content="Découvrez l'excellence hôtelière avec HotelEase. Réservez les meilleurs hôtels au meilleur prix." />
    </teleport>
    <Navbar />

    <section class="hero relative min-h-screen pt-24 md:pt-28 pb-16 md:pb-24">
      <div class="hero-overlay absolute inset-0"></div>
      <div class="relative max-w-7xl mx-auto px-4">
        <div class="max-w-4xl text-[#3A1A04]">
          <div class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6 text-[#8B4513]">
            <span class="badge-trophy">🏆</span>
            <span class="text-sm sm:text-base font-semibold">{{ t('hero.badge') }}</span>
          </div>

          <h1 class="hero-title text-4xl sm:text-6xl md:text-7xl leading-tight mb-5">
            <span class="hero-word">{{ t('home.heroLine1') }}</span>
            <span class="hero-word hero-accent">{{ t('home.heroAccent') }}</span>
            <span class="hero-word">{{ t('home.heroLine2') }}</span>
          </h1>

          <p class="text-lg sm:text-xl text-[#3A1A04] max-w-2xl mb-9 font-medium">
            {{ t('hero.subtitle') }}
          </p>

          <div class="relative z-[1000]">
            <div
              :class="['search-glass home-search-shell rounded-3xl p-3 sm:p-4', searchFocused ? 'search-focused' : '']"
              @focusin="searchFocused = true"
              @focusout="searchFocused = false"
            >
            <div class="grid grid-cols-1 md:grid-cols-[1.7fr_1fr_1fr_0.8fr_auto] gap-2">
              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.destination') }}</p>
                <div class="relative">
                  <input
                    v-model="destination"
                    type="text"
                    class="hero-input"
                    :placeholder="t('hero.searchPlaceholder')"
                    @blur="hideDestinationDropdown"
                    @keyup.enter="doSearch"
                  />

                  <div
                    v-if="showDestinationDropdown"
                    class="absolute top-full left-0 w-full mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[9999] overflow-hidden"
                  >
                    <div class="p-2">
                      <button
                        v-for="(item, idx) in destinationSuggestions"
                        :key="`${item.type}-${destinationLabel(item)}-${idx}`"
                        type="button"
                        class="flex items-center justify-between p-3 hover:bg-orange-50 rounded-lg cursor-pointer group w-full"
                        @mousedown.prevent="selectDestinationSuggestion(item)"
                      >
                        <div class="flex items-center">
                          <MapPin class="w-4 h-4 text-gray-400 group-hover:text-orange-500 mr-2" />
                          <span class="text-[#2D1B08] font-medium">{{ destinationLabel(item) }}</span>
                        </div>
                        <span class="text-[10px] px-2 py-1 bg-orange-100 text-orange-600 rounded font-bold uppercase">{{ item.type === 'hotel' ? 'Hôtel' : 'Ville' }}</span>
                      </button>

                      <div v-if="!destinationSuggestions.length" class="p-3 italic text-sm text-gray-500">
                        Aucun résultat
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.checkin') }}</p>
                <div class="relative">
                  <input v-model="search.dateArrivee" type="date" class="premium-native-date hero-input" />
                </div>
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.checkout') }}</p>
                <div class="relative">
                  <input v-model="search.dateDepart" type="date" class="premium-native-date hero-input" />
                </div>
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.travelers') }}</p>
                <div class="traveler-control flex items-center justify-between px-2 bg-slate-50/50 rounded-xl border border-slate-100 transition-all focus-within:border-[#D4820A]">
                  <button type="button" class="traveler-btn w-8 h-8 rounded-lg hover:bg-slate-200 transition-colors" @click="decreaseTravelers">-</button>
                  <input v-model.number="search.nbVoyageurs" type="number" min="1" max="20" class="hero-input traveler-input text-center flex-1 !border-none !bg-transparent" />
                  <button type="button" class="traveler-btn w-8 h-8 rounded-lg hover:bg-slate-200 transition-colors" @click="increaseTravelers">+</button>
                </div>
              </div>

              <div class="flex items-center">
                <button @click="doSearch" class="search-cta w-full md:w-auto px-8 py-3 rounded-full text-sm sm:text-base font-bold">
                  {{ t('searchbar.searchButton') }}
                </button>
              </div>
            </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section ref="statsSectionRef" class="-mt-10 relative z-10 pb-8">
      <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
          <article v-for="card in statCards" :key="card.key" class="stat-card">
            <div class="stat-icon-shell">
              <component :is="card.icon" class="stat-icon" :size="40" stroke-width="2.25" />
            </div>
            <p class="stat-value">
              <span>{{ card.valueText }}</span><span v-if="card.valueSuffix" class="stat-value-suffix">{{ card.valueSuffix }}</span>
            </p>
            <p class="stat-label">{{ card.label }}</p>
          </article>
        </div>
      </div>
    </section>

    <section class="py-16 why-premium">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
          <p class="why-kicker">{{ t('home.whyTag') }}</p>
          <h2 class="why-heading">{{ t('home.whyTitlePremium') }}</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="why-card">
            <div class="why-icon-box why-icon-box--trophy">
              <Trophy :size="26" stroke-width="2.2" />
            </div>
            <h3 class="why-title">{{ t('home.why1Title') }}</h3>
            <p class="why-text">{{ t('home.why1Text') }}</p>
          </div>
          <div class="why-card">
            <div class="why-icon-box why-icon-box--lock">
              <Lock :size="24" stroke-width="2.2" />
            </div>
            <h3 class="why-title">{{ t('home.why2Title') }}</h3>
            <p class="why-text">{{ t('home.why2Text') }}</p>
          </div>
          <div class="why-card">
            <div class="why-icon-box why-icon-box--star">
              <Star :size="26" stroke-width="2.2" />
            </div>
            <h3 class="why-title">{{ t('home.why3Title') }}</h3>
            <p class="why-text">{{ t('home.why3Text') }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="py-16 max-w-7xl mx-auto px-4">
      <div class="text-center mb-12">
        <p class="text-amber-500 font-semibold uppercase tracking-[0.2em] text-xs">{{ t('hotelsSection.subtitle') }}</p>
        <h2 class="font-display text-4xl sm:text-5xl text-slate-900 mt-3">{{ t('hotelsSection.title') }}</h2>
        <div class="header-underline"></div>
      </div>

      <div v-if="hotelStore.loading" class="flex justify-center py-16">
        <div class="w-12 h-12 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else class="featured-wrap">
        <RouterLink
          v-for="(item, index) in featuredHotels"
          :key="item._id || item.nom || index"
          :to="`/hotels/${item._id}`"
          :class="['featured-card group', cardLayoutClass(index)]"
        >
          <img :src="hotelImage(item)" :alt="item.nom" loading="lazy" class="featured-image" @error="onImageError" />
          <div class="featured-gradient"></div>

          <div class="featured-top">
            <span class="featured-stars">{{ starString(item.etoiles) }}</span>
            <span class="featured-price">{{ t('hotels.fromPrice') }} {{ Number(item.prix_min || 0) }}€/{{ t('hotels.perNight') }}</span>
          </div>

          <div class="featured-bottom">
            <p class="featured-city">{{ localize(item.ville) }}</p>
            <h3 class="featured-title">{{ localize(item.nom) }}</h3>
            <span class="featured-cta">{{ t('hotelsSection.viewButton') }}</span>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="py-16 max-w-7xl mx-auto px-4">
      <div class="flex items-end justify-between mb-8 gap-4 flex-wrap">
        <div>
          <p class="text-amber-500 font-semibold uppercase tracking-[0.2em] text-xs">{{ t('home.popularDestinationsTag') }}</p>
          <h2 class="font-display text-4xl text-[#3A1A04] mt-2">{{ t('home.popularDestinationsTitle') }}</h2>
        </div>
        <RouterLink to="/hotels" class="text-sm font-semibold text-secondary hover:underline">{{ t('home.seeAllDestinations') }}</RouterLink>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <RouterLink
          v-for="city in destinationCards"
          :key="city.name"
          :to="{ path: '/hotels', query: { ville: city.name } }"
          class="destination-card group"
        >
          <img :src="city.image" :alt="city.name" loading="lazy" class="destination-image" @error="onImageError" />
          <div class="destination-overlay"></div>
          <div class="destination-content">
            <h3 class="destination-title">{{ localize(city.name) }}</h3>
            <p class="destination-count">{{ city.count }} {{ t('hotels.resultsWord') }}</p>
          </div>
        </RouterLink>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-24 bg-white overflow-hidden">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
          <p class="text-amber-500 font-semibold uppercase tracking-[0.2em] text-xs">Avis de nos clients</p>
          <h2 class="font-display text-4xl text-[#3A1A04] mt-2">Expériences Exceptionnelles</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
          <div v-for="(testi, i) in testimonials" :key="i" class="testimonial-card p-8 rounded-[2rem] bg-slate-50 border border-slate-100 flex flex-col justify-between transition-all hover:-translate-y-2">
            <div>
              <div class="flex gap-1 mb-6">
                <Star v-for="s in 5" :key="s" :size="16" fill="#D4820A" class="text-[#D4820A]" />
              </div>
              <p class="text-[#3A1A04] italic leading-relaxed text-lg mb-8">"{{ testi.quote }}"</p>
            </div>
            <div class="flex items-center gap-4">
              <img :src="testi.avatar" class="w-12 h-12 rounded-full object-cover" alt="Client" />
              <div>
                <p class="font-bold text-[#3A1A04]">{{ testi.author }}</p>
                <p class="text-xs text-slate-500 uppercase tracking-widest">{{ testi.location }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer class="premium-footer pt-16 pb-12 mt-12">
      <div class="footer-gold-line"></div>
      <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="space-y-4">
          <AppLogo variant="dark" size="md" />
          <p class="text-[#A07040] text-sm leading-relaxed max-w-xs">{{ t('home.footerDescription') }}</p>
          <div class="flex items-center gap-3 pt-2">
            <a href="#" class="social-pill" aria-label="Instagram" title="Instagram">IG</a>
            <a href="#" class="social-pill" aria-label="Facebook" title="Facebook">FB</a>
            <a href="#" class="social-pill" aria-label="LinkedIn" title="LinkedIn">IN</a>
          </div>
        </div>

        <div v-for="col in footerCols" :key="col.title" class="space-y-3">
          <h4 class="font-bold text-[#3A1A04] text-sm uppercase tracking-wide">{{ col.title }}</h4>
          <ul class="space-y-2.5">
            <li v-for="link in col.links" :key="link" class="text-[#A07040] text-sm hover:text-[#D4820A] transition-colors cursor-pointer">{{ link }}</li>
          </ul>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 mt-12 pt-8 border-t border-[rgba(180,110,30,0.18)] flex flex-wrap gap-4 justify-between items-center text-xs text-[#A07040]">
        <p>{{ t('home.copyright') }}</p>
        <LanguageSwitcher />
      </div>
    </footer>
  </div>
</template>

<script setup>
import { reactive, onMounted, onBeforeUnmount, computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHotelStore } from '../stores/hotel'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import LanguageSwitcher from '../components/LanguageSwitcher.vue'
import AppLogo from '../components/AppLogo.vue'
import { Building2, Users, Globe2, Star, Trophy, Lock, MapPin } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const hotelStore = useHotelStore()
const { t } = useI18n()

const search = reactive({ ville: '', dateArrivee: '', dateDepart: '', nbVoyageurs: 1 })
const destination = ref('')
const destinationSuggestions = ref([])
const showDestinationDropdown = ref(false)
const searchFocused = ref(false)
const statsSectionRef = ref(null)
const selectedDestinationType = ref('')
const popularCities = ref([])
let destinationDebounceTimer = null

const testimonials = [
  {
    quote: "Un séjour inoubliable. Le service conciergerie a anticipé chacun de nos besoins. L'excellence à l'état pur.",
    author: "Marc-Antoine de Rochefort",
    location: "Paris, France",
    avatar: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80"
  },
  {
    quote: "HotelEase a transformé ma façon de voyager. La sélection d'hôtels est tout simplement irréprochable.",
    author: "Elena Rodriguez",
    location: "Madrid, Espagne",
    avatar: "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80"
  },
  {
    quote: "Le programme de fidélité offre de réels avantages. Je ne réserve plus que par cette plateforme.",
    author: "Jean-Philippe Tremblay",
    location: "Montréal, Canada",
    avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80"
  }
]

const liveStats = ref({
  hotels_count: 0,
  clients_count: 0,
  reservations_count: 0,
  cities_count: 0,
  average_rating: null,
})

const animatedStats = reactive({
  hotels: 0,
  clients: 0,
  cities: 0,
  rating: 0,
})

const averageStars = computed(() => {
  const hotels = hotelStore.hotels || []
  if (!hotels.length) return null
  const values = hotels.map((item) => Number(item?.etoiles || 0)).filter((value) => value > 0)
  if (!values.length) return null
  return values.reduce((sum, value) => sum + value, 0) / values.length
})

const statCards = computed(() => {
  const ratingTarget = liveStats.value.average_rating ?? averageStars.value
  return [
      {
        key: 'hotels',
        icon: Building2,
        label: t('stats.hotels'),
        valueText: `${Math.round(animatedStats.hotels)}`,
      },
      {
        key: 'clients',
        icon: Users,
        label: t('stats.clients'),
        valueText: `${Math.round(animatedStats.clients)}`,
      },
      {
        key: 'cities',
        icon: Globe2,
        label: t('stats.cities'),
        valueText: `${Math.round(animatedStats.cities)}`,
      },
      {
        key: 'rating',
        icon: Star,
        alt: 'Rating illustration',
        label: t('stats.rating'),
        valueText: ratingTarget === null ? '5.0' : `${animatedStats.rating.toFixed(1)}`,
        valueSuffix: '★',
      },
  ]
})

const featuredHotels = computed(() => {
  const hotels = Array.isArray(hotelStore.hotels) ? [...hotelStore.hotels] : []
  return hotels
    .sort((a, b) => {
      const noteA = Number(a?.noteMoyenne || 0)
      const noteB = Number(b?.noteMoyenne || 0)
      if (noteA !== noteB) return noteB - noteA
      const starsA = Number(a?.etoiles || 0)
      const starsB = Number(b?.etoiles || 0)
      return starsB - starsA
    })
    .slice(0, 4)
})

const destinationCards = computed(() => {
  return popularCities.value.slice(0, 4).map((city) => ({
    name: city.ville,
    count: Number(city.count || 0),
    image: city.image,
  }))
})

function localize(field) {
  if (!field) return ''
  if (typeof field === 'string') return field
  if (typeof field === 'object' && !Array.isArray(field)) {
    return field[locale.value] || field.fr || field.en || field.ar || Object.values(field)[0] || ''
  }
  return String(field || '')
}

const footerCols = computed(() => [
  {
    title: t('home.footer.destinationsTitle'),
    links: popularCities.value.slice(0, 5).map((city) => city.ville),
  },
  { title: t('home.footer.servicesTitle'), links: [t('home.footer.links.booking'), t('home.footer.links.flexibleCancellation'), t('home.footer.links.support247'), t('home.footer.links.loyaltyProgram')] },
  { title: t('home.footer.companyTitle'), links: [t('home.footer.links.about'), t('home.footer.links.careers'), t('home.footer.links.partnerships'), t('home.footer.links.blog'), t('home.footer.links.press')] },
])

function doSearch() {
  const rawValue = String(destination.value || '').trim()
  if (rawValue && destinationSuggestions.value.length) {
    const exact = destinationSuggestions.value.find((item) => {
      const label = destinationLabel(item).toLowerCase()
      return label === rawValue.toLowerCase()
    })

    if (!exact) {
      const cityFirst = destinationSuggestions.value.find((item) => item?.type === 'ville')
      if (cityFirst) {
        destination.value = destinationLabel(cityFirst)
        selectedDestinationType.value = 'ville'
      }
    }
  }

  search.ville = String(destination.value || '').trim()
  search.nbVoyageurs = Math.max(1, Math.min(20, Number(search.nbVoyageurs || 1)))
  router.push({ path: '/hotels', query: { ...search } })
}

function destinationLabel(item) {
  if (!item || typeof item !== 'object') return ''
  return localize(item.nom || item.ville || item.value || '').trim()
}

function selectDestinationSuggestion(item) {
  const value = destinationLabel(item)
  destination.value = value
  search.ville = value
  selectedDestinationType.value = item?.type || ''
  showDestinationDropdown.value = false
}

function increaseTravelers() {
  search.nbVoyageurs = Math.min(20, Number(search.nbVoyageurs || 1) + 1)
}

function decreaseTravelers() {
  search.nbVoyageurs = Math.max(1, Number(search.nbVoyageurs || 1) - 1)
}

function hideDestinationDropdown() {
  setTimeout(() => {
    showDestinationDropdown.value = false
  }, 200)
}

watch(destination, (value) => {
  search.ville = value
  selectedDestinationType.value = ''

  if (destinationDebounceTimer) {
    clearTimeout(destinationDebounceTimer)
  }

  const q = String(value || '').trim()
  if (!q.length) {
    destinationSuggestions.value = []
    showDestinationDropdown.value = false
    return
  }

  destinationDebounceTimer = setTimeout(async () => {
    try {
      const response = await fetch(`/api/hotels/suggestions?q=${encodeURIComponent(q)}`)
      const data = await response.json()
      const suggestions = Array.isArray(data) ? data : []
      destinationSuggestions.value = [...suggestions].sort((a, b) => {
        const rankA = a?.type === 'ville' ? 0 : 1
        const rankB = b?.type === 'ville' ? 0 : 1
        if (rankA !== rankB) return rankA - rankB
        return destinationLabel(a).localeCompare(destinationLabel(b), 'fr', { sensitivity: 'base' })
      })
      showDestinationDropdown.value = true
    } catch (error) {
      destinationSuggestions.value = []
      showDestinationDropdown.value = true
      console.error('Autocomplete error:', error)
    }
  }, 300)
})

function starString(stars) {
  const count = Math.max(1, Math.min(5, Number(stars || 0)))
  return '★'.repeat(count)
}

function hotelImage(item) {
  if (item?.previewPhoto) return item.previewPhoto
  const photos = Array.isArray(item?.photos) ? item.photos.filter(Boolean) : []
  return photos[0] || 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1400&q=80'
}

function onImageError(event) {
  if (!event?.target) return
  event.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
}

function cardLayoutClass(index) {
  if (index === 0) {
    return 'md:col-span-2 md:row-span-2'
  }
  return 'md:col-span-2 md:row-span-1'
}

function animateNumber(from, to, setter, duration = 1100) {
  const start = performance.now()
  const easeOut = (x) => 1 - Math.pow(1 - x, 3)

  const frame = (now) => {
    const progress = Math.min(1, (now - start) / duration)
    const value = from + (to - from) * easeOut(progress)
    setter(value)
    if (progress < 1) {
      requestAnimationFrame(frame)
    }
  }

  requestAnimationFrame(frame)
}

function runStatsAnimation() {
  const ratingTarget = liveStats.value.average_rating ?? averageStars.value ?? 0
  animateNumber(0, Number(liveStats.value.hotels_count || 0), (value) => { animatedStats.hotels = value }, 1100)
  animateNumber(0, Number(liveStats.value.clients_count || 0), (value) => { animatedStats.clients = value }, 1200)
  animateNumber(0, Number(liveStats.value.cities_count || 0), (value) => { animatedStats.cities = value }, 1050)
  animateNumber(0, Number(ratingTarget || 0), (value) => { animatedStats.rating = value }, 1250)
}

async function fetchHomepageStats() {
  try {
    const { data } = await api.get('/stats')
    liveStats.value = {
      hotels_count: Number(data?.hotels_count || 0),
      clients_count: Number(data?.clients_count || 0),
      reservations_count: Number(data?.reservations_count || 0),
      cities_count: Number(data?.cities_count || 0),
      average_rating: data?.average_rating === null || data?.average_rating === undefined
        ? null
        : Number(data.average_rating),
    }
  } catch {
    liveStats.value = {
      hotels_count: 0,
      clients_count: 0,
      reservations_count: 0,
      cities_count: 0,
      average_rating: null,
    }
  }
}

async function fetchPopularCities() {
  try {
    const { data } = await api.get('/hotels/cities')
    popularCities.value = Array.isArray(data) ? data : []
  } catch {
    popularCities.value = []
  }
}

let observer = null

onMounted(async () => {
  destination.value = String(route.query?.ville || '')
  search.ville = destination.value

  await Promise.all([
    fetchHomepageStats(),
    fetchPopularCities(),
    hotelStore.fetchHotels({ per_page: 40, sort: 'recommande' }),
  ])

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          runStatsAnimation()
          observer?.disconnect()
        }
      })
    },
    { threshold: 0.35 },
  )

  if (statsSectionRef.value) {
    observer.observe(statsSectionRef.value)
  }
})

onBeforeUnmount(() => {
  if (observer) {
    observer.disconnect()
  }
  if (destinationDebounceTimer) {
    clearTimeout(destinationDebounceTimer)
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap');

.home-premium {
  font-family: 'Inter', sans-serif;
}

.font-display,
.hero-title,
h2 {
  font-family: 'Playfair Display', serif;
}

.hero {
  background-image: linear-gradient(120deg, rgba(250, 246, 238, 0.86) 0%, rgba(250, 246, 238, 0.62) 45%, rgba(250, 246, 238, 0.3) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

.hero-overlay {
  background: radial-gradient(circle at 20% 20%, rgba(212, 130, 10, 0.1), transparent 38%);
}

.hero-badge {
  background: rgba(245, 158, 11, 0.18);
  border: 1px solid rgba(251, 191, 36, 0.55);
  animation: badgePulse 2.6s infinite;
}

.badge-trophy {
  display: inline-block;
  animation: bounceIn 0.9s ease;
}

.hero-title {
  font-weight: 800;
}

.hero-word {
  display: block;
  opacity: 0;
  animation: fadeInUp 0.7s ease forwards;
}

.hero-word:nth-child(1) { animation-delay: 0.05s; }
.hero-word:nth-child(2) { animation-delay: 0.2s; }
.hero-word:nth-child(3) { animation-delay: 0.35s; }

.hero-accent {
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  position: relative;
}

.hero-accent::after {
  content: '';
  position: absolute;
  top: 0;
  left: -15%;
  width: 45%;
  height: 100%;
  background: linear-gradient(110deg, transparent, rgba(255, 255, 255, 0.6), transparent);
  animation: shimmer 2.5s infinite;
}

.search-glass {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(180, 110, 30, 0.22);
  box-shadow: 0 16px 40px rgba(58, 26, 4, 0.12);
  transition: box-shadow 0.25s ease;
  position: relative;
  overflow: visible;
}

.search-focused {
  box-shadow: 0 0 0 2px rgba(212, 130, 10, 0.45), 0 16px 40px rgba(58, 26, 4, 0.14);
}

.search-segment {
  padding: 0.45rem 0.8rem;
  border-right: 1px solid rgba(180, 110, 30, 0.2);
  position: relative;
  overflow: visible;
}

.search-segment:last-child {
  border-right: none;
}

.traveler-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.traveler-btn {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  border: 1px solid rgba(180, 110, 30, 0.32);
  color: var(--text-primary);
  font-weight: 800;
  background: rgba(250, 246, 238, 0.95);
}

.traveler-btn:hover {
  background: rgba(212, 130, 10, 0.16);
}

.traveler-input {
  text-align: center;
}

.destination-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 100%;
  background: #fff;
  z-index: 2147483000; /* force above all stacking contexts */
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
  max-height: 320px;
  overflow-y: auto;
}

.destination-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 16px;
  color: #2D1B08;
  font-size: 15px;
  text-align: left;
  border-left: 3px solid transparent;
}

.destination-item:hover {
  background: rgba(255, 242, 230, 0.9);
  border-left-color: #FF8C00;
}

.destination-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #2D1B08;
  font-weight: 600;
}

.destination-badge {
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 999px;
  background: #FFF3E0; /* soft orange background */
  color: #C2410C; /* darker orange text */
  border: 1px solid #FED7AA;
}

.destination-icon {
  width: 16px;
  height: 16px;
  stroke: currentColor;
}

.destination-empty {
  padding: 14px 20px;
  text-align: center;
  font-style: italic;
  color: #64748b;
}

.search-label {
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(58, 26, 4, 0.9);
  margin-bottom: 0.2rem;
  font-weight: 700;
}

:deep(.hero-input) {
  width: 100%;
  background: transparent;
  color: var(--text-primary);
  border: none;
  outline: none;
  font-weight: 600;
}

:deep(.hero-input::placeholder) {
  color: rgba(58, 26, 4, 0.65);
}

.search-cta {
  background: linear-gradient(90deg, #f59e0b, #f97316);
  color: white;
  box-shadow: 0 10px 26px rgba(249, 115, 22, 0.45);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.search-cta:hover {
  transform: scale(1.03);
  box-shadow: 0 12px 30px rgba(249, 115, 22, 0.55);
}

.stat-card {
  position: relative;
  overflow: hidden;
  min-height: 274px;
  padding: 22px 18px 18px;
  border-radius: 30px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(251, 249, 244, 0.98) 100%);
  border: 1px solid rgba(244, 203, 123, 0.3);
  box-shadow: 0 12px 26px rgba(58, 26, 4, 0.08);
  transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 18px 38px rgba(58, 26, 4, 0.12);
  border-color: rgba(245, 158, 11, 0.28);
}

.stat-icon-shell {
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 26px;
  background: #f59e0b;
  color: #fffaf0;
  margin-bottom: 1.15rem;
  box-shadow: 0 14px 24px rgba(245, 158, 11, 0.26);
}

.stat-icon {
  width: 42px;
  height: 42px;
  stroke: currentColor;
  fill: none;
}

.stat-value {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.1rem;
  font-size: 2.45rem;
  font-weight: 800;
  color: #23140a;
  line-height: 1;
  letter-spacing: -0.04em;
  margin-bottom: 0.35rem;
}

.stat-value-suffix {
  color: #f59e0b;
  font-size: 1.2rem;
  font-weight: 800;
  line-height: 1;
  margin-left: 0.1rem;
}

.stat-label {
  font-size: 1rem;
  color: #9a8b75;
  margin-top: 0.1rem;
}

.stat-card::after {
  content: '';
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4px;
  background: linear-gradient(90deg, #f59e0b, #fb923c);
}

.why-premium {
  background: linear-gradient(180deg, rgba(255, 251, 245, 0.96), rgba(248, 239, 225, 0.96));
}

.why-card {
  border-radius: 1.8rem;
  border: 1px solid rgba(244, 203, 123, 0.42);
  background: linear-gradient(180deg, rgba(255, 253, 249, 0.98), rgba(252, 247, 236, 0.98));
  padding: 1.5rem 1.55rem 1.45rem;
  min-height: 214px;
  box-shadow: 0 12px 24px rgba(58, 26, 4, 0.06);
}

.why-kicker {
  color: #b8946b;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.22em;
  text-transform: uppercase;
}

.why-heading {
  margin-top: 0.5rem;
  color: #4a2a11;
  font-family: var(--font-display, Georgia, 'Times New Roman', serif);
  font-size: clamp(2.35rem, 4vw, 4.1rem);
  line-height: 1.05;
  font-weight: 500;
}

.why-icon-box {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #111111;
  background: #f4a01d;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.why-icon-box :deep(svg) {
  display: block;
}

.why-icon-box--lock {
  color: #111111;
}

.why-icon-box--star {
  color: #111111;
}

.why-title {
  color: #4a2a11;
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 0.65rem;
  line-height: 1.2;
}

.why-text {
  color: #b27d4f;
  font-size: 1rem;
  line-height: 1.55;
}

.header-underline {
  width: 130px;
  height: 4px;
  border-radius: 999px;
  margin: 0.8rem auto 0;
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.featured-wrap {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 0.4rem;
}

.featured-card {
  position: relative;
  min-width: 86%;
  height: 420px;
  border-radius: 1.1rem;
  overflow: hidden;
  display: block;
  box-shadow: 0 16px 36px rgba(58, 26, 4, 0.08);
}

.featured-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.45s ease;
}

.featured-card:hover .featured-image {
  transform: scale(1.05);
}

.featured-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
}

.featured-top {
  position: absolute;
  top: 0.9rem;
  left: 0.9rem;
  right: 0.9rem;
  display: flex;
  justify-content: space-between;
  gap: 0.6rem;
}

.featured-stars,
.featured-price {
  font-size: 0.76rem;
  font-weight: 700;
  border-radius: 999px;
  padding: 0.35rem 0.6rem;
  white-space: nowrap;
}

.featured-stars {
  color: var(--color-primary);
  background: rgba(255, 255, 255, 0.9);
}

.featured-price {
  color: #fff;
  background: var(--color-primary);
}

.featured-bottom {
  position: absolute;
  left: 1rem;
  right: 1rem;
  bottom: 1rem;
}

.featured-city {
  color: rgba(255, 255, 255, 0.98);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.featured-title {
  color: #fff;
  font-family: 'Playfair Display', serif;
  font-size: 1.68rem;
  line-height: 1.45;
  font-weight: 700;
  margin: 0.25rem 0 0.6rem;
  letter-spacing: 0.01em;
  text-rendering: optimizeLegibility;
  padding: 3px 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
  transition: color 0.3s ease;
}

.featured-card:hover .featured-title {
  color: #f59e0b;
}

.featured-cta {
  display: inline-flex;
  transform: translateY(12px);
  opacity: 0;
  transition: all 0.25s ease;
  color: white;
  background: rgba(245, 158, 11, 0.92);
  padding: 0.45rem 0.75rem;
  border-radius: 999px;
  font-weight: 700;
  font-size: 0.82rem;
}

.featured-card:hover .featured-cta {
  transform: translateY(0);
  opacity: 1;
}

.destination-card {
  position: relative;
  height: 260px;
  border-radius: 1rem;
  overflow: hidden;
  display: block;
}

.destination-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.35s ease;
}

.destination-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.4) 45%, transparent 100%);
  transition: background 0.3s ease;
}

.destination-content {
  position: absolute;
  left: 1rem;
  right: 1rem;
  bottom: 1rem;
}

.destination-title {
  color: #fff;
  font-size: 1.45rem;
  font-weight: 800;
}

.destination-count {
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.9rem;
}

.destination-card:hover .destination-image {
  transform: scale(1.05);
}

.destination-card:hover .destination-overlay {
  background: linear-gradient(to top, rgba(0, 0, 0, 0.75) 0%, rgba(0, 0, 0, 0.42) 40%, transparent 100%);
}

.premium-footer {
  background: var(--bg-primary);
  position: relative;
}

.footer-gold-line {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, transparent, #f59e0b, #fbbf24, transparent);
}

.social-pill {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: #D4820A;
  background: rgba(212, 130, 10, 0.08);
  border: 1.5px solid rgba(212, 130, 10, 0.25);
  transition: all 0.3s ease;
}

.social-pill:hover {
  background: rgba(212, 130, 10, 0.15);
  border-color: rgba(212, 130, 10, 0.4);
  transform: translateY(-2px);
}

@media (min-width: 768px) {
  .featured-wrap {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    grid-auto-rows: 200px;
    overflow: visible;
  }

  .featured-card {
    min-width: auto;
    height: auto;
  }
}

@media (max-width: 767px) {
  .search-segment {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.18);
  }

  .search-segment:last-child {
    border-bottom: none;
  }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes shimmer {
  0% { transform: translateX(0); }
  100% { transform: translateX(300%); }
}

@keyframes bounceIn {
  0% { transform: translateY(-8px) scale(0.9); opacity: 0; }
  55% { transform: translateY(0) scale(1.08); opacity: 1; }
  100% { transform: translateY(0) scale(1); }
}

@keyframes badgePulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.2); }
  50% { box-shadow: 0 0 0 8px rgba(245, 158, 11, 0.06); }
}

@keyframes floatIcon {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
.search-glass {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(45, 27, 8, 0.08);
  box-shadow: 0 20px 50px rgba(45, 27, 8, 0.12);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-focused {
  background: #ffffff;
  transform: translateY(-4px);
  box-shadow: 0 30px 70px rgba(45, 27, 8, 0.18);
  border-color: rgba(212, 130, 10, 0.2);
}

.search-segment {
  padding: 0.5rem 1rem;
  border-right: 1px solid rgba(45, 27, 8, 0.06);
}

.search-segment:last-child {
  border-right: none;
}

.search-label {
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  color: #8B4513;
  margin-bottom: 0.5rem;
  opacity: 0.6;
}

.hero-input {
  width: 100%;
  background: transparent;
  border: none;
  padding: 0.25rem 0;
  font-weight: 700;
  color: #2D1B08;
  outline: none;
  font-size: 1rem;
}

.hero-input::placeholder {
  color: #A07040;
  opacity: 0.4;
}

.search-cta {
  background: linear-gradient(135deg, #2D1B08 0%, #3D2B18 100%);
  color: #fff;
  transition: all 0.3s ease;
  box-shadow: 0 10px 25px rgba(45, 27, 8, 0.25);
}

.search-cta:hover {
  transform: scale(1.05);
  background: #D4820A;
  box-shadow: 0 12px 30px rgba(212, 130, 10, 0.3);
}

.testimonial-card {
  box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}

.testimonial-card:hover {
  box-shadow: 0 20px 50px rgba(45, 27, 8, 0.08);
  background: #fff;
}

@media (max-width: 768px) {
  .search-segment {
    border-right: none;
    border-bottom: 1px solid rgba(45, 27, 8, 0.06);
    padding-bottom: 1rem;
  }
}
</style>
