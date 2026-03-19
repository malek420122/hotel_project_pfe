<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Check-Out</h2>
    <div class="card mb-6">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Rechercher un client</h3>
      <div class="flex gap-3">
        <input v-model="search" placeholder="Nom, chambre ou référence..." class="input-field flex-1" />
        <button @click="doSearch" class="btn-primary">Rechercher</button>
      </div>
    </div>
    <div class="card">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Check-outs du jour</h3>
      <div class="space-y-3">
        <div v-for="r in checkouts" :key="r.ref" class="flex items-center gap-4 p-4 rounded-xl border hover:shadow-sm">
          <div class="text-3xl">🚪</div>
          <div class="flex-1">
            <p class="font-bold text-gray-800">{{ r.client }}</p>
            <p class="text-sm text-gray-500">{{ r.hotel }} · Chambre {{ r.chambre }} · Heure: {{ r.heure }}</p>
          </div>
          <div class="flex gap-2 items-center">
            <span class="font-bold text-secondary">{{ r.solde > 0 ? r.solde+'€ dû' : 'Soldé ✅' }}</span>
            <button @click="doCheckOut(r)" class="btn-accent text-xs py-1.5 px-4">Checkout</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
const search = ref('')
const checkouts = ref([
  { ref:'R010', client:'Marie Curie', hotel:'Atlas', chambre:'209', heure:'11:00', solde:0 },
  { ref:'R011', client:'Jean Dupont', hotel:'Atlas', chambre:'301', heure:'12:00', solde:45 },
])
function doSearch() {}
function doCheckOut(r) { alert(`Check-out de ${r.client} effectué !`); checkouts.value = checkouts.value.filter(c => c.ref !== r.ref) }
</script>
