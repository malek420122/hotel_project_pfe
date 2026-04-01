<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Mes Réservations</h2>
      <RouterLink to="/dashboard/client/new-booking" class="btn-primary text-sm">+ Nouvelle réservation</RouterLink>
    </div>
    <!-- Filters -->
    <div class="card mb-6 flex flex-wrap gap-3">
      <button v-for="s in statuts" :key="s.val" @click="filterStatus = s.val"
        :class="['px-4 py-2 rounded-xl text-sm font-medium transition-colors', filterStatus === s.val ? 'bg-secondary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">
        {{ s.label }}
      </button>
    </div>
    <div v-if="bookingStore.loading" class="flex justify-center py-20">
      <div class="w-10 h-10 border-4 border-secondary border-t-transparent rounded-full animate-spin"></div>
    </div>
    <div v-else class="space-y-4">
      <div v-for="res in filtered" :key="res._id" class="card border hover:shadow-lg transition-shadow">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="w-full md:w-32 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
            <img :src="res.hotel?.photos?.[0] || '/placeholder-hotel.jpg'" class="w-full h-full object-cover" />
          </div>
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-bold text-gray-800 text-lg">{{ res.hotel?.nom }}</p>
                <p class="text-gray-500 text-sm">🛏️ {{ res.chambre?.nom }} · {{ res.chambre?.type }}</p>
                <p class="text-gray-500 text-sm">📅 {{ formatDate(res.dateArrivee) }} → {{ formatDate(res.dateDepart) }} ({{ nightCount(res) }} nuit(s))</p>
              </div>
              <StatusBadge :status="res.statut" />
            </div>
            <div class="flex items-center gap-4 mt-3">
              <span class="text-lg font-bold text-secondary">{{ res.prixTotal }}€</span>
              <span class="text-xs text-gray-400">Réf: {{ res.reference }}</span>
              <div class="flex gap-2 ml-auto flex-wrap">
                <button @click="showInvoice(res)" class="btn-outline text-xs py-1.5 px-3">📄 Facture</button>
                <button v-if="['EN_ATTENTE','CONFIRMEE'].includes(res.statut)" @click="confirmCancel(res)"
                  class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-600 border border-red-200 hover:bg-red-100">🗑️ Annuler</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="!filtered.length" class="card text-center py-16 text-gray-400">
        <p class="text-5xl mb-4">📋</p>
        <p class="text-lg">Aucune réservation trouvée</p>
        <RouterLink to="/dashboard/client/new-booking" class="btn-primary mt-4 inline-block text-sm">Faire une réservation</RouterLink>
      </div>
    </div>
    <ConfirmModal :show="cancelModal.show" title="Annuler la réservation"
      :message="`Voulez-vous vraiment annuler votre réservation à ${cancelModal.res?.hotel?.nom} ?`"
      danger @confirm="doCancelBooking" @cancel="cancelModal.show = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useBookingStore } from '../../../stores/booking'
import StatusBadge from '../../../components/StatusBadge.vue'
import ConfirmModal from '../../../components/ConfirmModal.vue'

const bookingStore = useBookingStore()
const { locale } = useI18n()
const filterStatus = ref('ALL')
const cancelModal = ref({ show: false, res: null })

const statuts = [
  { val: 'ALL', label: 'Toutes' },
  { val: 'EN_ATTENTE', label: 'En attente' },
  { val: 'CONFIRMEE', label: 'Confirmées' },
  { val: 'EN_COURS', label: 'En cours' },
  { val: 'TERMINEE', label: 'Terminées' },
  { val: 'ANNULEE', label: 'Annulées' },
]

const filtered = computed(() => {
  if (filterStatus.value === 'ALL') return bookingStore.reservations
  return bookingStore.reservations.filter(r => r.statut === filterStatus.value)
})

function formatDate(d) {
  if (!d) return '—'
  const code = locale.value === 'ar' ? 'ar-MA' : locale.value === 'en' ? 'en-US' : 'fr-FR'
  return new Date(d).toLocaleDateString(code, { day: 'numeric', month: 'short', year: 'numeric' })
}

function nightCount(res) {
  const d1 = new Date(res.dateArrivee), d2 = new Date(res.dateDepart)
  return Math.max(1, Math.ceil((d2 - d1) / 86400000))
}

function confirmCancel(res) { cancelModal.value = { show: true, res } }
async function doCancelBooking() {
  await bookingStore.cancelReservation(cancelModal.value.res._id)
  cancelModal.value.show = false
}
function showInvoice(res) { alert(`Facture pour réservation ${res.reference} - Total: ${res.prixTotal}€`) }

onMounted(() => bookingStore.fetchMyReservations())
</script>
