<template>
  <div class="special-requests-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">{{ t('reception.specialRequests.title') }}</h1>
        <p class="page-subtitle">{{ t('reception.specialRequests.subtitle') || 'Gérez les demandes personnalisées des clients en temps réel.' }}</p>
      </div>
      <button @click="fetchRequests" class="refresh-btn" :class="{ 'refreshing': loading }" title="Actualiser">
        <RefreshCw :size="18" />
        <span>{{ t('common.refresh') || 'Actualiser' }}</span>
      </button>
    </div>

    <div class="tabs-bar">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="tab-item"
        :class="{ 'tab-item-active': selectedTab === tab.value }"
        @click="selectedTab = tab.value"
      >
        <component :is="tab.icon" :size="18" class="tab-icon" />
        <span class="tab-label">{{ tab.label }}</span>
        <span v-if="tab.count !== undefined" class="tab-count">{{ tab.count }}</span>
      </button>
    </div>

    <div v-if="loading && !requests.length" class="loading-state">
      <div v-for="n in 3" :key="n" class="skeleton-card"></div>
    </div>

    <div v-else-if="filteredRequests.length > 0" class="requests-grid">
      <transition-group name="list" tag="div" class="list-container">
        <div 
          v-for="req in filteredRequests" 
          :key="req.id" 
          class="request-card"
          :class="[`priority-${req.priority.toLowerCase()}`, { 'is-processed': req.status === 'TRAITE' }]"
        >
          <div class="priority-strip"></div>
          
          <div class="card-content">
            <div class="card-header">
              <div class="user-meta">
                <div class="avatar-wrapper">
                  <div class="avatar-bg"></div>
                  <User :size="20" class="relative z-10" />
                </div>
                <div class="user-details">
                  <h4 class="client-name">{{ req.client }}</h4>
                  <div class="room-info">
                    <Home :size="13" />
                    <span>{{ req.roomLabel }}</span>
                  </div>
                </div>
              </div>
              
              <div class="status-badges">
                <div :class="['priority-badge', `priority-${req.priority.toLowerCase()}`]">
                  <Zap v-if="req.priority === 'URGENT'" :size="12" />
                  <Info v-else :size="12" />
                  <span>{{ priorityLabel(req.priority) }}</span>
                </div>
                <div v-if="req.status === 'TRAITE'" class="status-badge processed">
                  <CheckCircle :size="12" />
                  <span>{{ t('reception.specialRequests.processed') }}</span>
                </div>
              </div>
            </div>

            <div class="request-main">
              <div class="quote-container">
                <MessageSquare :size="24" class="quote-icon-bg" />
                <p class="request-text">{{ req.demande }}</p>
              </div>
            </div>

            <div class="card-footer">
              <div class="meta-pills">
                <div class="meta-pill">
                  <Building :size="12" />
                  <span>{{ req.hotel }}</span>
                </div>
                <div class="meta-pill">
                  <Calendar :size="12" />
                  <span>{{ req.arrivee }} - {{ req.depart }}</span>
                </div>
                <div class="meta-pill">
                  <Clock :size="12" />
                  <span>{{ req.heure }}</span>
                </div>
              </div>

              <div class="actions">
                <button v-if="req.status !== 'TRAITE'" @click="markDone(req)" class="btn-action success" :title="t('reception.specialRequests.markProcessed')">
                  <Check :size="18" />
                </button>
                <button @click="openDetails(req)" class="btn-action info" title="Détails">
                  <Eye :size="18" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <div v-else class="empty-state">
      <div class="empty-illustration">
        <ClipboardList :size="64" />
        <div class="illustration-rings"></div>
      </div>
      <h3 class="empty-title">{{ t('reception.specialRequests.noRequests') || 'Aucune demande' }}</h3>
      <p class="empty-text">{{ selectedTab === 'pending' ? 'Toutes les demandes ont été traitées. Beau travail !' : 'Aucune demande ne correspond à ce filtre.' }}</p>
    </div>

    <!-- Modal Détails -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="selected" class="modal-overlay" @click.self="selected = null">
          <div class="modal-card shadow-2xl">
            <div class="modal-header">
              <div class="header-main">
                <h3 class="modal-title">Détails de la demande</h3>
                <p class="modal-subtitle">{{ selected.client }} • {{ selected.roomLabel }}</p>
              </div>
              <button class="modal-close-btn" @click="selected = null">
                <X :size="20" />
              </button>
            </div>

            <div class="modal-body custom-scrollbar">
              <div class="modal-info-grid">
                <div class="info-group">
                  <label>Réservation</label>
                  <div class="info-content">
                    <div class="info-item">
                      <Building :size="16" />
                      <span>{{ selected.hotel }}</span>
                    </div>
                    <div class="info-item">
                      <Key :size="16" />
                      <span>Chambre {{ selected.roomNumber }}</span>
                    </div>
                    <div class="info-item">
                      <Calendar :size="16" />
                      <span>{{ selected.arrivee }} au {{ selected.depart }}</span>
                    </div>
                  </div>
                </div>

                <div class="info-group">
                  <label>Statut & Urgence</label>
                  <div class="info-content">
                    <div :class="['badge-large', `priority-${selected.priority.toLowerCase()}`]">
                      {{ priorityLabel(selected.priority) }}
                    </div>
                    <div :class="['badge-large', selected.status === 'TRAITE' ? 'processed' : 'pending']">
                      {{ selected.status === 'TRAITE' ? 'Traitée' : 'En attente' }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-request-box">
                <label>Message du client</label>
                <div class="request-content-text">
                  {{ selected.demande }}
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn-secondary" @click="selected = null">{{ t('common.close') }}</button>
              <button 
                v-if="selected.status !== 'TRAITE'" 
                class="btn-success-large" 
                @click="markDone(selected)"
              >
                <Check :size="20" />
                <span>Marquer comme traitée</span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'
import { 
  ClipboardList, 
  Clock, 
  CheckCircle, 
  Zap, 
  Info, 
  Calendar, 
  Home, 
  User, 
  Building, 
  Key, 
  Eye, 
  Check, 
  X,
  RefreshCw,
  MessageSquare
} from 'lucide-vue-next'

const { t, locale } = useI18n()
const requests = ref([])
const loading = ref(true)
const selectedTab = ref('pending')
const selected = ref(null)

function getResId(r) {
  if (!r) return ''
  return r.id || r._id || String(r)
}

const tabs = computed(() => {
  const pendingCount = requests.value.filter(r => r.status !== 'TRAITE').length
  return [
    { value: 'pending', label: 'En attente', icon: Clock, count: pendingCount },
    { value: 'processed', label: 'Traitées', icon: CheckCircle },
    { value: 'all', label: 'Toutes', icon: ClipboardList },
  ]
})

const filteredRequests = computed(() => {
  if (selectedTab.value === 'pending') {
    return requests.value.filter((req) => req.status !== 'TRAITE')
  }
  if (selectedTab.value === 'processed') {
    return requests.value.filter((req) => req.status === 'TRAITE')
  }
  return requests.value
})

function priorityLabel(priority) {
  if (priority === 'URGENT') return 'Urgent'
  if (priority === 'LOW') return 'Basse'
  return 'Normale'
}

function mapRequest(req) {
  return {
    id: getResId(req),
    client: req.client || 'Client inconnu',
    roomLabel: req.chambre || req.roomNumber || 'Chambre',
    roomNumber: req.roomNumber || req.chambre || '-',
    hotel: req.hotel || '-',
    demande: req.details || req.demande || '',
    heure: req.heure || '-',
    arrivee: formatLocalizedDate(req.arrivee, locale.value, { day: '2-digit', month: 'short' }) || '-',
    depart: formatLocalizedDate(req.depart, locale.value, { day: '2-digit', month: 'short' }) || '-',
    priority: req.priority || 'NORMAL',
    status: req.status || 'EN_ATTENTE',
  }
}

async function fetchRequests() {
  try {
    loading.value = true
    const { data } = await api.get('/reservations/special-requests')
    requests.value = Array.isArray(data) ? data.map(mapRequest) : []
  } catch (error) {
    console.error('Fetch error:', error)
    requests.value = []
  } finally {
    loading.value = false
  }
}

function openDetails(req) {
  selected.value = req
}

async function markDone(req) {
  try {
    const id = req.id
    await api.put(`/reservations/${id}/special-request/done`)
    if (selected.value && selected.value.id === id) {
      selected.value = null
    }
    await fetchRequests()
  } catch (error) {
    console.error('Mark done error:', error)
  }
}

onMounted(fetchRequests)
</script>

<style scoped>
.special-requests-container {
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 3rem;
  animation: pageFadeIn 0.5s ease-out;
}

@keyframes pageFadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
}

.page-title {
  font-size: 2.25rem;
  font-weight: 900;
  color: #1e293b;
  letter-spacing: -0.04em;
  margin-bottom: 0.25rem;
}

.page-subtitle {
  color: #64748b;
  font-size: 1.05rem;
  font-weight: 500;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  color: #1e293b;
  font-weight: 700;
  font-size: 0.9rem;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.refresh-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
  border-color: #cbd5e1;
}

.refreshing svg {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Tabs */
.tabs-bar {
  display: flex;
  gap: 0.75rem;
  background: rgba(241, 245, 249, 0.5);
  backdrop-filter: blur(8px);
  padding: 0.5rem;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2.5rem;
  width: fit-content;
}

.tab-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.5rem;
  border-radius: 16px;
  font-weight: 800;
  font-size: 0.9rem;
  color: #64748b;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tab-item:hover {
  color: #1e293b;
  background: white;
}

.tab-item-active {
  background: #1e293b;
  color: white !important;
  box-shadow: 0 10px 20px -5px rgba(30, 41, 59, 0.3);
}

.tab-count {
  background: #f43f5e;
  color: white;
  font-size: 0.7rem;
  padding: 2px 8px;
  border-radius: 99px;
  min-width: 20px;
  text-align: center;
}

/* Grid & Cards */
.list-container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

.request-card {
  background: white;
  border-radius: 24px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  display: flex;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
}

.request-card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08);
  border-color: #cbd5e1;
}

.priority-strip {
  width: 8px;
  background: #cbd5e1;
  flex-shrink: 0;
}

.priority-urgent .priority-strip { background: #f43f5e; }
.priority-low .priority-strip { background: #10b981; }
.priority-normal .priority-strip { background: #f59e0b; }

.is-processed {
  opacity: 0.75;
}

.card-content {
  flex-grow: 1;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.user-meta {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.avatar-wrapper {
  position: relative;
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1e293b;
}

.avatar-bg {
  position: absolute;
  inset: 0;
  background: #f1f5f9;
  border-radius: 18px;
  transform: rotate(4deg);
  transition: transform 0.3s ease;
}

.request-card:hover .avatar-bg {
  transform: rotate(12deg) scale(1.1);
}

.client-name {
  font-size: 1.25rem;
  font-weight: 900;
  color: #1e293b;
  letter-spacing: -0.02em;
}

.room-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #64748b;
  margin-top: 0.2rem;
}

.status-badges {
  display: flex;
  gap: 0.5rem;
}

.priority-badge, .status-badge {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.8rem;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
}

.priority-badge.priority-urgent { background: #fff1f2; color: #e11d48; }
.priority-badge.priority-normal { background: #fffbeb; color: #d97706; }
.priority-badge.priority-low { background: #f0fdf4; color: #166534; }
.status-badge.processed { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

.request-main {
  margin-bottom: 2rem;
}

.quote-container {
  position: relative;
  background: #f8fafc;
  padding: 1.5rem;
  border-radius: 20px;
  border: 1px solid #f1f5f9;
}

.quote-icon-bg {
  position: absolute;
  top: -12px;
  right: 15px;
  color: #e2e8f0;
  opacity: 0.5;
}

.request-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: #334155;
  line-height: 1.6;
  position: relative;
  z-index: 1;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px dashed #e2e8f0;
}

.meta-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.meta-pill {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #94a3b8;
  background: #f8fafc;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
}

.actions {
  display: flex;
  gap: 0.6rem;
}

.btn-action {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-action.success {
  background: #10b981;
  color: white;
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
}

.btn-action.success:hover {
  background: #059669;
  transform: translateY(-2px);
}

.btn-action.info {
  background: white;
  border: 1px solid #e2e8f0;
  color: #64748b;
}

.btn-action.info:hover {
  background: #f8fafc;
  color: #1e293b;
  border-color: #cbd5e1;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 5rem 2rem;
  background: white;
  border-radius: 32px;
  border: 1px solid #e2e8f0;
}

.empty-illustration {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e2e8f0;
}

.illustration-rings {
  position: absolute;
  inset: 0;
  border: 2px solid #f1f5f9;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); opacity: 1; }
  100% { transform: scale(1.5); opacity: 0; }
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 900;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #64748b;
  max-width: 320px;
  margin: 0 auto;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.5rem;
}

.modal-card {
  background: white;
  width: 100%;
  max-width: 600px;
  border-radius: 32px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 900;
  color: #1e293b;
  letter-spacing: -0.03em;
}

.modal-subtitle {
  color: #64748b;
  font-weight: 600;
  margin-top: 0.1rem;
}

.modal-close-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: white;
  border: 1px solid #e2e8f0;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.modal-body {
  padding: 2rem;
}

.modal-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2.5rem;
  margin-bottom: 2.5rem;
}

.info-group label {
  display: block;
  font-size: 0.75rem;
  font-weight: 900;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1rem;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
  color: #475569;
}

.badge-large {
  display: inline-flex;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-weight: 800;
  font-size: 0.85rem;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.badge-large.priority-urgent { background: #fff1f2; color: #e11d48; }
.badge-large.priority-normal { background: #fffbeb; color: #d97706; }
.badge-large.processed { background: #f1f5f9; color: #475569; }
.badge-large.pending { background: #fef2f2; color: #ef4444; }

.modal-request-box {
  padding-top: 2rem;
  border-top: 1px dashed #e2e8f0;
}

.modal-request-box label {
  display: block;
  font-size: 0.75rem;
  font-weight: 900;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1rem;
}

.request-content-text {
  background: #f8fafc;
  padding: 1.75rem;
  border-radius: 24px;
  font-size: 1.15rem;
  font-weight: 600;
  line-height: 1.8;
  color: #334155;
  border: 1px solid #f1f5f9;
}

.modal-footer {
  padding: 1.5rem 2rem;
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  font-weight: 800;
  color: #64748b;
  border-radius: 14px;
}

.btn-success-large {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.75rem;
  background: #10b981;
  color: white;
  border-radius: 14px;
  font-weight: 800;
  box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);
  transition: all 0.2s;
}

.btn-success-large:hover {
  background: #059669;
  transform: translateY(-2px);
}

/* Transitions */
.list-enter-active, .list-leave-active { transition: all 0.4s ease; }
.list-enter-from, .list-leave-to { opacity: 0; transform: scale(0.9); }

.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }

@media (max-width: 640px) {
  .modal-info-grid { grid-template-columns: 1fr; gap: 1.5rem; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
</style>
