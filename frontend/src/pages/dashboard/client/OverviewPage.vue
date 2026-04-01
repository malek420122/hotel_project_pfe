<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Bonjour, {{ auth.user?.prenom }} 👋</h2>
      <p class="text-gray-500">Voici un aperçu de votre compte</p>
    </div>
    <!-- KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <KpiCard icon="📅" label="Réservations actives" :value="stats.active" color="blue" />
      <KpiCard icon="✅" label="Séjours complétés" :value="stats.done" color="green" />
      <KpiCard icon="🎁" label="Points fidélité" :value="loyalty.points" color="gold" />
      <KpiCard icon="💰" label="Total dépenses" :value="`${stats.spent}€`" color="purple" />
    </div>
    <!-- Next booking -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Prochain séjour</h3>
        <div v-if="nextBooking" class="flex items-center gap-4">
          <div class="text-5xl">🏨</div>
          <div>
            <p class="font-semibold text-gray-800">{{ nextBooking.hotel?.nom }}</p>
            <p class="text-gray-500 text-sm">{{ formatDate(nextBooking.dateArrivee) }} → {{ formatDate(nextBooking.dateDepart) }}</p>
            <StatusBadge :status="nextBooking.statut" class="mt-2" />
          </div>
        </div>
        <div v-else class="text-center py-6 text-gray-400">
          <p class="text-3xl mb-2">🛏️</p>
          <p class="text-sm">Aucune réservation à venir</p>
          <RouterLink to="/dashboard/client/new-booking" class="btn-primary mt-3 inline-block text-sm">Réserver maintenant</RouterLink>
        </div>
      </div>
      <div class="card">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Programme fidélité</h3>
        <div class="flex items-center gap-3 mb-3">
          <span class="text-3xl">{{ loyaltyLevel.icon }}</span>
          <div>
            <p class="font-bold text-lg text-gray-800">Niveau {{ loyalty.niveau || 'Bronze' }}</p>
            <p class="text-sm text-gray-500">{{ loyalty.points || 0 }} points</p>
          </div>
        </div>
        <div class="mb-2">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ loyalty.points || 0 }} pts</span>
            <span>{{ loyaltyLevel.next }} pts pour {{ loyaltyLevel.nextLevel }}</span>
          </div>
          <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-accent to-yellow-400 rounded-full transition-all duration-1000"
              :style="`width:${loyaltyPercent}%`"></div>
          </div>
        </div>
      </div>
    </div>
    <!-- Recent Reservations -->
    <div class="card">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-800">{{ $t('dashboard.recent_reservations') }}</h3>
        <RouterLink to="/dashboard/client/reservations" class="text-secondary text-sm hover:underline">Voir toutes</RouterLink>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ $t('dashboard.hotel') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">Chambre</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ $t('dashboard.dates') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ $t('dashboard.price') }}</th>
              <th class="px-3 py-2 text-left font-semibold text-gray-600">{{ $t('dashboard.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="res in recentReservations" :key="res._id" class="hover:bg-gray-50">
              <td class="px-3 py-3 font-medium">{{ res.hotel?.nom }}</td>
              <td class="px-3 py-3 text-gray-600">{{ res.chambre?.nom }}</td>
              <td class="px-3 py-3 text-gray-500">{{ formatDate(res.dateArrivee) }}</td>
              <td class="px-3 py-3 font-semibold text-secondary">{{ res.prixTotal }}€</td>
              <td class="px-3 py-3"><StatusBadge :status="res.statut" /></td>
            </tr>
            <tr v-if="!recentReservations.length">
              <td colspan="5" class="px-3 py-8 text-center text-gray-400">Aucune réservation</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../../stores/auth'
import { useBookingStore } from '../../../stores/booking'
import KpiCard from '../../../components/KpiCard.vue'
import StatusBadge from '../../../components/StatusBadge.vue'

const auth = useAuthStore()
const bookingStore = useBookingStore()
const { locale } = useI18n()
const loyalty = ref({ points: 0, niveau: 'Bronze' })

const stats = computed(() => ({
  active: bookingStore.reservations.filter(r => ['EN_ATTENTE','CONFIRMEE','EN_COURS'].includes(r.statut)).length,
  done: bookingStore.reservations.filter(r => r.statut === 'TERMINEE').length,
  spent: bookingStore.reservations.filter(r => r.statut === 'TERMINEE').reduce((sum, r) => sum + (r.prixTotal || 0), 0),
}))

const nextBooking = computed(() => bookingStore.reservations.find(r => ['EN_ATTENTE','CONFIRMEE'].includes(r.statut)))
const recentReservations = computed(() => bookingStore.reservations.slice(0, 5))

const loyaltyLevel = computed(() => {
  const pts = loyalty.value.points
  if (pts >= 5000) return { icon: '🥇', nextLevel: '', next: 5000 }
  if (pts >= 2000) return { icon: '🥈', nextLevel: 'Or', next: 5000 }
  return { icon: '🥉', nextLevel: 'Argent', next: 2000 }
})
const loyaltyPercent = computed(() => Math.min(100, ((loyalty.value.points || 0) / loyaltyLevel.value.next) * 100))

function formatDate(d) {
  if (!d) return '—'
  const code = locale.value === 'ar' ? 'ar-MA' : locale.value === 'en' ? 'en-US' : 'fr-FR'
  return new Date(d).toLocaleDateString(code, { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  await bookingStore.fetchMyReservations()
})
</script>
