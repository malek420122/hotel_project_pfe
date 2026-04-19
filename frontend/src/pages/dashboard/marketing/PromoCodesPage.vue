<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">{{ $t('dashboard.promo_codes') }}</h2>
      <button @click="showModal=true" class="btn-primary">{{ $t('dashboard.create_code') }}</button>
    </div>
    <DataTable :columns="cols" :data="codes">
      <template #statut="{ row }"><StatusBadge :status="row.statut" /></template>
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button @click="copyCode(row.code)" class="text-xs px-3 py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100">{{ $t('dashboard.copy') }}</button>
          <span v-if="copiedCode === row.code" class="text-xs px-2 py-1 rounded-lg bg-green-50 text-green-700">{{ $t('dashboard.copied') }}</span>
          <button @click="toggleCode(row)" :class="['text-xs px-3 py-1.5 rounded-lg', row.statut === 'ACTIVE' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600']">
            {{ row.statut === 'ACTIVE' ? $t('dashboard.deactivate') : $t('dashboard.activate') }}
          </button>
        </div>
      </template>
    </DataTable>
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
          <h3 class="text-xl font-bold mb-4">{{ $t('dashboard.new_promo_code') }}</h3>
          <form @submit.prevent="createCode" class="space-y-3">
            <div class="flex gap-2">
              <input v-model="form.code" placeholder="CODE2025" class="input-field flex-1 font-mono uppercase" required />
              <button type="button" @click="form.code = genCode()" class="btn-outline px-3">🔀</button>
            </div>
            <select v-model="form.type" class="input-field">
              <option value="POURCENTAGE">{{ $t('dashboard.percentage') }}</option>
              <option value="MONTANT_FIXE">{{ $t('dashboard.fixed_amount') }}</option>
            </select>
            <input v-model.number="form.valeur" type="number" :placeholder="form.type === 'POURCENTAGE' ? $t('dashboard.percent_value_placeholder') : $t('dashboard.fixed_value_placeholder')" class="input-field" required />
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.dateExpiration" type="date" class="input-field" />
              <input v-model.number="form.nbUtilisationsMax" type="number" :placeholder="$t('dashboard.nb_max_uses')" class="input-field" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showModal=false" class="btn-outline">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary">{{ $t('common.create') }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import api from '../../../api'

const { t } = useI18n()

const showModal = ref(false)
const form = reactive({ code: '', type: 'POURCENTAGE', valeur: 10, dateExpiration: '', nbUtilisationsMax: 100 })
const codes = ref([])
const copiedCode = ref('')
const cols = computed(() => [
  { key: 'code', label: t('dashboard.code') },
  { key: 'type', label: t('dashboard.type') },
  { key: 'valeur', label: t('dashboard.value') },
  { key: 'utilise', label: t('dashboard.used') },
  { key: 'max', label: t('dashboard.max') },
  { key: 'expiration', label: t('dashboard.expiration') },
  { key: 'statut', label: t('common.status') },
  { key: 'actions', label: t('common.actions') },
])

function genCode() {
  const uuid = (globalThis.crypto?.randomUUID?.() || `${Date.now()}`).replace(/-/g, '')
  return `PROMO${uuid.slice(0, 6).toUpperCase()}`
}

async function copyCode(c) {
  try {
    await navigator.clipboard.writeText(c)
    copiedCode.value = c
    setTimeout(() => {
      if (copiedCode.value === c) copiedCode.value = ''
    }, 1200)
  } catch {
    copiedCode.value = ''
  }
}

function promotionToCode(promo) {
  return {
    _id: promo._id,
    code: promo.codePromo,
    type: Number(promo.remise_pourcent || 0) >= 0 ? 'POURCENTAGE' : 'MONTANT_FIXE',
    valeur: `${promo.remise_pourcent || 0}%`,
    utilise: promo.nbUtilisations || 0,
    max: promo.limiteUtilisations || 0,
    expiration: promo.dateFin,
    statut: promo.estActive ? 'ACTIVE' : 'INACTIVE',
  }
}

async function loadCodes() {
  try {
    const { data } = await api.get('/marketing/promotions')
    codes.value = (Array.isArray(data) ? data : []).map(promotionToCode)
  } catch {
    codes.value = []
  }
}

async function toggleCode(code) {
  await api.put(`/marketing/promotions/${code._id}`, { estActive: code.statut !== 'ACTIVE' })
  await loadCodes()
}

async function createCode() {
  await api.post('/marketing/promotions', {
    titre: form.code,
    description: form.type === 'POURCENTAGE' ? `${form.valeur}%` : `${form.valeur}€`,
    remise_pourcent: Number(form.valeur || 0),
    codePromo: form.code,
    dateDebut: form.dateExpiration || new Date().toISOString().slice(0, 10),
    dateFin: form.dateExpiration || new Date().toISOString().slice(0, 10),
    nbUtilisations: 0,
    limiteUtilisations: Number(form.nbUtilisationsMax || 0),
    estActive: true,
  })
  showModal.value = false
  await loadCodes()
}

onMounted(loadCodes)
</script>
