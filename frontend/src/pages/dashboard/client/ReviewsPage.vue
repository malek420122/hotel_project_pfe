<template>
  <div>
    <div class="mb-6 flex items-center justify-between gap-3">
      <h2 class="text-2xl font-bold text-gray-800">{{ t('reviews.myReviews') }}</h2>
      <span v-if="myReviews.length" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
        {{ myReviews.length }}
      </span>
    </div>

    <div
      v-if="successMsg"
      class="fixed top-6 right-6 z-40 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-lg"
    >
      {{ successMsg }}
    </div>

    <p v-if="errorMsg" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ errorMsg }}</p>

    <div v-if="loading" class="space-y-4">
      <div class="card p-5">
        <div class="skeleton-line w-48 mb-4"></div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[150px_1fr]">
          <div class="skeleton-box h-36 w-full rounded-xl"></div>
          <div class="space-y-3">
            <div class="skeleton-line w-64"></div>
            <div class="skeleton-line w-40"></div>
            <div class="skeleton-line w-52"></div>
            <div class="skeleton-box h-24 w-full rounded-xl"></div>
          </div>
        </div>
      </div>
      <div class="card p-5">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[120px_1fr]">
          <div class="skeleton-box h-28 w-full rounded-xl"></div>
          <div class="space-y-3">
            <div class="skeleton-line w-56"></div>
            <div class="skeleton-line w-44"></div>
            <div class="skeleton-line w-full"></div>
          </div>
        </div>
      </div>
    </div>

    <section v-else-if="eligibleReservations.length" class="mb-8 space-y-4">
      <h3 class="text-lg font-bold text-gray-800">{{ t('reviews.leaveReview') }}</h3>

      <article
        v-for="reservation in eligibleReservations"
        :key="reservation._id"
        class="card border border-amber-100"
      >
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[150px_1fr] lg:items-start">
          <img
            :src="hotelImage(reservation.hotel)"
            :alt="reservation.hotel?.nom || t('common.hotel')"
            class="h-36 w-full rounded-xl object-cover"
          />

          <div>
            <RouterLink
              :to="`/hotels/${reservation.hotel?._id || reservation.hotelId}`"
              class="text-lg font-bold text-gray-800 hover:text-secondary"
            >
              {{ reservation.hotel?.nom || '-' }}
            </RouterLink>
            <p class="mt-1 text-sm text-slate-500">
              {{ hotelCity(reservation.hotel) }}
              <span v-if="Number(reservation.hotel?.etoiles || 0) > 0" class="ml-2 text-amber-500">{{ '★'.repeat(Number(reservation.hotel?.etoiles || 0)) }}</span>
            </p>
            <p class="mt-1 text-sm text-slate-500">
              {{ t('reviews.reference') }}: {{ reservation.reference || '-' }}
              · {{ t('reviews.roomType') }}: {{ getRoomTypeName(reservation.chambre?.type) }}
            </p>
            <p class="mt-1 text-sm text-gray-500">{{ t('reviews.stayFromTo', { range: formatDateRange(reservation.dateArrivee, reservation.dateDepart) }) }}</p>

            <div class="mt-3 flex items-center gap-1">
              <button
                v-for="star in 5"
                :key="`star-${reservation._id}-${star}`"
                type="button"
                class="text-2xl leading-none transition-transform hover:scale-110"
                :class="star <= activeStar(reservation._id) ? 'text-amber-400' : 'text-slate-300'"
                @mouseenter="setHoverStar(reservation._id, star)"
                @mouseleave="setHoverStar(reservation._id, 0)"
                @click="setSelectedStar(reservation._id, star)"
              >
                ★
              </button>
            </div>

            <textarea
              v-model="reviewDrafts[String(reservation._id)].commentaire"
              rows="4"
              class="input-field mt-3"
              :placeholder="t('reviews.yourCommentPlaceholder')"
            />

            <button
              class="btn-primary mt-3"
              :disabled="reviewDrafts[String(reservation._id)].submitting"
              @click="submitReview(reservation)"
            >
              <span v-if="reviewDrafts[String(reservation._id)].submitting">{{ t('common.loading') }}</span>
              <span v-else>{{ t('reviews.submitReview') }}</span>
            </button>
          </div>
        </div>
      </article>
    </section>

    <section v-if="myReviews.length" class="space-y-4">
      <h3 class="text-lg font-bold text-gray-800">{{ t('reviews.myReviews') }} ({{ myReviews.length }})</h3>

      <article v-for="item in myReviews" :key="item._id" class="card">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[120px_1fr]">
          <img
            :src="hotelImage(item.hotel)"
            :alt="item.hotel?.nom || t('common.hotel')"
            class="h-28 w-full rounded-xl object-cover"
          />

          <div>
            <RouterLink
              :to="`/hotels/${item.hotel?._id || item.hotel_id}`"
              class="text-lg font-bold text-gray-800 hover:text-secondary"
            >
              {{ item.hotel?.nom || '-' }}
            </RouterLink>
            <p class="mt-1 text-sm text-slate-500">
              {{ hotelCity(item.hotel) }}
              <span v-if="Number(item.hotel?.etoiles || 0) > 0" class="ml-2 text-amber-500">{{ '★'.repeat(Number(item.hotel?.etoiles || 0)) }}</span>
            </p>
            <p class="mt-1 text-sm text-slate-500">
              {{ t('reviews.reference') }}: {{ item.reservation?.reference || '-' }}
              <span v-if="item.reservation?.chambre?.type">· {{ t('reviews.roomType') }}: {{ getRoomTypeName(item.reservation?.chambre?.type) }}</span>
            </p>
            <p class="mt-1 text-sm text-amber-500">{{ starText(Number(item.note || 0)) }} • {{ formatDisplayDate(item.created_at) }}</p>
            <p class="mt-2 text-sm italic text-gray-600">"{{ item.commentaire }}"</p>

            <p
              v-if="item.reponse_marketing || item.reponseHotel"
              class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-700"
            >
              {{ t('reviews.teamResponse') }}: {{ item.reponse_marketing || item.reponseHotel }}
            </p>
          </div>
        </div>
      </article>
    </section>

    <section
      v-if="!loading && !eligibleReservations.length && !myReviews.length"
      class="card text-center"
    >
      <p class="text-4xl">⭐</p>
      <p class="mt-2 text-lg font-bold text-gray-800">{{ t('reviews.noReviewsYet') }}</p>
      <p class="mt-1 text-sm text-gray-500">{{ t('reviews.noReviewsHint') }}</p>
      <RouterLink to="/hotels" class="btn-primary mt-4 inline-block">{{ t('reviews.browseHotels') }}</RouterLink>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const { t, locale } = useI18n()

const loading = ref(false)
const reservations = ref([])
const myReviews = ref([])
const errorMsg = ref('')
const successMsg = ref('')
const reviewDrafts = reactive({})

const eligibleReservations = computed(() => reservations.value)

function ensureDraft(reservationId) {
  const key = String(reservationId)
  if (!reviewDrafts[key]) {
    reviewDrafts[key] = {
      note: 0,
      hover: 0,
      commentaire: '',
      submitting: false,
    }
  }
}

function activeStar(reservationId) {
  ensureDraft(reservationId)
  const draft = reviewDrafts[String(reservationId)]
  return draft.hover || draft.note
}

function setHoverStar(reservationId, value) {
  ensureDraft(reservationId)
  reviewDrafts[String(reservationId)].hover = Number(value || 0)
}

function setSelectedStar(reservationId, value) {
  ensureDraft(reservationId)
  reviewDrafts[String(reservationId)].note = Number(value || 0)
}

function formatDisplayDate(value) {
  return formatLocalizedDate(value, locale.value, { day: '2-digit', month: 'long', year: 'numeric' }) || '-'
}

function formatDateRange(start, end) {
  const startText = formatDisplayDate(start)
  const endText = formatDisplayDate(end)
  return locale.value === 'ar' ? `${endText} ← ${startText}` : `${startText} → ${endText}`
}

function starText(note) {
  const safe = Math.max(0, Math.min(5, Number(note || 0)))
  return `${'★'.repeat(safe)}${'☆'.repeat(5 - safe)}`
}

function hotelCity(hotel) {
  const value = hotel?.ville
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return value[locale.value] || value.fr || value.en || value.ar || Object.values(value)[0] || '-'
  }
  return String(value || '-')
}

function getRoomTypeName(type) {
  const normalized = String(type || '').toLowerCase().trim()
  const labels = {
    simple: t('booking.roomSingle'),
    double: t('booking.roomDouble'),
    suite: t('booking.roomSuite'),
    deluxe: t('room.deluxe'),
  }
  return labels[normalized] || String(type || '-')
}

function hotelImage(hotel) {
  if (hotel?.previewPhoto) return hotel.previewPhoto
  if (Array.isArray(hotel?.photos) && hotel.photos.length) return hotel.photos[0]
  return 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'
}

async function loadData() {
  loading.value = true
  errorMsg.value = ''

  try {
    const [reservationsResponse, reviewsResponse] = await Promise.all([
      api.get('/client/reviews/reviewable'),
      api.get('/client/reviews'),
    ])

    reservations.value = Array.isArray(reservationsResponse.data) ? reservationsResponse.data : []
    myReviews.value = Array.isArray(reviewsResponse.data) ? reviewsResponse.data : []

    eligibleReservations.value.forEach((reservation) => ensureDraft(reservation._id))
  } catch (error) {
    errorMsg.value = error?.response?.data?.message || t('reviews.loadError')
    reservations.value = []
    myReviews.value = []
  } finally {
    loading.value = false
  }
}

async function submitReview(reservation) {
  const reservationId = String(reservation?._id || '')
  if (!reservationId) return

  ensureDraft(reservationId)
  const draft = reviewDrafts[reservationId]

  if (!draft.note) {
    errorMsg.value = t('reviews.ratingRequired')
    return
  }

  if (!draft.commentaire || draft.commentaire.trim().length < 10) {
    errorMsg.value = t('reviews.commentMinLength')
    return
  }

  try {
    errorMsg.value = ''
    draft.submitting = true

    await api.post('/client/reviews', {
      hotel_id: reservation.hotel?._id || reservation.hotelId,
      reservation_id: reservation._id,
      note: Number(draft.note),
      commentaire: draft.commentaire.trim(),
    })

    successMsg.value = t('reviews.submitSuccess')
    setTimeout(() => {
      successMsg.value = ''
    }, 2500)

    await loadData()
  } catch (error) {
    errorMsg.value = error?.response?.data?.message || t('reviews.submitError')
  } finally {
    draft.submitting = false
  }
}

onMounted(loadData)
</script>

<style scoped>
.skeleton-box,
.skeleton-line {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 37%, #e2e8f0 63%);
  background-size: 400% 100%;
  animation: reviews-shimmer 1.4s ease infinite;
}

.skeleton-line {
  height: 0.85rem;
  border-radius: 999px;
}

@keyframes reviews-shimmer {
  0% {
    background-position: 100% 50%;
  }
  100% {
    background-position: 0 50%;
  }
}
</style>
