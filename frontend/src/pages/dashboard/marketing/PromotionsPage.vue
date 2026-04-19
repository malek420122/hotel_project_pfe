<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ $t('dashboard.promotions') }}</h2>
      <button @click="showModal=true" class="btn-primary">{{ $t('dashboard.plus_new_promotion') }}</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="promo in promotions" :key="promo.id" :class="['card border-2', promo.statut === 'ACTIVE' ? 'border-green-300' : 'border-gray-200']">
        <div class="flex justify-between items-start mb-3">
          <span class="text-3xl">{{ promo.icon }}</span>
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
            <div :class="['h-full bg-green-500 rounded-full', toPctClass((promo.utilisations / promo.maxUtil) * 100)]"></div>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="btn-outline text-xs py-1.5 px-3 flex-1" @click="editPromo(promo)">✏️ Modifier</button>
          <button @click="togglePromo(promo)" :class="['text-xs py-1.5 px-3 rounded-xl font-semibold flex-1', promo.statut === 'ACTIVE' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600']">
            {{ promo.statut === 'ACTIVE' ? '⏸ Pause' : '▶ Activer' }}
          </button>
        </div>
      </div>
    </div>
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg">
          <h3 class="text-xl font-bold mb-4">{{ form._id ? 'Modifier la promotion' : $t('dashboard.new_promotion') }}</h3>
          <form @submit.prevent="savePromo" class="space-y-3">
            <input v-model="form.titre" :placeholder="$t('dashboard.promo_title')" class="input-field" required />
            <textarea v-model="form.description" :placeholder="$t('dashboard.description')" rows="2" class="input-field"></textarea>
            <div class="grid grid-cols-2 gap-3">
              <input v-model.number="form.remise_pourcent" type="number" :placeholder="$t('dashboard.discount')" class="input-field" min="1" max="100" />
              <input v-model="form.codePromo" placeholder="PROMO2025" class="input-field" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.dateDebut" type="date" class="input-field" />
              <input v-model="form.dateFin" type="date" class="input-field" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input v-model.number="form.nbUtilisations" type="number" :placeholder="$t('dashboard.current_uses')" class="input-field" min="0" />
              <input v-model.number="form.limiteUtilisations" type="number" :placeholder="$t('dashboard.max_uses')" class="input-field" min="1" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showModal=false" class="btn-outline">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary">{{ form._id ? $t('common.save') : $t('common.create') }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'

const showModal = ref(false)
const promotions = ref([])
const form = reactive({ _id: null, titre: '', description: '', remise_pourcent: 10, codePromo: '', dateDebut: '', dateFin: '', nbUtilisations: 0, limiteUtilisations: 100, estActive: true })

function promoToView(promo) {
  return {
    id: String(promo._id || promo.codePromo),
    _id: promo._id,
    icon: promo.estActive ? '🎯' : '⏸️',
    titre: promo.titre,
    description: promo.description,
    remise: Number(promo.remise_pourcent || 0),
    debut: promo.dateDebut,
    fin: promo.dateFin,
    statut: promo.estActive ? 'ACTIVE' : 'INACTIVE',
    utilisations: Number(promo.nbUtilisations || 0),
    maxUtil: Number(promo.limiteUtilisations || 100),
    codePromo: promo.codePromo,
    nbUtilisations: Number(promo.nbUtilisations || 0),
    limiteUtilisations: Number(promo.limiteUtilisations || 100),
  }
}

async function loadPromotions() {
  try {
    const { data } = await api.get('/marketing/promotions')
    promotions.value = (Array.isArray(data) ? data : []).map(promoToView)
  } catch {
    promotions.value = []
  }
}

function editPromo(promo) {
  Object.assign(form, {
    _id: promo._id,
    titre: promo.titre,
    description: promo.description,
    remise_pourcent: promo.remise,
    codePromo: promo.codePromo,
    dateDebut: promo.debut,
    dateFin: promo.fin,
    nbUtilisations: promo.nbUtilisations,
    limiteUtilisations: promo.limiteUtilisations,
    estActive: promo.statut === 'ACTIVE',
  })
  showModal.value = true
}

async function savePromo() {
  const payload = { ...form, estActive: form.estActive !== false }
  if (form._id) {
    await api.put(`/marketing/promotions/${form._id}`, payload)
  } else {
    await api.post('/marketing/promotions', payload)
  }
  showModal.value = false
  await loadPromotions()
}

async function togglePromo(promo) {
  await api.put(`/marketing/promotions/${promo._id}`, { estActive: promo.statut !== 'ACTIVE' })
  await loadPromotions()
}

function toPctClass(value) {
  const normalized = Math.max(0, Math.min(100, Math.round((Number(value || 0) / 5)) * 5))
  return `w-pct-${normalized}`
}

onMounted(loadPromotions)
</script>
