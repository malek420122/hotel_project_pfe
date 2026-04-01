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
          <button @click="toggleCode(row)" :class="['text-xs px-3 py-1.5 rounded-lg', row.statut==='ACTIVE' ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600']">
            {{ row.statut==='ACTIVE' ? 'Désactiver' : 'Activer' }}
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
            <input v-model.number="form.valeur" type="number" :placeholder="form.type==='POURCENTAGE' ? 'Valeur en %' : 'Valeur en €'" class="input-field" required />
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
import { ref, reactive } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
const showModal = ref(false)
const form = reactive({ code: '', type: 'POURCENTAGE', valeur: 10, dateExpiration: '', nbUtilisationsMax: 100 })
const codes = ref([
  { code:'SUMMER25', type:'POURCENTAGE', valeur:'25%', utilise:45, max:200, expiration:'31/08/25', statut:'ACTIVE' },
  { code:'WELCOME10', type:'POURCENTAGE', valeur:'10%', utilise:89, max:500, expiration:'31/12/25', statut:'ACTIVE' },
  { code:'FLAT50', type:'MONTANT_FIXE', valeur:'50€', utilise:12, max:50, expiration:'30/06/25', statut:'EXPIREE' },
])
const cols = [
  { key:'code', label:'Code' }, { key:'type', label:'Type' }, { key:'valeur', label:'Valeur' },
  { key:'utilise', label:'Utilisé' }, { key:'max', label:'Max' }, { key:'expiration', label:'Expiration' },
  { key:'statut', label:'Statut' }, { key:'actions', label:'Actions' }
]
function genCode() { return 'PROMO' + Math.random().toString(36).slice(2,6).toUpperCase() }
function copyCode(c) { navigator.clipboard.writeText(c); alert('Code copié !') }
function toggleCode(c) { c.statut = c.statut === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }
function createCode() { codes.value.push({ ...form, utilise:0, max:form.nbUtilisationsMax, expiration: form.dateExpiration, valeur: form.type==='POURCENTAGE' ? `${form.valeur}%` : `${form.valeur}€`, statut:'ACTIVE' }); showModal.value=false }
</script>
