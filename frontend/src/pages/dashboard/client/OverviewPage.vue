<template>
  <div>
    <div class="mb-6">
      <div class="flex items-center gap-2">
        <div>
          <h2 class="text-3xl font-serif font-bold text-[#2D1B08] tracking-tight">
            {{ t('dashboard.hello', { name: formattedName }) }}
          </h2>
          <p class="text-slate-500 font-medium mt-1 tracking-tight uppercase text-xs">{{ t('dashboard.accountOverview') }}</p>
        </div>
        <Sparkles :size="28" class="text-[#FF8C00] opacity-85 flex-shrink-0" />
      </div>
    </div>

    <RouterLink
      to="/dashboard/client/incidents"
      class="mb-6 block rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 via-orange-50 to-amber-50 p-4 shadow-sm transition-all hover:shadow-md"
    >
      <div class="flex items-center justify-between gap-4">
        <div>
          <p class="text-xs font-black uppercase tracking-widest text-red-500">Assistance chambre</p>
          <h3 class="mt-1 text-lg font-bold text-[#2D1B08]">{{ t('nav.report_problem') }}</h3>
          <p class="mt-1 text-sm text-gray-600">{{ t('incidents.client.quickHelp') }}</p>
        </div>
        <AlertTriangle :size="28" class="text-red-500 flex-shrink-0" />
      </div>
    </RouterLink>

    <!-- KPI Cards -->
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <KpiCard :icon="CalendarDays" :label="t('dashboard.activeBookings')" :value="stats.active" color="blue" />
      <KpiCard :icon="CircleCheck" :label="t('dashboard.completedStays')" :value="stats.done" color="green" />
      <KpiCard :icon="Gift" :label="t('dashboard.loyaltyPoints')" :value="loyaltyData.points" color="gold" />
      <KpiCard :icon="Wallet" :label="t('dashboard.totalSpent')" :value="`${stats.spent}€`" color="purple" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Prochain Séjour -->
      <div class="card h-full">
        <h3 class="mb-4 text-lg font-bold text-gray-800 flex items-center gap-2">
          <Hotel :size="20" class="text-gray-400" />
          {{ t('dashboard.nextStay') }}
        </h3>

        <div v-if="nextBooking" class="flex items-center gap-4 py-2">
          <div class="w-16 h-16 rounded-2xl bg-orange-50 flex items-center justify-center text-3xl">🏨</div>
          <div class="flex-grow">
            <p class="font-bold text-gray-900">{{ localize(nextBooking.hotel?.nom) }}</p>
            <p class="text-sm text-gray-500 font-medium">{{ formatDateRange(nextBooking.dateArrivee, nextBooking.dateDepart) }}</p>
            <div class="flex items-center gap-2 mt-2">
              <StatusBadge :status="nextBooking.statut" />
              <span class="text-[10px] font-black uppercase text-gray-400 tracking-tighter">ID: {{ nextBooking.reference }}</span>
            </div>
          </div>
        </div>

        <div v-else class="py-10 text-center text-gray-400">
          <div class="mb-3 flex justify-center opacity-20"><Bed :size="48" /></div>
          <p class="text-sm font-medium">{{ t('dashboard.noUpcomingBooking') }}</p>
          <RouterLink to="/hotels" class="mt-4 inline-flex items-center gap-2 px-6 py-2 bg-[#3E2723] text-white rounded-xl text-sm font-bold hover:bg-[#2A1A17] transition-all">
            {{ t('dashboard.bookNow') }}
          </RouterLink>
        </div>
      </div>

      <!-- Résumé Fidélité -->
      <div class="card h-full overflow-hidden relative group cursor-pointer" @click="router.push('/dashboard/client/loyalty')">
        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
          <Trophy :size="120" />
        </div>
        
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <Gift :size="22" class="text-[#FF8C00]" />
            Statut Fidélité
          </h3>
          <ArrowRight :size="18" class="text-gray-300 group-hover:text-[#FF8C00] transform group-hover:translate-x-1 transition-all" />
        </div>

        <div class="flex items-center gap-5 mb-6">
          <div class="relative">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shadow-lg"
                 :style="{ backgroundColor: loyaltyData.niveau.color + '20', color: loyaltyData.niveau.color, border: `2px solid ${loyaltyData.niveau.color}20` }">
              <span v-if="loyaltyData.niveau.nom === 'Platine'"><Crown :size="28" /></span>
              <span v-else-if="loyaltyData.niveau.nom === 'Or'"><Trophy :size="28" /></span>
              <span v-else-if="loyaltyData.niveau.nom === 'Argent'"><Medal :size="28" /></span>
              <span v-else><Award :size="28" /></span>
            </div>
          </div>

          <div class="flex-grow">
            <div class="flex justify-between items-end mb-1">
              <div>
                <p class="text-xl font-black text-gray-900 leading-none">{{ loyaltyData.points }} <span class="text-xs font-bold text-gray-400 uppercase">pts</span></p>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1">{{ loyaltyData.niveau.nom }}</p>
              </div>
              <div v-if="loyaltyData.prochain" class="text-right">
                <p class="text-[9px] font-black text-gray-400 uppercase mb-1">Objectif {{ loyaltyData.prochain.nom }}</p>
                <p class="text-xs font-black text-[#FF8C00]">{{ Math.round(loyaltyProgress) }}%</p>
              </div>
            </div>

            <div class="h-2 bg-gray-100 rounded-full overflow-hidden mt-1 p-0.5">
              <div 
                class="h-full rounded-full transition-all duration-1000 ease-out"
                :style="{ 
                  width: `${loyaltyProgress}%`, 
                  background: `linear-gradient(90deg, ${loyaltyData.niveau.color}, #FF8C00)` 
                }"
              ></div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-auto">
          <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-[#FF8C00] shadow-sm"><Ticket :size="14" /></div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase leading-none">Récompenses</p>
              <p class="text-xs font-bold text-gray-800">3 dispos</p>
            </div>
          </div>
          <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-emerald-500 shadow-sm"><History :size="14" /></div>
            <div>
              <p class="text-[10px] font-black text-gray-400 uppercase leading-none">Dernier gain</p>
              <p class="text-xs font-bold text-gray-800" v-if="loyaltyData.historique.length">+{{ loyaltyData.historique[0].points }}</p>
              <p class="text-xs font-bold text-gray-800" v-else>—</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recommandations -->
    <div class="mb-8">
      <div class="flex items-center gap-2 mb-4">
        <Sparkles :size="24" class="text-[#FF8C00]" />
        <h3 class="text-xl font-bold text-[#2D1B08]">Recommandé pour vous</h3>
      </div>
      
      <div v-if="loadingRecommendations" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="i in 3" :key="i" class="animate-pulse bg-white rounded-2xl p-4 shadow-sm border border-gray-50">
          <div class="w-full h-40 bg-gray-100 rounded-xl mb-4"></div>
          <div class="h-4 bg-gray-100 rounded w-3/4 mb-2"></div>
          <div class="h-4 bg-gray-100 rounded w-1/2"></div>
        </div>
      </div>

      <div v-else-if="recommendations.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <RouterLink 
          v-for="item in recommendations.slice(0, 3)" 
          :key="item.hotel?._id || item.hotel?.id" 
          :to="`/hotels/${item.hotel?._id || item.hotel?.id}`"
          class="group bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:scale-105 border border-gray-100 flex flex-col cursor-pointer"
        >
          <div class="relative h-48 overflow-hidden">
            <img :src="item.hotel?.photos?.[0] || 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800'" :alt="localize(item.hotel?.nom)" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
            <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-2 py-1 rounded-lg flex items-center gap-1 shadow-sm">
              <Star :size="14" class="text-[#FF8C00] fill-[#FF8C00]" />
              <span class="text-xs font-bold text-[#2D1B08]">{{ item.hotel?.noteMoyenne || 5.0 }}</span>
            </div>
          </div>
          <div class="p-5 flex flex-col flex-grow">
            <h4 class="text-lg font-bold text-[#2D1B08] mb-1 group-hover:text-[#FF8C00] transition-colors truncate">{{ localize(item.hotel?.nom) }}</h4>
            <div class="flex items-center text-gray-500 text-sm mb-4 truncate">
              <MapPin :size="14" class="mr-1 flex-shrink-0" />
              <span class="truncate">{{ localize(item.hotel?.ville) }}</span>
            </div>
            <div class="mt-auto flex items-end justify-between">
              <span class="text-xs text-gray-400 font-medium">À partir de</span>
              <span class="text-lg font-black text-[#2D1B08]">{{ item.hotel?.prix_min || 0 }}€<span class="text-sm font-normal text-gray-500">/nuit</span></span>
            </div>
          </div>
        </RouterLink>
      </div>

      <div v-else class="text-center py-10 bg-white rounded-3xl border border-gray-100 shadow-sm">
        <p class="text-gray-500 font-medium">Aucune recommandation disponible pour le moment.</p>
      </div>
    </div>

    <!-- Dernières Réservations -->
    <div class="card border-none shadow-sm overflow-hidden">
      <div class="mb-6 flex items-center justify-between px-2">
        <h3 class="text-xl font-bold text-[#2D1B08] flex items-center gap-2">
          <ClipboardList :size="24" class="text-gray-400" />
          {{ t('dashboard.recent_reservations') }}
        </h3>
        <RouterLink to="/dashboard/client/reservations" class="text-xs font-black uppercase text-[#FF8C00] tracking-widest hover:underline">{{ t('common.viewAll') }}</RouterLink>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('dashboard.hotel') }}</th>
              <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('booking.room') }}</th>
              <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('dashboard.dates') }}</th>
              <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('dashboard.price') }}</th>
              <th class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ t('dashboard.status') }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <tr v-for="res in recentReservations" :key="res._id" class="hover:bg-gray-50/80 transition-colors">
              <td class="px-4 py-4 font-bold text-gray-800">{{ localize(res.hotel?.nom) }}</td>
              <td class="px-4 py-4 text-gray-600 font-medium">{{ getRoomTypeName(res.chambre?.type) }}</td>
              <td class="px-4 py-4 text-gray-500 text-xs">{{ formatDateRange(res.dateArrivee, res.dateDepart) }}</td>
              <td class="px-4 py-4 font-black text-gray-900">{{ Number(res.prixTotal || 0) }}€</td>
              <td class="px-4 py-4"><StatusBadge :status="res.statut" /></td>
            </tr>

            <tr v-if="!recentReservations.length">
              <td colspan="5" class="px-4 py-12 text-center">
                <div class="mb-2 opacity-10 flex justify-center"><ClipboardList :size="48" /></div>
                <p class="text-gray-400 font-medium">{{ t('dashboard.noReservations') }}</p>
              </td>
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
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import api from '../../../api'
import KpiCard from '../../../components/KpiCard.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import { formatDate as formatLocalizedDate } from '../../../utils/formatDate'
import { 
  CalendarDays, CircleCheck, Gift, Wallet, Sparkles, MapPin, Star,
  Trophy, Medal, Award, Crown, Plus, ArrowRight, Hotel, Bed, ClipboardList, Ticket, History
  , AlertTriangle
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const { locale, t } = useI18n()
const loyaltyData = ref({ 
  points: 0, 
  niveau: { nom: 'Bronze', color: '#CD7F32' }, 
  prochain: null, 
  historique: [] 
})
const statsApi = ref(null)

const recommendations = ref([])
const loadingRecommendations = ref(true)

const formattedName = computed(() => {
  const name = String(auth.user?.prenom || '').trim()
  return name.charAt(0).toUpperCase() + name.slice(1)
})

const stats = computed(() => {
  return {
    active: Number(statsApi.value?.reservations_actives || 0),
    done: Number(statsApi.value?.sejours_completes || 0),
    spent: Number(statsApi.value?.total_depenses || 0),
  }
})

const nextBooking = computed(() => {
  return statsApi.value?.prochain_sejour || null
})

const recentReservations = computed(() => Array.isArray(statsApi.value?.recent_reservations) ? statsApi.value.recent_reservations.slice(0, 5) : [])

const loyaltyProgress = computed(() => {
  if (!loyaltyData.value.prochain) return 100
  const cur = loyaltyData.value.points
  const min = loyaltyData.value.niveau.min
  const max = loyaltyData.value.prochain.min
  const progress = ((cur - min) / (max - min)) * 100
  return Math.min(100, Math.max(0, progress))
})

function formatDisplayDate(dateValue) {
  return formatLocalizedDate(dateValue, locale.value, { day: 'numeric', month: 'long', year: 'numeric' }) || '—'
}

function formatDateRange(start, end) {
  const startText = formatDisplayDate(start)
  const endText = formatDisplayDate(end)
  return locale.value === 'ar' ? `${endText} ← ${startText}` : `${startText} → ${endText}`
}

function localize(field) {
  if (!field) return ''
  if (typeof field === 'string') return field
  if (typeof field === 'object') {
    return field[locale.value] || field['fr'] || field['en'] || Object.values(field)[0]
  }
  return field
}

function getRoomTypeName(type) {
  const key = String(type || '').toLowerCase().trim()
  if (!key) return '—'

  const map = {
    simple: t('booking.roomSingle'),
    standard: t('room.standard'),
    double: t('booking.roomDouble'),
    suite: t('booking.roomSuite'),
    deluxe: t('room.deluxe'),
  }

  return map[key] || String(type || '—')
}

onMounted(async () => {
  try {
    const { data } = await api.get('/client/stats')
    statsApi.value = data || null
    if (data?.loyalty) {
      loyaltyData.value = data.loyalty
    }
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
    statsApi.value = null
    loyaltyData.value = { points: 0, niveau: { nom: 'Bronze', color: '#CD7F32' }, prochain: null, historique: [] }
  }

  try {
    loadingRecommendations.value = true
    const { data } = await api.get('/recommendations')
    recommendations.value = data?.data || []
  } catch (error) {
    console.error('Erreur de chargement des recommandations:', error)
    recommendations.value = []
  } finally {
    loadingRecommendations.value = false
  }
})
</script>

<style scoped>
.card {
  background: white;
  border-radius: 24px;
  padding: 1.5rem;
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
}

.card:hover {
  border-color: #e2e8f0;
}
</style>
