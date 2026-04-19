<template>
  <section class="marketing-reviews-page">
    <header class="reviews-header">
      <div>
        <h2 class="reviews-title">{{ t('dashboard.review_moderation') }}</h2>
        <p class="reviews-subtitle">{{ t('marketing.reviewsSubtitle') }}</p>
      </div>
      <button class="export-btn" :disabled="exporting" @click="exportCsv">
        {{ exporting ? t('common.loading') : t('marketing.exportCsv') }}
      </button>
    </header>

    <div class="tab-row">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="tab-btn"
        :class="activeTab === tab.value ? 'tab-btn-active' : 'tab-btn-idle'"
        @click="changeTab(tab.value)"
      >
        {{ tab.label }}
        <span class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <div v-if="loading" class="space-y-3">
      <div v-for="n in 4" :key="n" class="premium-card skeleton"></div>
    </div>

    <div v-else class="space-y-4">
      <article v-for="review in reviews" :key="review._id" class="premium-card review-card">
        <div class="review-head">
          <div class="review-user-wrap">
            <div class="avatar">{{ review.client_initiales || 'CL' }}</div>
            <div>
              <p class="client-name">{{ review.client_nom }}</p>
              <p class="client-email">{{ review.client_email || '—' }}</p>
            </div>
          </div>

          <div class="review-hotel-meta">
            <RouterLink :to="`/hotels/${review.hotel_id}`" class="hotel-link">{{ review.hotel_nom }}</RouterLink>
            <p class="hotel-sub">{{ review.hotel_ville || '—' }} · {{ formatDisplayDate(review.created_at) }}</p>
          </div>
        </div>

        <div class="review-rating-row">
          <div class="stars-wrap">
            <span
              v-for="i in 5"
              :key="`${review._id}-star-${i}`"
              :class="i <= Number(review.note || 0) ? 'star-filled' : 'star-empty'"
            >{{ i <= Number(review.note || 0) ? '★' : '☆' }}</span>
            <span class="score">{{ Number(review.note || 0) }}/5</span>
          </div>
          <span class="status-pill" :class="statusClass(review.statut)">{{ statusLabel(review.statut) }}</span>
        </div>

        <p class="review-comment">"{{ review.commentaire || '' }}"</p>

        <div class="actions-row">
          <button
              v-if="isPendingReview(review)"
            class="btn-approve"
            :disabled="busyId === review._id"
            @click="approve(review)"
          >✓ {{ t('marketing.approve') }}</button>

          <button
            v-if="isPendingReview(review)"
            class="btn-reject"
            :disabled="busyId === review._id"
            @click="reject(review)"
          >✗ {{ t('marketing.reject') }}</button>

          <button
            v-if="isPendingReview(review) || isPublishedReview(review)"
            class="btn-reply"
            :disabled="busyId === review._id"
            @click="toggleReply(review)"
          >💬 {{ t('marketing.reply') }}</button>
        </div>

        <div v-if="replyingId === review._id && !isRejectedReview(review)" class="reply-box">
          <label class="reply-label">{{ t('marketing.yourReply') }}</label>
          <textarea v-model="replyText" rows="3" class="reply-input" :placeholder="t('marketing.replyPlaceholder')"></textarea>
          <div class="reply-actions">
            <button class="btn-send" :disabled="busyId === review._id || !replyText.trim()" @click="sendReply(review)">
              {{ t('marketing.send') }}
            </button>
            <button class="btn-cancel" :disabled="busyId === review._id" @click="cancelReply">{{ t('common.cancel') }}</button>
          </div>
        </div>

        <div v-if="!isRejectedReview(review) && review.reponse_marketing" class="team-reply-box">
          <p class="team-reply-title">{{ t('marketing.teamReply') }}</p>
          <p class="team-reply-text">"{{ review.reponse_marketing }}"</p>
        </div>
      </article>

      <article v-if="!reviews.length" class="premium-card empty-state">{{ t('common.noResults') }}</article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'

const { t, locale } = useI18n()

const activeTab = ref('pending')
const loading = ref(false)
const exporting = ref(false)
const busyId = ref('')
const replyingId = ref('')
const replyText = ref('')

const reviews = ref([])
const counts = ref({ pending: 0, published: 0, rejected: 0 })

const tabs = computed(() => ([
  { value: 'pending', label: t('marketing.pending'), count: counts.value.pending || 0 },
  { value: 'published', label: t('marketing.published'), count: counts.value.published || 0 },
  { value: 'rejected', label: t('marketing.rejected'), count: counts.value.rejected || 0 },
]))

function formatDisplayDate(value) {
  return formatLocalizedDate(value, locale.value, { day: '2-digit', month: 'long', year: 'numeric' }) || '—'
}

function statusClass(status) {
  if (status === 'published') return 'status-published'
  if (status === 'rejected') return 'status-rejected'
  return 'status-pending'
}

function statusLabel(status) {
  if (status === 'published') return t('marketing.published')
  if (status === 'rejected') return t('marketing.rejected')
  return t('marketing.pending')
}

function isPendingReview(review) {
  return review.statut === 'pending'
}

function isPublishedReview(review) {
  return review.statut === 'published'
}

function isRejectedReview(review) {
  return review.statut === 'rejected'
}

function normalizeReview(item) {
  return {
    _id: String(item?._id || ''),
    note: Number(item?.note || 0),
    commentaire: String(item?.commentaire || ''),
    statut: String(item?.statut || 'pending'),
    created_at: item?.created_at,
    reponse_marketing: String(item?.reponse_marketing || ''),
    client_nom: String(item?.client_nom || 'Client'),
    client_email: String(item?.client_email || ''),
    client_initiales: String(item?.client_initiales || 'CL'),
    hotel_nom: String(item?.hotel_nom || 'Hotel'),
    hotel_ville: String(item?.hotel_ville || ''),
    hotel_id: String(item?.hotel_id || ''),
  }
}

async function fetchReviews() {
  try {
    loading.value = true
    const { data } = await api.get('/marketing/reviews', { params: { status: activeTab.value } })
    reviews.value = Array.isArray(data?.data) ? data.data.map(normalizeReview) : []
    counts.value = {
      pending: Number(data?.counts?.pending || 0),
      published: Number(data?.counts?.published || 0),
      rejected: Number(data?.counts?.rejected || 0),
    }
  } catch {
    reviews.value = []
    counts.value = { pending: 0, published: 0, rejected: 0 }
  } finally {
    loading.value = false
  }
}

async function changeTab(tab) {
  if (activeTab.value === tab) return
  activeTab.value = tab
  replyingId.value = ''
  replyText.value = ''
  await fetchReviews()
}

async function approve(review) {
  try {
    busyId.value = review._id
    await api.put(`/marketing/reviews/${encodeURIComponent(review._id)}/approve`)
    await fetchReviews()
  } finally {
    busyId.value = ''
  }
}

async function reject(review) {
  const ok = window.confirm(t('marketing.confirmReject'))
  if (!ok) return
  try {
    busyId.value = review._id
    await api.put(`/marketing/reviews/${encodeURIComponent(review._id)}/reject`)
    await fetchReviews()
  } finally {
    busyId.value = ''
  }
}

function toggleReply(review) {
  if (replyingId.value === review._id) {
    replyingId.value = ''
    replyText.value = ''
    return
  }
  replyingId.value = review._id
  replyText.value = review.reponse_marketing || ''
}

function cancelReply() {
  replyingId.value = ''
  replyText.value = ''
}

async function sendReply(review) {
  if (!replyText.value.trim()) return
  try {
    busyId.value = review._id
    await api.put(`/marketing/reviews/${encodeURIComponent(review._id)}/reply`, {
      reponse: replyText.value.trim(),
    })
    replyingId.value = ''
    replyText.value = ''
    await fetchReviews()
  } finally {
    busyId.value = ''
  }
}

async function exportCsv() {
  try {
    exporting.value = true
    const response = await api.get('/marketing/reviews/export-csv', {
      params: { status: activeTab.value },
      responseType: 'blob',
    })

    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `marketing-reviews-${activeTab.value}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

onMounted(fetchReviews)
</script>

<style scoped>
.marketing-reviews-page {
  --bg-card: #1e293b;
  --text-main: #e2e8f0;
  --text-soft: #94a3b8;
}

.reviews-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.8rem;
  margin-bottom: 1rem;
}

.reviews-title {
  color: var(--text-main);
  font-size: 1.6rem;
  font-weight: 800;
}

.reviews-subtitle {
  color: var(--text-soft);
  margin-top: 0.3rem;
  font-size: 0.92rem;
}

.export-btn {
  background: #0ea5e9;
  color: white;
  border-radius: 0.75rem;
  font-size: 0.82rem;
  padding: 0.55rem 0.9rem;
  font-weight: 700;
}

.export-btn:disabled { opacity: 0.6; }

.tab-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.tab-btn {
  border-radius: 999px;
  padding: 0.48rem 0.85rem;
  font-size: 0.82rem;
  font-weight: 700;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.tab-btn-active { background: #0ea5e9; color: white; }
.tab-btn-idle { background: #1e293b; color: #cbd5e1; }

.tab-count {
  margin-inline-start: 0.35rem;
  padding: 0.08rem 0.35rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
}

.premium-card {
  background: var(--bg-card);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 20px;
  color: var(--text-main);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.premium-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(2, 6, 23, 0.36);
}

.skeleton {
  min-height: 160px;
  animation: pulse 1.2s infinite;
}

@keyframes pulse {
  0% { opacity: 0.4; }
  50% { opacity: 0.75; }
  100% { opacity: 0.4; }
}

.review-head {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.review-user-wrap {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.avatar {
  width: 2.45rem;
  height: 2.45rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: white;
  background: linear-gradient(135deg, #38bdf8, #2563eb);
}

.client-name { font-weight: 700; color: #f1f5f9; }
.client-email { color: var(--text-soft); font-size: 0.82rem; }

.review-hotel-meta { text-align: end; }
.hotel-link { color: #fbbf24; font-weight: 700; }
.hotel-sub { color: var(--text-soft); font-size: 0.82rem; }

.review-rating-row {
  margin-top: 0.7rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.6rem;
}

.stars-wrap { display: flex; gap: 0.1rem; align-items: center; }
.star-filled { color: #fbbf24; }
.star-empty { color: #64748b; }
.score { margin-inline-start: 0.4rem; color: #cbd5e1; font-size: 0.85rem; }

.status-pill {
  padding: 0.2rem 0.62rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
}

.status-pending { background: rgba(245, 158, 11, 0.16); color: #f59e0b; }
.status-published { background: rgba(16, 185, 129, 0.16); color: #34d399; }
.status-rejected { background: rgba(239, 68, 68, 0.16); color: #f87171; }

.review-comment { margin-top: 0.7rem; color: #e2e8f0; font-style: italic; }

.actions-row {
  margin-top: 0.85rem;
  display: flex;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.actions-row button {
  border-radius: 0.7rem;
  padding: 0.42rem 0.74rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.btn-approve { background: rgba(16, 185, 129, 0.22); color: #34d399; }
.btn-reject { background: rgba(239, 68, 68, 0.2); color: #f87171; }
.btn-reply { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }

.reply-box {
  margin-top: 0.85rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 0.72rem;
  background: rgba(15, 23, 42, 0.45);
}

.reply-label { display: block; color: #cbd5e1; margin-bottom: 0.42rem; font-size: 0.82rem; }

.reply-input {
  width: 100%;
  border-radius: 10px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  background: rgba(2, 6, 23, 0.55);
  color: #e2e8f0;
  padding: 0.52rem 0.66rem;
}

.reply-actions {
  margin-top: 0.52rem;
  display: flex;
  gap: 0.4rem;
  justify-content: end;
}

.reply-actions button {
  border-radius: 0.62rem;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.36rem 0.72rem;
}

.btn-send { background: #0ea5e9; color: white; }
.btn-cancel { background: rgba(100, 116, 139, 0.2); color: #cbd5e1; }

.team-reply-box {
  margin-top: 0.9rem;
  border-top: 1px dashed rgba(148, 163, 184, 0.35);
  padding-top: 0.65rem;
}

.team-reply-title { color: #cbd5e1; font-size: 0.82rem; margin-bottom: 0.2rem; }
.team-reply-text { color: #e2e8f0; }

.empty-state { text-align: center; color: #94a3b8; }
</style>
