<template>
  <div class="loyalty-page">
    <div class="mb-8">
      <h1 class="text-3xl font-serif font-bold text-[#2D1B08] tracking-tight">
        {{ t('dashboard.loyaltyProgram') }}
      </h1>
      <p class="text-slate-500 font-medium mt-1 tracking-tight uppercase text-xs">Gérez vos points et profitez de vos récompenses exclusives.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      <!-- Section Gauche : Statut Actuel -->
      <div class="lg:col-span-5 space-y-6">
        <div class="card overflow-hidden relative border-none shadow-2xl bg-gradient-to-br from-white to-gray-50">
          <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
            <Trophy :size="150" />
          </div>

          <div class="relative z-10 p-8">
            <div class="flex items-center gap-6 mb-8">
              <div class="relative">
                <div class="w-24 h-24 rounded-3xl flex items-center justify-center text-4xl shadow-xl transform transition-transform hover:scale-105 duration-300"
                     :style="{ backgroundColor: loyaltyData.niveau.color + '20', color: loyaltyData.niveau.color, border: `3px solid ${loyaltyData.niveau.color}40` }">
                  <span v-if="loyaltyData.niveau.nom === 'Platine'"><Crown :size="48" /></span>
                  <span v-else-if="loyaltyData.niveau.nom === 'Or'"><Trophy :size="48" /></span>
                  <span v-else-if="loyaltyData.niveau.nom === 'Argent'"><Medal :size="48" /></span>
                  <span v-else><Award :size="48" /></span>
                </div>
                <div class="absolute -bottom-2 -right-2 bg-white border border-gray-100 rounded-xl px-3 py-1 shadow-md text-xs font-black uppercase tracking-widest"
                     :style="{ color: loyaltyData.niveau.color }">
                  {{ loyaltyData.niveau.nom }}
                </div>
              </div>

              <div>
                <p class="text-4xl font-black text-gray-900 leading-tight">{{ loyaltyData.points }}</p>
                <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mt-1">Points cumulés</p>
              </div>
            </div>

            <div v-if="loyaltyData.prochain" class="space-y-4">
              <div class="flex justify-between items-end">
                <p class="text-sm font-bold text-gray-500">
                  Plus que <span class="text-[#FF8C00] font-black">{{ loyaltyData.prochain.min - loyaltyData.points }} pts</span> pour atteindre <span class="text-gray-800">{{ loyaltyData.prochain.nom }}</span>
                </p>
                <p class="text-xs font-black text-gray-400 uppercase">{{ Math.round(loyaltyProgress) }}%</p>
              </div>

              <!-- Progress Bar Premium -->
              <div class="h-4 bg-gray-200/50 rounded-full overflow-hidden border border-gray-100 p-0.5">
                <div 
                  class="h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_20px_rgba(255,140,0,0.4)]"
                  :style="{ 
                    width: `${loyaltyProgress}%`, 
                    background: `linear-gradient(90deg, ${loyaltyData.niveau.color}, #FF8C00)` 
                  }"
                ></div>
              </div>
            </div>

            <div v-else class="flex items-center gap-3 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-emerald-600">
              <Sparkles :size="24" />
              <div class="font-black text-sm uppercase tracking-wider">Niveau Maximum Atteint</div>
            </div>
          </div>
          
          <div class="bg-[#3E2723] p-6 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
              <ShieldCheck :size="20" class="text-[#D4AF37]" />
              <span class="text-sm font-bold">Avantages du niveau {{ loyaltyData.niveau.nom }}</span>
            </div>
            <button @click="isBenefitsModalOpen = true" class="text-xs font-black uppercase tracking-widest hover:text-[#D4AF37] transition-colors">Détails</button>
          </div>
        </div>

        <div class="card">
          <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
            <History :size="20" class="text-gray-400" />
            Historique détaillé
          </h3>
          <div class="space-y-4">
            <div v-for="item in loyaltyData.historique" :key="item.id" class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 hover:bg-white transition-all duration-300 border border-transparent hover:border-gray-100 hover:shadow-sm">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-emerald-500 shadow-sm border border-gray-50">
                  <Plus :size="18" />
                </div>
                <div>
                  <p class="text-sm font-black text-gray-800">{{ item.label }}</p>
                  <p class="text-xs text-gray-400 font-medium">{{ item.date }}</p>
                </div>
              </div>
              <div class="text-base font-black text-emerald-600">+{{ item.points }} pts</div>
            </div>
            
            <div v-if="!loyaltyData.historique.length" class="text-center py-10 text-gray-400">
              <div class="mb-4 flex justify-center opacity-20"><History :size="48" /></div>
              <p class="text-sm font-medium">Aucun historique de points disponible.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Droite : Récompenses -->
      <div class="lg:col-span-7">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <Gift :size="24" class="text-[#FF8C00]" />
            Récompenses disponibles
          </h3>
          <span class="text-xs font-black uppercase text-gray-400 tracking-widest bg-white px-3 py-1 rounded-full border border-gray-100">
            {{ loyaltyData.points }} points restants
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div 
            v-for="reward in availableRewards" 
            :key="reward.id"
            class="group relative overflow-hidden rounded-3xl transition-all duration-500 border-2"
            :class="loyaltyData.points >= reward.cost ? 'bg-white border-[#D4AF37]/20 hover:border-[#D4AF37] hover:shadow-2xl' : 'bg-gray-50 border-gray-100 opacity-70'"
          >
            <!-- Background Decoration -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full opacity-5 transition-transform duration-700 group-hover:scale-150"
                 :style="{ backgroundColor: loyaltyData.points >= reward.cost ? '#D4AF37' : '#94a3b8' }"></div>

            <div class="p-6 relative z-10 flex flex-col h-full">
              <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg mb-4 transform transition-transform group-hover:rotate-12 duration-300"
                   :class="loyaltyData.points >= reward.cost ? 'bg-gradient-to-br from-[#D4AF37] to-[#B8860B]' : 'bg-gray-300'">
                <component :is="reward.icon" :size="28" />
              </div>

              <h4 class="text-lg font-black text-gray-900 mb-2">{{ reward.title }}</h4>
              <p class="text-sm text-gray-500 font-medium mb-6 flex-grow">{{ reward.desc }}</p>

              <div class="mt-auto flex items-center justify-between gap-4">
                <div class="flex flex-col">
                  <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Coût</span>
                  <span class="text-lg font-black" :class="loyaltyData.points >= reward.cost ? 'text-[#D4AF37]' : 'text-gray-400'">
                    {{ reward.cost }} pts
                  </span>
                </div>

                <button 
                  @click="redeemReward(reward.id)"
                  :disabled="loyaltyData.points < reward.cost || redeeming === reward.id"
                  class="flex items-center gap-2 px-6 py-3 rounded-2xl font-black text-sm transition-all duration-300 shadow-lg"
                  :class="loyaltyData.points >= reward.cost 
                    ? 'bg-[#3E2723] text-white hover:bg-[#2A1A17] hover:shadow-[#3E2723]/30' 
                    : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                >
                  <template v-if="redeeming === reward.id">
                    <Loader2 class="animate-spin" :size="18" />
                  </template>
                  <template v-else>
                    Échanger
                  </template>
                </button>
              </div>
            </div>
            
            <!-- Progress indicator inside reward card -->
            <div v-if="loyaltyData.points < reward.cost" class="absolute bottom-0 left-0 right-0 h-1.5 bg-gray-100">
              <div class="h-full bg-gray-300 transition-all duration-1000" :style="{ width: `${(loyaltyData.points / reward.cost) * 100}%` }"></div>
            </div>
          </div>
        </div>
        
        <div class="mt-8 bg-[#3E2723]/5 p-8 rounded-3xl border border-[#3E2723]/10">
          <div class="flex items-start gap-4">
            <div class="p-3 bg-white rounded-2xl shadow-sm text-[#3E2723]">
              <Info :size="24" />
            </div>
            <div>
              <h4 class="font-black text-[#3E2723] mb-2 uppercase tracking-widest text-sm">Comment ça marche ?</h4>
              <p class="text-[#3E2723]/70 text-sm leading-relaxed font-medium">
                Gagnez des points pour chaque euro dépensé lors de vos séjours. 1€ = 0.1 point. Une fois que vous avez assez de points, vous pouvez les échanger contre des codes promos exclusifs valables sur vos prochaines réservations. Les codes sont envoyés par email et sont valables pendant 3 mois.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <RewardModal 
      v-model:isOpen="isRewardModalOpen" 
      :promoCode="rewardPromoCode" 
    />

    <!-- Modal Avantages -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="isBenefitsModalOpen" class="modal-overlay" @click.self="isBenefitsModalOpen = false">
          <div class="modal-content relative p-8 max-w-md">
            <button @click="isBenefitsModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
              <X :size="20" />
            </button>
            <div class="text-center mb-6">
              <div class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-2xl mb-4"
                   :style="{ backgroundColor: loyaltyData.niveau.color + '20', color: loyaltyData.niveau.color }">
                <span v-if="loyaltyData.niveau.nom === 'Platine'"><Crown :size="32" /></span>
                <span v-else-if="loyaltyData.niveau.nom === 'Or'"><Trophy :size="32" /></span>
                <span v-else-if="loyaltyData.niveau.nom === 'Argent'"><Medal :size="32" /></span>
                <span v-else><Award :size="32" /></span>
              </div>
              <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Avantages {{ loyaltyData.niveau.nom }}</h3>
            </div>
            <ul class="space-y-3">
              <li v-for="(benefit, i) in loyaltyData.niveau.avantages" :key="i" class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mt-0.5">
                  <Check :size="12" />
                </div>
                <span class="text-sm font-bold text-gray-700">{{ benefit }}</span>
              </li>
            </ul>
            <button @click="isBenefitsModalOpen = false" class="w-full mt-8 py-3 bg-[#3E2723] text-white rounded-xl font-bold text-sm">
              Fermer
            </button>
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
import { 
  Gift, Sparkles, Trophy, Medal, Award, Crown, Plus, History, 
  ShieldCheck, Percent, Coffee, ArrowUpCircle, Loader2, Info, X, Check
} from 'lucide-vue-next'
import RewardModal from '../../../components/RewardModal.vue'

const { t } = useI18n()
const loyaltyData = ref({ 
  points: 0, 
  niveau: { nom: 'Bronze', color: '#CD7F32', avantages: [] }, 
  prochain: null, 
  historique: [] 
})
const loading = ref(true)

const isBenefitsModalOpen = ref(false)
const isRewardModalOpen = ref(false)
const availableRewards = [
  { id: 'discount10', title: 'Réduction 10%', desc: 'Une remise immédiate sur votre prochain séjour dans n\'importe lequel de nos hôtels.', cost: 500, icon: Percent },
  { id: 'free_breakfast', title: 'Petit-déjeuner offert', desc: 'Profitez d\'un petit-déjeuner complet pour 2 personnes durant tout votre séjour.', cost: 1000, icon: Coffee },
  { id: 'free_upgrade', title: 'Surclassement VIP', desc: 'Obtenez une chambre de catégorie supérieure selon disponibilité à votre arrivée.', cost: 2000, icon: ArrowUpCircle },
]

const rewardPromoCode = ref('')
const redeeming = ref(null)

const loyaltyProgress = computed(() => {
  if (!loyaltyData.value.prochain) return 100
  const cur = loyaltyData.value.points
  const min = loyaltyData.value.niveau.min
  const max = loyaltyData.value.prochain.min
  const progress = ((cur - min) / (max - min)) * 100
  return Math.min(100, Math.max(0, progress))
})

async function fetchStats() {
  try {
    loading.value = true
    const { data } = await api.get('/client/stats')
    if (data?.loyalty) {
      loyaltyData.value = data.loyalty
    }
  } catch (error) {
    console.error('Erreur stats:', error)
  } finally {
    loading.value = false
  }
}

async function redeemReward(rewardId) {
  if (redeeming.value) return
  
  try {
    redeeming.value = rewardId
    const { data } = await api.post('/client/loyalty/redeem', { reward: rewardId })
    
    if (data.codePromo) {
      rewardPromoCode.value = data.codePromo
      isRewardModalOpen.value = true
      
      // Re-fetch to update points and history
      await fetchStats()
    }
  } catch (error) {
    console.error('Erreur lors du rachat:', error)
    alert(error.response?.data?.error || 'Une erreur est survenue')
  } finally {
    redeeming.value = null
  }
}

onMounted(fetchStats)
</script>

<style scoped>
.loyalty-page {
  animation: pageSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes pageSlideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.card {
  background: white;
  border-radius: 32px;
  padding: 2rem;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
}
</style>
