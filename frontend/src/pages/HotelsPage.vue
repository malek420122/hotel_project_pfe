<template>
  <div class="hotels-page min-h-screen bg-slate-50 text-slate-900">
    <Navbar />

    <section class="hotels-hero relative overflow-visible">
      <div class="hero-backdrop"></div>
      <div class="hero-image-layer"></div>
      <div class="hero-bokeh bokeh-1"></div>
      <div class="hero-bokeh bokeh-2"></div>
      <div class="hero-bokeh bokeh-3"></div>
      <div class="hero-bokeh bokeh-4"></div>

      <div class="relative z-10 max-w-7xl mx-auto px-4 pt-10 pb-20">
        <p v-if="bookingMessage" class="mb-4 inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700">
          <span>ℹ️</span>
          <span>{{ bookingMessage }}</span>
        </p>
        <div class="max-w-4xl text-white">
          <p class="text-amber-300 font-semibold uppercase tracking-[0.24em] text-xs mb-4">{{ t('hotelsSection.subtitle') }}</p>
          <h1 class="font-display text-4xl sm:text-5xl md:text-6xl leading-[1.05] max-w-3xl">
            {{ t('hotelsSection.title') }}
          </h1>
          <p class="text-white/80 mt-4 max-w-2xl text-base sm:text-lg">
            {{ t('hotelsSection.description') }}
          </p>
        </div>

        <div class="search-shell mt-8 lg:mt-10">
          <div :class="['grid grid-cols-1 lg:grid-cols-[1.6fr_1fr_1fr_1fr_1.1fr_auto] gap-3 items-end', isRTL && 'search-grid-rtl']">
            <div class="search-field">
              <p class="search-label">{{ t('searchbar.destination') }}</p>
              <SearchAutocomplete
                v-model="filters.ville"
                :placeholder="t('hero.searchPlaceholder')"
                input-class="hero-input"
                wrapper-class="relative"
                dropdown-width-mode="full-search-bar"
                dropdown-container-selector=".search-shell"
                :dropdown-max-height="420"
                @enter="applyFilters"
                @select="applyFilters"
              />
            </div>

            <div class="search-field">
              <p class="search-label">{{ t('searchbar.checkin') }}</p>
              <input v-model="filters.dateArrivee" type="date" class="hero-input" />
            </div>

            <div class="search-field">
              <p class="search-label">{{ t('searchbar.checkout') }}</p>
              <input v-model="filters.dateDepart" type="date" class="hero-input" />
            </div>

            <div class="search-field">
              <p class="search-label">{{ t('searchbar.travelers') }}</p>
              <div class="traveler-hero-control">
                <button v-if="isRTL" type="button" class="traveler-btn" @click="updateTravelers(1)">+</button>
                <button v-else type="button" class="traveler-btn" @click="updateTravelers(-1)">-</button>
                <span class="traveler-value">{{ filters.nbVoyageurs }}</span>
                <button v-if="isRTL" type="button" class="traveler-btn" @click="updateTravelers(-1)">-</button>
                <button v-else type="button" class="traveler-btn" @click="updateTravelers(1)">+</button>
              </div>
            </div>

            <div class="search-field">
              <p class="search-label">{{ t('filters.stars') }}</p>
              <div class="star-hero-group">
                <button
                  type="button"
                  :class="['hero-star-button', !filters.etoiles_min && 'hero-star-button-active']"
                  @click="clearStarFilterAndApply"
                >
                  {{ t('filters.all') }}
                </button>
                <button
                  v-for="n in [3, 4, 5]"
                  :key="`hero-star-${n}`"
                  type="button"
                  :class="['hero-star-button', Number(filters.etoiles_min || 0) === n && 'hero-star-button-active']"
                  @click="setStarFilter(n)"
                >
                  {{ n }}★
                </button>
              </div>
            </div>

            <button class="search-cta" :disabled="applyingFilters" @click="applyFilters">
              <span v-if="applyingFilters" class="inline-flex items-center gap-2">
                <span class="w-4 h-4 border-2 border-white/50 border-t-white rounded-full animate-spin"></span>
                {{ t('common.loading') }}
              </span>
              <span v-else>🔍 {{ t('searchbar.searchButton') }}</span>
            </button>
          </div>

          <div class="mt-6 text-center">
            <div class="premium-counter-pill inline-flex items-center gap-2">
              <span class="text-amber-300 text-lg">✦</span>
              <span class="text-sm sm:text-base font-semibold">{{ t('hotels.resultsFoundShort', { count: animatedCount }) }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="relative z-20 -mt-10">
      <div class="max-w-7xl mx-auto px-4">
        <div class="results-bar">
          <div>
            <p class="results-count">{{ t('hotels.resultsFound', { count: animatedCount }) }}</p>
            <p class="results-subtitle">{{ t('hotels.resultsForSearch') }}</p>
          </div>

          <div class="flex items-center gap-3">
            <label class="results-sort-label">{{ t('hotels.sortBy') }}</label>
            <div ref="sortDropdownRef" class="custom-sort-select">
              <button type="button" class="custom-sort-trigger" @click="toggleSortDropdown">
                <span class="custom-sort-icon">{{ currentSortOption.icon }}</span>
                <span class="custom-sort-label">{{ currentSortOption.label }}</span>
                <span class="custom-sort-arrow">▼</span>
              </button>

              <transition name="sort-dropdown">
                <div v-if="sortDropdownOpen" class="custom-sort-options-list">
                  <button
                    v-for="opt in sortOptions"
                    :key="opt.value"
                    type="button"
                    :class="['custom-sort-option', sortBy === opt.value && 'custom-sort-option-active']"
                    @click.stop="selectSortOption(opt)"
                  >
                    <span class="custom-sort-option-icon">{{ opt.icon }}</span>
                    <span class="custom-sort-option-label">{{ opt.label }}</span>
                    <span v-if="sortBy === opt.value" class="custom-sort-check">✓</span>
                  </button>
                </div>
              </transition>
            </div>
          </div>
        </div>
        <div class="gold-divider"></div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-8 lg:py-10">
      <div class="lg:grid lg:grid-cols-[330px_1fr] lg:gap-8 items-start">
        <aside class="hidden lg:block sticky top-24">
          <div class="filter-card">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <span class="text-amber-300 text-xl">⛭</span>
                <h3 class="filter-title">{{ t('filters.title') }}</h3>
              </div>
              <button @click="resetFilters" class="filter-reset-link">{{ t('filters.reset') }}</button>
            </div>

            <div class="space-y-6">
              <div>
                <p class="filter-label">{{ t('filters.services') }}</p>
                <div class="chip-grid">
                  <button
                    v-for="amenity in amenityOptions"
                    :key="amenity.value"
                    type="button"
                    :class="['toggle-chip', filters.equipements.includes(amenity.value) && 'toggle-chip-active']"
                    @click="toggleAmenity(amenity.value)"
                  >
                    <span v-if="filters.equipements.includes(amenity.value)">✓ </span>{{ amenity.label }}
                  </button>
                </div>
              </div>

              <div>
                <p class="filter-label">{{ t('filters.stars') }}</p>
                <div class="star-filter-row">
                  <button
                    v-for="n in [1, 2, 3, 4, 5]"
                    :key="n"
                    type="button"
                    :class="['star-pill', Number(filters.etoiles_min || 0) === n && 'star-pill-active']"
                    @click="setStarFilter(n)"
                  >
                    {{ n }}★
                  </button>
                </div>
                <button v-if="filters.etoiles_min" type="button" class="clear-chip mt-2" @click="clearStarFilterAndApply">{{ t('filters.reset') }}</button>
              </div>

              <div>
                <p class="filter-label">{{ t('filters.pricePerNight') }}</p>
                <div class="price-range-values">
                  <template v-if="isRTL">
                    <span>{{ formatPrice(filters.prix_max || priceBounds.max) }}</span>
                    <span>{{ formatPrice(filters.prix_min || priceBounds.min) }}</span>
                  </template>
                  <template v-else>
                    <span>{{ formatPrice(filters.prix_min || priceBounds.min) }}</span>
                    <span>{{ formatPrice(filters.prix_max || priceBounds.max) }}</span>
                  </template>
                </div>
                <div class="space-y-3 price-sliders-ltr">
                  <input :value="filters.prix_min" @input="onPriceMinInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range" />
                  <input :value="filters.prix_max" @input="onPriceMaxInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range" />
                </div>
              </div>

              <button class="apply-btn" :disabled="applyingFilters" @click="applyFilters">
                <span v-if="applyingFilters" class="inline-flex items-center gap-2">
                  <span class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                  {{ t('common.loading') }}
                </span>
                <span v-else>{{ t('filters.apply') }}<span v-if="activeSidebarFiltersCount > 0"> ({{ activeSidebarFiltersCount }})</span></span>
              </button>
            </div>
          </div>
        </aside>

        <div class="space-y-6">
          <div class="md:block lg:hidden">
            <div class="tablet-panel">
              <button class="tablet-panel-toggle" @click="tabletPanelOpen = !tabletPanelOpen">
                <span>{{ t('filters.title') }}</span>
                <span>{{ tabletPanelOpen ? '−' : '+' }}</span>
              </button>
              <div v-if="tabletPanelOpen" class="tablet-panel-body">
                <div class="space-y-5">
                  <div>
                    <p class="filter-label">{{ t('filters.services') }}</p>
                    <div class="chip-grid">
                      <button
                        v-for="amenity in amenityOptions"
                        :key="amenity.value"
                        type="button"
                        :class="['toggle-chip', filters.equipements.includes(amenity.value) && 'toggle-chip-active']"
                        @click="toggleAmenity(amenity.value)"
                      >
                        <span v-if="filters.equipements.includes(amenity.value)">✓ </span>{{ amenity.label }}
                      </button>
                    </div>
                  </div>

                  <div>
                    <p class="filter-label">{{ t('filters.stars') }}</p>
                    <div class="star-filter-row">
                      <button
                        v-for="n in [1, 2, 3, 4, 5]"
                        :key="n"
                        type="button"
                        :class="['star-pill', Number(filters.etoiles_min || 0) === n && 'star-pill-active']"
                        @click="setStarFilter(n)"
                      >
                        {{ n }}★
                      </button>
                    </div>
                    <button v-if="filters.etoiles_min" type="button" class="clear-chip mt-2" @click="clearStarFilterAndApply">{{ t('filters.reset') }}</button>
                  </div>

                  <div>
                    <p class="filter-label">{{ t('filters.pricePerNight') }}</p>
                    <div class="price-sliders-ltr">
                      <input :value="filters.prix_min" @input="onPriceMinInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range mb-2" />
                      <input :value="filters.prix_max" @input="onPriceMaxInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range" />
                    </div>
                  </div>

                  <div class="flex gap-3">
                    <button class="apply-btn flex-1" :disabled="applyingFilters" @click="applyFilters">
                      <span v-if="applyingFilters" class="inline-flex items-center gap-2">
                        <span class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        {{ t('common.loading') }}
                      </span>
                      <span v-else>{{ t('filters.apply') }}<span v-if="activeSidebarFiltersCount > 0"> ({{ activeSidebarFiltersCount }})</span></span>
                    </button>
                    <button @click="resetFilters" class="filter-reset-link">{{ t('filters.reset') }}</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="activeFilterTags.length" class="active-filters-summary">
            <p class="active-filters-title">{{ t('filters.active') }}</p>
            <div class="active-filters-tags">
              <button
                v-for="tag in activeFilterTags"
                :key="tag.key"
                type="button"
                class="active-filter-tag"
                @click="removeFilterTag(tag)"
              >
                {{ tag.label }} ×
              </button>
            </div>
          </div>

          <div v-if="hotelStore.loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="n in 9" :key="`skeleton-${n}`" class="hotel-skeleton-card">
              <div class="hotel-skeleton-image shimmer"></div>
              <div class="hotel-skeleton-content">
                <div class="shimmer-line w-4/5"></div>
                <div class="shimmer-line w-3/5"></div>
                <div class="shimmer-line w-full mt-4"></div>
                <div class="shimmer-line w-11/12"></div>
                <div class="shimmer-line w-2/5 mt-4"></div>
              </div>
            </div>
          </div>

          <transition-group
            v-else-if="sortedHotels.length"
            ref="gridRef"
            name="cards-stagger"
            tag="div"
            :class="['grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6', gridInView && 'grid-visible']"
          >
            <RouterLink
              v-for="(hotel, index) in sortedHotels"
              :key="hotel._id || `${hotel.nom}-${index}`"
              :to="{
                path: `/hotels/${hotel._id}`,
                query: {
                  dateArrivee: filters.dateArrivee || undefined,
                  dateDepart: filters.dateDepart || undefined,
                  nbVoyageurs: filters.nbVoyageurs || undefined,
                  ville: filters.ville || undefined,
                },
              }"
              :class="['premium-hotel-card group', cardsReady && 'cards-ready']"
              :style="{ transitionDelay: `${index * 0.1}s` }"
            >
              <div class="hotel-image-wrap">
                <div v-if="!imageLoadedState[String(hotel._id)]" class="hotel-image-skeleton shimmer"></div>
                <img
                  :src="hotelImage(hotel)"
                  :alt="hotel.nom"
                  class="hotel-image"
                  @load="onHotelImageLoad(hotel._id)"
                  @error="onHotelImageError($event, hotel._id)"
                />
                <div class="hotel-image-overlay"></div>
                <div class="hotel-image-top-left">
                  <span class="star-badge">{{ starString(hotel.etoiles) }}</span>
                </div>
                <div class="hotel-image-top-right">
                  <span class="rating-badge">⭐ {{ Number(hotel.noteMoyenne || 0).toFixed(1) }}</span>
                  <span class="price-badge">{{ formatPricePerNight(hotel.prix_min || 0) }}</span>
                </div>
              </div>

              <div class="hotel-content">
                <h3 class="hotel-title">{{ hotel.nom }}</h3>
                <p class="hotel-city-text">{{ getVille(hotel) }}</p>
                <p class="hotel-description">{{ getDescription(hotel) }}</p>

                <div v-if="hotel.equipements?.length" class="service-chip-row">
                  <span
                    v-for="service in hotel.equipements.slice(0, 5)"
                    :key="`${hotel._id}-${service}`"
                    :class="['service-chip', `service-chip-${serviceClass(service)}`]"
                  >
                    {{ serviceLabel(service) }}
                  </span>
                </div>

                <div class="hotel-bottom-row">
                  <div>
                    <p class="price-label">{{ t('hotels.fromPrice') }}</p>
                    <p class="price-value">{{ formatPricePerNight(hotel.prix_min || 0) }}</p>
                  </div>

                  <span class="view-btn">
                    {{ t('hotelsSection.viewButton') }} <span class="view-arrow">→</span>
                  </span>
                </div>
              </div>
            </RouterLink>
          </transition-group>

          <div v-else class="empty-state">
            <p class="empty-icon">🔍</p>
            <p class="empty-title">{{ t('hotels.noResultsTitle') }}</p>
            <p class="empty-subtitle">{{ t('hotels.noResultsSubtitle') }}</p>
            <button class="apply-btn mt-6" @click="resetFilters">{{ t('filters.reset') }}</button>
          </div>

          <div v-if="hotelStore.pagination.last_page > 1" class="pagination-wrap">
            <button
              v-if="hotelStore.pagination.current_page > 1"
              class="page-pill page-nav"
              @click="goToPage(hotelStore.pagination.current_page - 1)"
            >
              ←
            </button>

            <button
              v-for="p in hotelStore.pagination.last_page"
              :key="p"
              @click="goToPage(p)"
              :class="['page-pill', p === hotelStore.pagination.current_page ? 'page-pill-active' : 'page-pill-inactive']"
            >
              {{ p }}
            </button>

            <button
              v-if="hotelStore.pagination.current_page < hotelStore.pagination.last_page"
              class="page-pill page-nav"
              @click="goToPage(hotelStore.pagination.current_page + 1)"
            >
              →
            </button>
          </div>

          <p v-if="hotelStore.pagination.last_page > 1" class="page-summary">
            Page {{ hotelStore.pagination.current_page }} sur {{ hotelStore.pagination.last_page }}
          </p>
        </div>
      </div>
    </section>

    <button class="mobile-filter-fab md:hidden" @click="mobileFiltersOpen = true">
      {{ t('filters.title') }}
    </button>

    <transition name="sheet-fade">
      <div v-if="mobileFiltersOpen" class="mobile-sheet-backdrop" @click.self="mobileFiltersOpen = false">
        <div class="mobile-sheet">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <span class="text-amber-300 text-xl">⛭</span>
              <h3 class="filter-title">{{ t('filters.title') }}</h3>
            </div>
            <button class="filter-reset-link" @click="mobileFiltersOpen = false">✕</button>
          </div>

          <div class="space-y-5">
            <div>
              <p class="filter-label">{{ t('filters.services') }}</p>
              <div class="chip-grid">
                <button
                  v-for="amenity in amenityOptions"
                  :key="amenity.value"
                  type="button"
                  :class="['toggle-chip', filters.equipements.includes(amenity.value) && 'toggle-chip-active']"
                  @click="toggleAmenity(amenity.value)"
                >
                  <span v-if="filters.equipements.includes(amenity.value)">✓ </span>{{ amenity.label }}
                </button>
              </div>
            </div>

            <div>
              <p class="filter-label">{{ t('filters.stars') }}</p>
              <div class="star-filter-row">
                <button
                  v-for="n in [1, 2, 3, 4, 5]"
                  :key="n"
                  type="button"
                  :class="['star-pill', Number(filters.etoiles_min || 0) === n && 'star-pill-active']"
                  @click="setStarFilter(n)"
                >
                  {{ n }}★
                </button>
              </div>
              <button v-if="filters.etoiles_min" type="button" class="clear-chip mt-2" @click="clearStarFilterAndApply">{{ t('filters.reset') }}</button>
            </div>

            <div>
              <p class="filter-label">{{ t('filters.pricePerNight') }}</p>
              <div class="price-sliders-ltr">
                <input :value="filters.prix_min" @input="onPriceMinInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range mb-2" />
                <input :value="filters.prix_max" @input="onPriceMaxInput" type="range" :min="priceBounds.min" :max="priceBounds.max" step="10" class="price-range" />
              </div>
            </div>

            <div class="flex gap-3">
              <button class="apply-btn flex-1" :disabled="applyingFilters" @click="applyFilters">
                <span v-if="applyingFilters" class="inline-flex items-center gap-2">
                  <span class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                  {{ t('common.loading') }}
                </span>
                <span v-else>{{ t('filters.apply') }}<span v-if="activeSidebarFiltersCount > 0"> ({{ activeSidebarFiltersCount }})</span></span>
              </button>
              <button @click="resetFilters" class="filter-reset-link">{{ t('filters.reset') }}</button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, reactive, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useHotelStore } from '../stores/hotel'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import SearchAutocomplete from '../components/SearchAutocomplete.vue'
import { getServiceLabel } from '../composables/useServiceLabel'

const route = useRoute()
const router = useRouter()
const hotelStore = useHotelStore()
const { t, locale } = useI18n()
const bookingMessage = computed(() => {
  return typeof route.query.booking_message === 'string' ? route.query.booking_message : ''
})
const isRTL = computed(() => locale.value === 'ar')

const sortBy = ref('recommande')
const sortDropdownOpen = ref(false)
const applyingFilters = ref(false)
const mobileFiltersOpen = ref(false)
const tabletPanelOpen = ref(false)
const cardsReady = ref(false)
const gridInView = ref(false)
const animatedCount = ref(0)
const imageLoadedState = reactive({})
const gridRef = ref(null)
const sortDropdownRef = ref(null)
const priceBounds = reactive({ min: 0, max: 3000 })

const sortOptions = computed(() => [
  { value: 'recommande', icon: '⭐', label: t('hotels.sortRecommended') },
  { value: 'prix_asc', icon: '💰', label: t('hotels.sortPriceAsc') },
  { value: 'prix_desc', icon: '💸', label: t('hotels.sortPriceDesc') },
  { value: 'note_desc', icon: '🏆', label: t('hotels.sortBestRated') },
])

const currentSortOption = computed(
  () => sortOptions.value.find((option) => option.value === sortBy.value) || sortOptions.value[0],
)

const amenityOptions = computed(() => [
  { label: t('filters.spa'), value: 'spa' },
  { label: t('filters.pool'), value: 'piscine' },
  { label: t('filters.wifi'), value: 'wifi' },
  { label: t('filters.parking'), value: 'parking' },
  { label: t('filters.ac'), value: 'climatisation' },
  { label: t('filters.restaurant'), value: 'restaurant' },
])

function normalizeAmenitiesQuery(value) {
  if (!value) return []
  if (Array.isArray(value)) return value.map((item) => String(item).trim()).filter(Boolean)
  return String(value)
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
}

const filters = reactive({
  ville: route.query.ville || '',
  dateArrivee: route.query.dateArrivee || '',
  dateDepart: route.query.dateDepart || '',
  etoiles_min: route.query.etoiles || route.query.etoiles_min || '',
  prix_min: route.query.prix_min || '',
  prix_max: route.query.prix_max || '',
  nbVoyageurs: Math.max(1, Math.min(10, Number(route.query.nbVoyageurs || 1) || 1)),
  equipements: normalizeAmenitiesQuery(route.query.services || route.query.equipements),
})

if (route.query.sort && ['recommande', 'prix_asc', 'prix_desc', 'note', 'note_desc'].includes(String(route.query.sort))) {
  sortBy.value = String(route.query.sort) === 'note' ? 'note_desc' : String(route.query.sort)
}

function sortValueForApi(value) {
  return value === 'note_desc' ? 'note' : value
}

function toggleSortDropdown() {
  sortDropdownOpen.value = !sortDropdownOpen.value
}

function closeSortDropdown() {
  sortDropdownOpen.value = false
}

function selectSortOption(option) {
  if (!option?.value) return
  sortBy.value = option.value
  closeSortDropdown()
  applyFilters()
}

const sortedHotels = computed(() => assignUniquePreviewPhotos([...hotelStore.hotels]))

const fallbackPool = [
  'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1400&q=80',
  'https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1400&q=80',
]

function assignUniquePreviewPhotos(list) {
  const used = new Set()
  return list.map((hotel, index) => {
    if (hotel?.previewPhoto) {
      used.add(hotel.previewPhoto)
      return hotel
    }

    const ownPhotos = Array.isArray(hotel?.photos) ? hotel.photos.filter(Boolean) : []
    const pool = ownPhotos.length ? ownPhotos : fallbackPool

    let pick = pool[index % pool.length]
    for (const candidate of pool) {
      if (!used.has(candidate)) {
        pick = candidate
        break
      }
    }

    used.add(pick)
    return { ...hotel, previewPhoto: pick }
  })
}

function resetImageStates() {
  Object.keys(imageLoadedState).forEach((key) => {
    delete imageLoadedState[key]
  })
}

function buildParams(page = 1) {
  const minPrice = Number(filters.prix_min || priceBounds.min)
  const maxPrice = Number(filters.prix_max || priceBounds.max)
  const includePrice = minPrice !== Number(priceBounds.min) || maxPrice !== Number(priceBounds.max)

  return {
    ville: (filters.ville || '').trim(),
    dateArrivee: filters.dateArrivee || '',
    dateDepart: filters.dateDepart || '',
    etoiles: filters.etoiles_min || '',
    prix_min: includePrice ? minPrice : '',
    prix_max: includePrice ? maxPrice : '',
    nbVoyageurs: filters.nbVoyageurs || '',
    services: filters.equipements,
    sort: sortValueForApi(sortBy.value || 'recommande'),
    per_page: 9,
    page,
  }
}

function cleanedParams(params) {
  return Object.fromEntries(
    Object.entries(params).filter(([, v]) => {
      if (Array.isArray(v)) return v.length > 0
      return v !== '' && v !== null && v !== undefined
    }),
  )
}

function syncRoute(page = 1) {
  const params = cleanedParams(buildParams(page))
  router.replace({ query: params })
  return params
}

async function runFetch(page = 1) {
  const params = syncRoute(page)
  resetImageStates()
  cardsReady.value = false
  await hotelStore.fetchHotels(params)
  await nextTick()
  cardsReady.value = true
}

async function applyFilters() {
  if (applyingFilters.value) return
  applyingFilters.value = true
  cardsReady.value = false
  await new Promise((resolve) => setTimeout(resolve, 300))
  await runFetch(1)
  applyingFilters.value = false
  mobileFiltersOpen.value = false
}

function resetFilters() {
  filters.ville = ''
  filters.dateArrivee = ''
  filters.dateDepart = ''
  filters.etoiles_min = ''
  filters.prix_min = Number(priceBounds.min)
  filters.prix_max = Number(priceBounds.max)
  filters.nbVoyageurs = 1
  filters.equipements = []
  sortBy.value = 'recommande'
  applyFilters()
}

function goToPage(page) {
  runFetch(page)
}

function toggleAmenity(service) {
  const index = filters.equipements.indexOf(service)
  if (index >= 0) {
    filters.equipements.splice(index, 1)
    return
  }
  filters.equipements.push(service)
}

async function setStarFilter(value) {
  filters.etoiles_min = Number(filters.etoiles_min) === value ? '' : value
  await applyFilters()
}

function clearStarFilter() {
  filters.etoiles_min = ''
}

async function clearStarFilterAndApply() {
  clearStarFilter()
  await applyFilters()
}

function onPriceMinInput(event) {
  const value = Number(event?.target?.value ?? priceBounds.min)
  const capped = Math.max(Number(priceBounds.min), Math.min(value, Number(filters.prix_max || priceBounds.max)))
  filters.prix_min = capped
}

function onPriceMaxInput(event) {
  const value = Number(event?.target?.value ?? priceBounds.max)
  const capped = Math.min(Number(priceBounds.max), Math.max(value, Number(filters.prix_min || priceBounds.min)))
  filters.prix_max = capped
}

const hasPriceFilter = computed(() => {
  return Number(filters.prix_min || priceBounds.min) !== Number(priceBounds.min)
    || Number(filters.prix_max || priceBounds.max) !== Number(priceBounds.max)
})

const activeSidebarFiltersCount = computed(() => {
  return filters.equipements.length
    + (filters.etoiles_min ? 1 : 0)
    + (hasPriceFilter.value ? 1 : 0)
})

const activeFilterTags = computed(() => {
  const tags = []

  filters.equipements.forEach((service) => {
    tags.push({ key: `service-${service}`, type: 'service', value: service, label: serviceLabel(service) })
  })

  if (filters.etoiles_min) {
    tags.push({ key: 'star-filter', type: 'star', label: `${filters.etoiles_min}★` })
  }

  if (hasPriceFilter.value) {
    tags.push({
      key: 'price-filter',
      type: 'price',
      label: `${Number(filters.prix_min || priceBounds.min)}€-${Number(filters.prix_max || priceBounds.max)}€`,
    })
  }

  return tags
})

async function removeFilterTag(tag) {
  if (!tag) return

  if (tag.type === 'service') {
    const idx = filters.equipements.indexOf(tag.value)
    if (idx >= 0) filters.equipements.splice(idx, 1)
  } else if (tag.type === 'star') {
    filters.etoiles_min = ''
  } else if (tag.type === 'price') {
    filters.prix_min = Number(priceBounds.min)
    filters.prix_max = Number(priceBounds.max)
  }

  await applyFilters()
}

async function loadPriceBounds() {
  try {
    const response = await api.get('/hotels/price-range')
    const min = Number(response?.data?.min || 0)
    const max = Number(response?.data?.max || 0)
    priceBounds.min = Number.isFinite(min) ? min : 0
    priceBounds.max = Number.isFinite(max) && max >= priceBounds.min ? max : priceBounds.min
  } catch {
    priceBounds.min = 0
    priceBounds.max = 3000
  }

  const qMin = Number(route.query.prix_min)
  const qMax = Number(route.query.prix_max)
  filters.prix_min = Number.isFinite(qMin) ? Math.max(priceBounds.min, Math.min(qMin, priceBounds.max)) : priceBounds.min
  filters.prix_max = Number.isFinite(qMax) ? Math.min(priceBounds.max, Math.max(qMax, filters.prix_min)) : priceBounds.max
}

function updateTravelers(delta) {
  const current = Number(filters.nbVoyageurs || 1)
  const next = Math.max(1, Math.min(10, current + delta))
  filters.nbVoyageurs = next
}

function starString(stars) {
  const count = Math.max(1, Math.min(5, Number(stars || 0)))
  return '★'.repeat(count)
}

function serviceLabel(service) {
  return getServiceLabel(service, locale.value)
}

function formatPrice(value) {
  return `${Number(value || 0)}€`
}

function formatPricePerNight(value) {
  return `${formatPrice(value)}/${t('hotels.perNight')}`
}

function getDescription(hotel) {
  const lang = locale.value
  const description = hotel?.description
  if (description && typeof description === 'object' && !Array.isArray(description)) {
    return description[lang] || description.fr || Object.values(description)[0] || ''
  }
  return description || ''
}

function getVille(hotel) {
  const lang = locale.value
  const city = hotel?.ville
  if (city && typeof city === 'object' && !Array.isArray(city)) {
    return city[lang] || city.fr || Object.values(city)[0] || ''
  }
  return city || ''
}

function serviceClass(service) {
  const normalized = String(service || '').toLowerCase()
  const map = {
    spa: 'spa',
    piscine: 'pool',
    restaurant: 'restaurant',
    wifi: 'wifi',
    parking: 'parking',
    climatisation: 'ac',
  }
  return map[normalized] || 'default'
}

function hotelImage(item) {
  if (item?.previewPhoto) return item.previewPhoto
  const photos = Array.isArray(item?.photos) ? item.photos.filter(Boolean) : []
  return photos[0] || 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
}

function onHotelImageLoad(id) {
  imageLoadedState[String(id)] = true
}

function onHotelImageError(event, id) {
  if (!event?.target) return
  event.target.src = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
  imageLoadedState[String(id)] = true
}

watch(
  () => hotelStore.pagination.total,
  (value) => {
    const target = Number(value || 0)
    const start = performance.now()
    const from = animatedCount.value
    const duration = 650

    const tick = (now) => {
      const progress = Math.min(1, (now - start) / duration)
      animatedCount.value = Math.round(from + (target - from) * (1 - Math.pow(1 - progress, 3)))
      if (progress < 1) requestAnimationFrame(tick)
    }

    requestAnimationFrame(tick)
  },
  { immediate: true },
)

let observer = null

onMounted(async () => {
  await loadPriceBounds()
  await runFetch(1)

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          gridInView.value = true
        }
      })
    },
    { threshold: 0.16 },
  )

  if (gridRef.value) {
    observer.observe(gridRef.value.$el || gridRef.value)
  }
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
  document.removeEventListener('click', onDocumentClick)
})

function onDocumentClick(event) {
  if (!sortDropdownRef.value) return
  if (!sortDropdownRef.value.contains(event.target)) {
    closeSortDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap');

.hotels-page {
  font-family: 'Inter', sans-serif;
}

.font-display {
  font-family: 'Playfair Display', serif;
}

.hotels-hero {
  position: relative;
  min-height: 280px;
  background: linear-gradient(135deg, #0a1628 0%, #1a3a6b 50%, #1e56db 100%);
}

.hero-backdrop,
.hero-image-layer {
  position: absolute;
  inset: 0;
}

.hero-backdrop {
  background: radial-gradient(circle at 20% 15%, rgba(245, 158, 11, 0.12), transparent 28%), radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.08), transparent 24%);
}

.hero-image-layer {
  background-image: linear-gradient(180deg, rgba(10, 22, 40, 0.24), rgba(10, 22, 40, 0.84)), url('https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1800&q=80');
  background-size: cover;
  background-position: center;
  opacity: 0.16;
  transform: scale(1.04);
}

.hero-bokeh {
  position: absolute;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  filter: blur(18px);
  animation: floatBokeh 11s ease-in-out infinite;
}

.bokeh-1 { width: 80px; height: 80px; top: 15%; left: 8%; }
.bokeh-2 { width: 130px; height: 130px; top: 42%; left: 22%; animation-delay: -3s; }
.bokeh-3 { width: 110px; height: 110px; top: 12%; right: 18%; animation-delay: -5s; }
.bokeh-4 { width: 160px; height: 160px; bottom: 5%; right: 10%; animation-delay: -7s; }

.search-shell {
  margin-bottom: -56px;
  padding: 18px;
  border-radius: 28px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(16px);
  box-shadow: 0 20px 50px rgba(2, 12, 40, 0.35);
}

.search-grid-rtl {
  direction: rtl;
}

.search-grid-rtl .search-label {
  text-align: right;
}

.search-grid-rtl :deep(.hero-input) {
  text-align: right;
}

.search-field {
  min-width: 0;
}

.search-label {
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: 0.35rem;
}

:deep(.hero-input) {
  width: 100%;
  min-height: 48px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.92);
  color: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 0.9rem 1rem;
  font-weight: 600;
}

:deep(.hero-input::placeholder) {
  color: #475569;
}

.hero-select {
  width: 100%;
  min-height: 48px;
  border-radius: 16px;
  background: rgba(15, 23, 42, 0.86);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.18);
  padding: 0.9rem 1rem;
  font-weight: 600;
}

.star-hero-group {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.5rem;
}

.hero-star-button {
  min-height: 48px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.9);
  color: #0f172a;
  font-weight: 800;
  border: 1px solid rgba(255, 255, 255, 0.35);
}

.hero-star-button-active {
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  box-shadow: 0 14px 28px rgba(249, 115, 22, 0.28);
}

.search-cta {
  min-height: 48px;
  border-radius: 999px;
  padding: 0.9rem 1.2rem;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(90deg, #f59e0b, #f97316);
  box-shadow: 0 16px 30px rgba(249, 115, 22, 0.36);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.search-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 36px rgba(249, 115, 22, 0.46);
}

.search-cta:disabled {
  opacity: 0.8;
  cursor: wait;
}

.premium-counter-pill {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid #f59e0b;
  color: #f59e0b;
  font-weight: 600;
  padding: 6px 16px;
  border-radius: 50px;
  backdrop-filter: blur(10px);
}

.traveler-hero-control {
  min-height: 48px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.4);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 8px;
}

.traveler-btn {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.22);
  background: #ffffff;
  color: #0f172a;
  font-size: 1.05rem;
  font-weight: 800;
}

.traveler-value {
  color: #0f172a;
  font-weight: 800;
  font-size: 0.95rem;
}

.results-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  background: #fff;
  border-radius: 24px 24px 0 0;
  padding: 1.1rem 1.25rem;
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
}

.results-count {
  color: #0f172a;
  font-size: 1.15rem;
  font-weight: 800;
}

.results-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  margin-top: 0.2rem;
}

.results-sort-label {
  color: #334155;
  font-weight: 700;
}

.custom-sort-select {
  position: relative;
}

.custom-sort-trigger {
  min-width: 200px;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: #0f172a;
  color: #fff;
  padding: 0.78rem 0.9rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  box-shadow: 0 16px 36px rgba(2, 6, 23, 0.22);
}

.custom-sort-icon {
  flex-shrink: 0;
}

.custom-sort-label {
  flex: 1;
  text-align: left;
  white-space: nowrap;
}

.custom-sort-arrow {
  color: #fbbf24;
  font-size: 0.82rem;
}

.custom-sort-options-list {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  min-width: 100%;
  background: #1e293b;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  box-shadow: 0 20px 48px rgba(2, 6, 23, 0.45);
  overflow: hidden;
  z-index: 40;
}

.custom-sort-option {
  width: 100%;
  padding: 12px 16px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-align: left;
  border-left: 3px solid transparent;
  transition: background 0.15s ease, border-left-color 0.15s ease, color 0.15s ease;
}

.custom-sort-option:hover {
  background: rgba(245, 158, 11, 0.15);
  border-left-color: #f59e0b;
}

.custom-sort-option-active {
  color: #f59e0b;
  border-left-color: #f59e0b;
}

.custom-sort-option-icon {
  flex-shrink: 0;
}

.custom-sort-option-label {
  flex: 1;
}

.custom-sort-check {
  color: #f59e0b;
  font-weight: 800;
}

.gold-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.95), transparent);
}

.filter-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  padding: 1.25rem;
  overflow: hidden;
  box-shadow: 0 22px 50px rgba(2, 6, 23, 0.22);
}

.filter-title {
  color: #fff;
  font-weight: 800;
  font-size: 1.05rem;
}

.filter-reset-link {
  color: #fbbf24;
  font-weight: 700;
  font-size: 0.9rem;
  transition: opacity 0.2s ease;
}

.filter-reset-link:hover {
  opacity: 0.8;
}

.filter-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.14em;
  font-weight: 800;
  margin-bottom: 0.7rem;
}

.chip-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.star-filter-row {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 0.5rem;
}

.toggle-chip,
.star-pill,
.clear-chip,
.page-pill,
.view-btn {
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, color 0.2s ease, opacity 0.2s ease;
}

.toggle-chip {
  border-radius: 999px;
  padding: 0.72rem 1rem;
  color: #fff;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  font-weight: 700;
  font-size: 0.88rem;
}

.toggle-chip-active {
  background: linear-gradient(135deg, #f59e0b, #f97316);
  box-shadow: 0 12px 24px rgba(245, 158, 11, 0.32);
}

.star-pill {
  width: 100%;
  min-height: 40px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: #e2e8f0;
  font-size: 0.9rem;
  font-weight: 800;
}

.star-pill-active {
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  box-shadow: 0 12px 24px rgba(245, 158, 11, 0.34);
}

.clear-chip {
  color: #fbbf24;
  font-size: 0.85rem;
  font-weight: 700;
}

.price-range-values {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  min-width: 0;
  color: #fff;
  font-weight: 700;
  font-size: 0.92rem;
  margin-bottom: 0.45rem;
  overflow: hidden;
}

.price-range-values span {
  min-width: 0;
  white-space: nowrap;
}

.price-range {
  width: 100%;
  accent-color: #f59e0b;
}

.price-sliders-ltr {
  direction: ltr;
}

.apply-btn {
  width: 100%;
  min-height: 48px;
  border-radius: 999px;
  color: #fff;
  font-weight: 800;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  box-shadow: 0 16px 28px rgba(249, 115, 22, 0.32);
}

.active-filters-summary {
  border: 1px solid rgba(245, 158, 11, 0.3);
  background: rgba(245, 158, 11, 0.08);
  border-radius: 16px;
  padding: 0.75rem 0.9rem;
}

.active-filters-title {
  color: #0f172a;
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 0.45rem;
}

.active-filters-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.active-filter-tag {
  border-radius: 999px;
  border: 1px solid rgba(245, 158, 11, 0.45);
  background: #fff;
  color: #92400e;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.34rem 0.65rem;
}

.hotel-skeleton-card {
  border-radius: 20px;
  overflow: hidden;
  background: #fff;
  box-shadow: var(--he-card-shadow);
}

.hotel-skeleton-image {
  height: 240px;
  background: linear-gradient(90deg, #dbe4f0 25%, #eef3f8 50%, #dbe4f0 75%);
  background-size: 200% 100%;
}

.hotel-skeleton-content {
  padding: 1rem;
}

.shimmer {
  animation: shimmer 1.4s infinite;
}

.shimmer-line {
  height: 12px;
  border-radius: 999px;
  margin-top: 0.65rem;
  background: linear-gradient(90deg, #dbe4f0 25%, #eef3f8 50%, #dbe4f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.premium-hotel-card {
  display: block;
  border-radius: 20px;
  overflow: hidden;
  background: #fff;
  box-shadow: var(--he-card-shadow);
  transform: translateY(0);
  transition: transform 0.4s ease, box-shadow 0.4s ease, border-top-color 0.4s ease;
  border-top: 3px solid transparent;
  opacity: 0;
}

.cards-ready {
  opacity: 1;
}

.premium-hotel-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--he-card-shadow-hover);
  border-top-color: #f59e0b;
}

.hotel-image-wrap {
  position: relative;
  height: 240px;
  overflow: hidden;
}

.hotel-image-skeleton,
.hotel-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.hotel-image {
  object-fit: cover;
  transition: transform 0.4s ease;
}

.premium-hotel-card:hover .hotel-image {
  transform: scale(1.08);
}

.hotel-image-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.12) 55%, rgba(0, 0, 0, 0.7) 100%);
}

.hotel-image-top-left,
.hotel-image-top-right {
  position: absolute;
  z-index: 2;
}

.hotel-image-top-left {
  top: 1rem;
  left: 1rem;
}

.hotel-image-top-right {
  top: 1rem;
  right: 1rem;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.4rem;
}

.star-badge,
.price-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 800;
}

.star-badge {
  color: #fbbf24;
  background: rgba(15, 23, 42, 0.72);
}

.price-badge {
  color: #fff;
  background: rgba(15, 23, 42, 0.72);
}

.rating-badge {
  position: relative;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(8px);
  color: #f59e0b;
  font-weight: 700;
  font-size: 13px;
  padding: 4px 10px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 2;
}

.hotel-content {
  background: #fff;
  padding: 1.1rem 1.1rem 1.25rem;
  overflow: hidden;
}

.hotel-title {
  color: #0f172a;
  font-size: 1.25rem;
  line-height: 1.2;
  font-weight: 800;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hotel-city-text {
  color: #64748b;
  font-size: 0.85rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  margin-top: 0.22rem;
  margin-bottom: 0.55rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hotel-description {
  color: var(--he-slate-500);
  font-size: 0.95rem;
  line-height: 1.55;
  line-clamp: 2;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 3rem;
}

.service-chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-top: 1rem;
}

.service-chip {
  border-radius: 999px;
  padding: 0.42rem 0.7rem;
  font-size: 0.72rem;
  font-weight: 800;
  color: #fff;
}

.service-chip-spa { background: linear-gradient(135deg, #8b5cf6, #a855f7); }
.service-chip-pool { background: linear-gradient(135deg, #1e56db, #38bdf8); }
.service-chip-restaurant { background: linear-gradient(135deg, #f59e0b, #f97316); }
.service-chip-wifi { background: linear-gradient(135deg, #16a34a, #22c55e); }
.service-chip-parking { background: linear-gradient(135deg, #64748b, #334155); }
.service-chip-ac { background: linear-gradient(135deg, #0ea5e9, #06b6d4); }

.hotel-bottom-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1.1rem;
}

.price-label {
  color: #64748b;
  font-size: 0.76rem;
}

.price-value {
  color: #1e56db;
  font-size: 1.5rem;
  font-weight: 900;
}

.view-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border-radius: 999px;
  border: 1px solid #1e56db;
  color: #1e56db;
  padding: 0.72rem 1rem;
  font-weight: 800;
  font-size: 0.9rem;
  transform: translateY(10px);
  opacity: 0;
}

.premium-hotel-card:hover .view-btn {
  transform: translateY(0);
  opacity: 1;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  border-color: transparent;
  color: #fff;
}

.view-arrow {
  display: inline-block;
  transition: transform 0.2s ease;
}

.premium-hotel-card:hover .view-arrow {
  transform: translateX(3px);
}

.empty-state {
  text-align: center;
  padding: 4rem 1rem;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 0.75rem;
}

.empty-title {
  font-size: 1.15rem;
  color: #0f172a;
  font-weight: 800;
}

.empty-subtitle {
  color: #64748b;
  margin-top: 0.4rem;
}

.pagination-wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  margin-top: 1.6rem;
}

.page-pill {
  min-width: 50px;
  height: 50px;
  border-radius: 999px;
  padding: 0 1rem;
  font-weight: 800;
  border: 1px solid rgba(15, 23, 42, 0.14);
}

.page-pill-inactive {
  background: transparent;
  color: #0f172a;
}

.page-pill-active {
  color: #fff;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  border-color: transparent;
  box-shadow: 0 14px 28px rgba(245, 158, 11, 0.25);
}

.page-nav {
  background: #fff;
  color: #0f172a;
}

.page-summary {
  text-align: center;
  color: #64748b;
  font-size: 0.95rem;
  margin-top: 0.85rem;
}

.mobile-filter-fab {
  position: fixed;
  bottom: 1.2rem;
  left: 1.2rem;
  z-index: 60;
  border-radius: 999px;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: #fff;
  font-weight: 800;
  padding: 0.9rem 1.2rem;
  box-shadow: 0 16px 30px rgba(249, 115, 22, 0.3);
}

.mobile-sheet-backdrop {
  position: fixed;
  inset: 0;
  z-index: 80;
  background: rgba(2, 6, 23, 0.62);
}

.mobile-sheet {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  background: #0f172a;
  color: #fff;
  padding: 1rem;
  border-radius: 24px 24px 0 0;
  max-height: 82vh;
  overflow-y: auto;
  overflow-x: hidden;
}

.tablet-panel {
  background: #0f172a;
  border-radius: 24px;
  padding: 1rem;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.tablet-panel-toggle {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #fff;
  font-weight: 800;
  padding: 0.4rem 0;
}

.tablet-panel-body {
  margin-top: 1rem;
}

.cards-stagger-enter-active,
.cards-stagger-leave-active {
  transition: opacity 0.35s ease, transform 0.35s ease;
}

.cards-stagger-enter-from,
.cards-stagger-leave-to {
  opacity: 0;
  transform: translateY(16px);
}

.cards-stagger-enter-to,
.cards-stagger-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.sheet-fade-enter-active,
.sheet-fade-leave-active {
  transition: opacity 0.2s ease;
}

.sheet-fade-enter-from,
.sheet-fade-leave-to {
  opacity: 0;
}

.sort-dropdown-enter-active,
.sort-dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.sort-dropdown-enter-from,
.sort-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.sort-dropdown-enter-to,
.sort-dropdown-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.shimmer {
  animation: shimmer 1.4s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@keyframes floatBokeh {
  0%, 100% { transform: translateY(0) translateX(0); }
  50% { transform: translateY(-18px) translateX(8px); }
}

@media (min-width: 768px) {
  .search-shell {
    margin-bottom: -66px;
  }
}
</style>
