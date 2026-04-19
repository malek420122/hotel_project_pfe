<template>
  <div class="home-premium bg-slate-50 text-slate-900">
    <Navbar />

    <section class="hero relative min-h-screen pt-24 md:pt-28 pb-16 md:pb-24">
      <div class="hero-overlay absolute inset-0"></div>
      <div class="relative max-w-7xl mx-auto px-4">
        <div class="max-w-4xl text-white">
          <div class="hero-badge inline-flex items-center gap-2 px-5 py-2 rounded-full mb-6">
            <span class="badge-trophy">🏆</span>
            <span class="text-sm sm:text-base font-semibold">{{ t('hero.badge') }}</span>
          </div>

          <h1 class="hero-title text-4xl sm:text-6xl md:text-7xl leading-tight mb-5">
            <span class="hero-word">{{ t('home.heroLine1') }}</span>
            <span class="hero-word hero-accent">{{ t('home.heroAccent') }}</span>
            <span class="hero-word">{{ t('home.heroLine2') }}</span>
          </h1>

          <p class="text-lg sm:text-xl text-white/85 max-w-2xl mb-9">
            {{ t('hero.subtitle') }}
          </p>

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

                  <div v-if="showDestinationDropdown" class="destination-dropdown">
                    <button
                      v-for="(item, idx) in destinationSuggestions"
                      :key="`${item.type}-${destinationLabel(item)}-${idx}`"
                      type="button"
                      class="destination-item"
                      @mousedown.prevent="selectDestinationSuggestion(item)"
                    >
                      <span class="destination-name">{{ destinationLabel(item) }}</span>
                      <span class="destination-badge">
                        {{ item.type === 'hotel' ? 'Hôtel' : 'Ville' }}
                      </span>
                    </button>

                    <div v-if="!destinationSuggestions.length" class="destination-empty">
                      Aucun résultat
                    </div>
                  </div>
                </div>
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.checkin') }}</p>
                <input v-model="search.dateArrivee" type="date" class="hero-input" />
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.checkout') }}</p>
                <input v-model="search.dateDepart" type="date" class="hero-input" />
              </div>

              <div class="search-segment">
                <p class="search-label">{{ t('searchbar.travelers') }}</p>
                <div class="traveler-control">
                  <button type="button" class="traveler-btn" @click="decreaseTravelers">-</button>
                  <input v-model.number="search.nbVoyageurs" type="number" min="1" max="20" class="hero-input traveler-input" />
                  <button type="button" class="traveler-btn" @click="increaseTravelers">+</button>
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
    </section>

    <section ref="statsSectionRef" class="-mt-10 relative z-10 pb-8">
      <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
          <article v-for="card in statCards" :key="card.key" class="stat-card">
            <div class="stat-icon">{{ card.icon }}</div>
            <p class="stat-value">{{ card.value }}</p>
            <p class="stat-label">{{ card.label }}</p>
            <div class="stat-accent"></div>
          </article>
        </div>
      </div>
    </section>

    <section class="py-16 why-premium">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-10">
          <p class="text-amber-300 font-semibold uppercase tracking-[0.18em] text-xs">{{ t('home.whyTag') }}</p>
          <h2 class="font-display text-4xl sm:text-5xl text-white mt-3">{{ t('home.whyTitlePremium') }}</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="why-card">
            <div class="why-icon">🏆</div>
            <h3 class="why-title">{{ t('home.why1Title') }}</h3>
            <p class="why-text">{{ t('home.why1Text') }}</p>
          </div>
          <div class="why-card">
            <div class="why-icon">🔒</div>
            <h3 class="why-title">{{ t('home.why2Title') }}</h3>
            <p class="why-text">{{ t('home.why2Text') }}</p>
          </div>
          <div class="why-card">
            <div class="why-icon">⭐</div>
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
            <p class="featured-city">{{ item.ville }}</p>
            <h3 class="featured-title">{{ item.nom }}</h3>
            <span class="featured-cta">{{ t('hotelsSection.viewButton') }}</span>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="py-16 max-w-7xl mx-auto px-4">
      <div class="flex items-end justify-between mb-8 gap-4 flex-wrap">
        <div>
          <p class="text-amber-500 font-semibold uppercase tracking-[0.2em] text-xs">{{ t('home.popularDestinationsTag') }}</p>
          <h2 class="font-display text-4xl text-slate-900 mt-2">{{ t('home.popularDestinationsTitle') }}</h2>
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
            <h3 class="destination-title">{{ city.name }}</h3>
            <p class="destination-count">{{ city.count }} {{ t('hotels.resultsWord') }}</p>
          </div>
        </RouterLink>
      </div>
    </section>

    <footer class="premium-footer text-white pt-14 pb-8 mt-8">
      <div class="footer-gold-line"></div>
      <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-10">
        <div>
          <AppLogo variant="dark" size="md" />
          <p class="text-white/70 text-sm mt-4">{{ t('home.footerDescription') }}</p>
          <div class="flex items-center gap-2 mt-5">
            <a href="#" class="social-pill" aria-label="Instagram">IG</a>
            <a href="#" class="social-pill" aria-label="Facebook">FB</a>
            <a href="#" class="social-pill" aria-label="LinkedIn">IN</a>
          </div>
        </div>

        <div v-for="col in footerCols" :key="col.title">
          <h4 class="font-semibold text-amber-300 mb-3">{{ col.title }}</h4>
          <ul class="space-y-2">
            <li v-for="link in col.links" :key="link" class="text-white/70 text-sm hover:text-white cursor-pointer">{{ link }}</li>
          </ul>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 mt-10 pt-6 border-t border-white/15 flex flex-wrap gap-4 justify-between items-center text-sm text-white/65">
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
      icon: '🏨',
      label: t('stats.hotels'),
      value: `${Math.round(animatedStats.hotels)}`,
    },
    {
      key: 'clients',
      icon: '😊',
      label: t('stats.clients'),
      value: `${Math.round(animatedStats.clients)}`,
    },
    {
      key: 'cities',
      icon: '🌍',
      label: t('stats.cities'),
      value: `${Math.round(animatedStats.cities)}`,
    },
    {
      key: 'rating',
      icon: '⭐',
      label: t('stats.rating'),
      value: ratingTarget === null ? '★' : `${animatedStats.rating.toFixed(1)}★`,
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
  return String(item.nom || item.ville || item.value || '').trim()
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
  background-image: linear-gradient(135deg, rgba(10, 20, 60, 0.92) 0%, rgba(26, 86, 219, 0.75) 100%), url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1920');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

.hero-overlay {
  background: radial-gradient(circle at 20% 20%, rgba(251, 191, 36, 0.12), transparent 35%);
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
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.35);
  box-shadow: 0 20px 50px rgba(2, 12, 40, 0.35);
  transition: box-shadow 0.25s ease;
  position: relative;
  overflow: visible;
}

.search-focused {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.8), 0 20px 50px rgba(2, 12, 40, 0.35);
}

.search-segment {
  padding: 0.45rem 0.8rem;
  border-right: 1px solid rgba(255, 255, 255, 0.22);
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
  border: 1px solid rgba(255, 255, 255, 0.4);
  color: #fff;
  font-weight: 800;
  background: rgba(255, 255, 255, 0.16);
}

.traveler-btn:hover {
  background: rgba(255, 255, 255, 0.26);
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
  z-index: 999999;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
  max-height: 320px;
  overflow-y: auto;
}

.destination-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 20px;
  color: #1e293b;
  font-size: 16px;
  text-align: left;
  border-left: 3px solid transparent;
}

.destination-item:hover {
  background: #f0f7ff;
  border-left-color: #f59e0b;
}

.destination-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.destination-badge {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 20px;
  background: #1a56db;
  color: #fff;
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
  color: rgba(255, 255, 255, 0.76);
  margin-bottom: 0.2rem;
  font-weight: 700;
}

:deep(.hero-input) {
  width: 100%;
  background: transparent;
  color: white;
  border: none;
  outline: none;
  font-weight: 600;
}

:deep(.hero-input::placeholder) {
  color: rgba(255, 255, 255, 0.82);
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
  padding: 1rem 1.1rem 1.15rem;
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(148, 163, 184, 0.25);
  box-shadow: 0 10px 25px rgba(2, 6, 23, 0.12);
  transition: transform 0.22s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
}

.stat-icon {
  font-size: 1.25rem;
  margin-bottom: 0.35rem;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
}

.stat-label {
  font-size: 0.78rem;
  color: #475569;
  margin-top: 0.2rem;
}

.stat-accent {
  position: absolute;
  left: 0;
  bottom: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(90deg, #f59e0b, #f97316);
}

.why-premium {
  background: #0f172a;
}

.why-card {
  border-radius: 1rem;
  border: 1px solid rgba(251, 191, 36, 0.25);
  background: rgba(255, 255, 255, 0.05);
  padding: 1.25rem 1.2rem;
}

.why-icon {
  font-size: 2rem;
  margin-bottom: 0.8rem;
  animation: floatIcon 3.8s ease-in-out infinite;
}

.why-title {
  color: #fff;
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
}

.why-text {
  color: rgba(255, 255, 255, 0.74);
  font-size: 0.9rem;
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
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
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
  background: linear-gradient(to top, rgba(2, 6, 23, 0.85), rgba(2, 6, 23, 0.05));
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
  color: #fbbf24;
  background: rgba(15, 23, 42, 0.65);
}

.featured-price {
  color: #fff;
  background: rgba(2, 132, 199, 0.75);
}

.featured-bottom {
  position: absolute;
  left: 1rem;
  right: 1rem;
  bottom: 1rem;
}

.featured-city {
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.82rem;
}

.featured-title {
  color: white;
  font-size: 1.45rem;
  line-height: 1.2;
  margin: 0.2rem 0 0.45rem;
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
  background: linear-gradient(to top, rgba(2, 6, 23, 0.82), rgba(2, 6, 23, 0.12));
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
  background: linear-gradient(to top, rgba(2, 6, 23, 0.92), rgba(2, 6, 23, 0.25));
}

.premium-footer {
  background: #0f172a;
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
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: white;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.26);
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
</style>
