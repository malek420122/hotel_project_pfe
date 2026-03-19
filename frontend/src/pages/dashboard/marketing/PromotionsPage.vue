<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ $t('dashboard.promotions') }}</h2>
      <button @click="showModal=true" class="btn-primary">{{ $t('dashboard.plus_new_promotion') }}</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="promo in promotions" :key="promo.id" :class="['card border-2', promo.statut==='ACTIVE' ? 'border-green-300' : 'border-gray-200']">
        <div class="flex justify-between items-start mb-3">
          <span :class="['text-3xl']">{{ promo.icon }}</span>
          <StatusBadge :status="promo.statut" />
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-1">{{ promo.titre }}</h3>
        <p class="text-gray-500 text-sm mb-3">{{ promo.description }}</p>
        <div class="flex justify-between text-sm mb-3">
          <span class="text-green-600 font-bold">-{{ promo.remise }}%</span>
          <span class="text-gray-400">{{ promo.debut }} → {{ promo.fin }}</span>
        </div>
        <div class="mb-3">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ promo.utilisations }} utilisations</span>
            <span>Max: {{ promo.maxUtil }}</span>
          </div>
          <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div :style="`width:${(promo.utilisations/promo.maxUtil)*100}%`" class="h-full bg-green-500 rounded-full"></div>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="btn-outline text-xs py-1.5 px-3 flex-1">✏️ Modifier</button>
          <button @click="togglePromo(promo)" :class="['text-xs py-1.5 px-3 rounded-xl font-semibold flex-1', promo.statut==='ACTIVE' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600']">
            {{ promo.statut==='ACTIVE' ? '⏸ Pause' : '▶ Activer' }}
          </button>
        </div>
      </div>
    </div>
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
          <h3 class="text-xl font-bold mb-4">{{ $t('dashboard.new_promotion') }}</h3>
          <form @submit.prevent="createPromo" class="space-y-3">
            <input v-model="form.titre" :placeholder="$t('dashboard.promo_title')" class="input-field" required />
            <textarea v-model="form.description" :placeholder="$t('dashboard.description')" rows="2" class="input-field"></textarea>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.remise" type="number" :placeholder="$t('dashboard.discount')" class="input-field" min="1" max="100" />
              <input v-model="form.maxUtil" type="number" :placeholder="$t('dashboard.max_uses')" class="input-field" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.debut" type="date" class="input-field" />
              <input v-model="form.fin" type="date" class="input-field" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showModal=false" class="btn-outline">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary">{{ $t('common.create') }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Broadcast Notification -->
    <div class="mt-12 lg:mt-16 bg-blue-50/50 rounded-2xl p-6 border border-blue-100 shadow-sm relative overflow-hidden">
      <div class="absolute -top-6 -right-6 w-32 h-32 bg-blue-100/50 rounded-full blur-2xl"></div>
      <div class="relative">
        <h3 class="text-xl font-extrabold text-blue-900 mb-2 flex items-center gap-2">
          <span>📢</span> {{ t('dashboard.marketing.broadcast.title', 'Diffusion de Notification') }}
        </h3>
        <p class="text-blue-700/70 text-sm mb-6 max-w-xl">
          {{ t('dashboard.marketing.broadcast.subtitle', 'Envoyez un message promotionnel à tous vos clients en un clic pour booster vos réservations.') }}
        </p>

        <form @submit.prevent="sendBroadcast" class="space-y-4 max-w-2xl">
          <textarea
            v-model="broadcastMessage"
            class="input-field bg-white border-blue-200 focus:ring-blue-500 focus:border-blue-500 min-h-[100px]"
            :placeholder="t('dashboard.marketing.broadcast.placeholder', 'Ex: Profitez de -20% sur tous les hôtels à Marrakech ce weekend ! #OffreEte')"
            required
          ></textarea>
          <div class="flex items-center justify-between">
            <p class="text-[10px] text-blue-400 font-medium tracking-wider uppercase">
              {{ broadcastMessage.length }} {{ t('common.characters', 'caractères') }}
            </p>
            <button
              type="submit"
              :disabled="broadcastMessage.length < 10 || sendingBroadcast"
              class="btn-primary bg-blue-600 hover:bg-blue-700 disabled:opacity-50 min-w-[160px] flex items-center justify-center gap-2"
            >
              <span v-if="sendingBroadcast" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
              <span v-else>🚀</span>
              {{ sendingBroadcast ? t('common.sending', 'Envoi...') : t('dashboard.marketing.broadcast.btn', 'Diffuser à tous') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'

const { t } = useI18n()
const showModal = ref(false)
const sendingBroadcast = ref(false)
const broadcastMessage = ref('')
const form = reactive({ titre: '', description: '', remise: 10, maxUtil: 100, debut: '', fin: '' })

const promotions = ref([
  { id:1, icon:'☀️', titre: t('promotions.summer.title', 'Offre Été 2025'), description: t('promotions.summer.desc', '10% de remise sur tous les séjours en juillet/août'), remise:10, debut:'01/07', fin:'31/08', statut:'ACTIVE', utilisations:89, maxUtil:200 },
  { id:2, icon:'🌙', titre: t('promotions.ramadan.title', 'Ramadan Special'), description: t('promotions.ramadan.desc', '15% de remise pour le mois sacré'), remise:15, debut:'01/03', fin:'31/03', statut:'ACTIVE', utilisations:45, maxUtil:150 },
  { id:3, icon:'❄️', titre: t('promotions.winter.title', 'Promo Hiver'), description: t('promotions.winter.desc', '20% sur les séjours en janvier'), remise:20, debut:'01/01', fin:'31/01', statut:'EXPIREE', utilisations:120, maxUtil:120 },
])

function togglePromo(p) { p.statut = p.statut === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }
function createPromo() { promotions.value.push({ ...form, id: Date.now(), icon:'🎯', statut:'ACTIVE', utilisations:0 }); showModal.value = false }

async function sendBroadcast() {
  if (!broadcastMessage.value || broadcastMessage.value.length < 10) return
  sendingBroadcast.value = true
  try {
    await api.post('/marketing/notifications/broadcast', { message: broadcastMessage.value })
    alert(t('dashboard.marketing.broadcast.success', 'Notification diffusée avec succès à tous les clients !'))
    broadcastMessage.value = ''
  } catch (e) {
    alert(t('common.error', 'Une erreur est survenue'))
  } finally {
    sendingBroadcast.value = false
  }
}
</script>
