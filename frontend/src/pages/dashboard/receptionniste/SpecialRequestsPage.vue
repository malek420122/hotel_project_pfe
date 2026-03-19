<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Demandes Spéciales</h2>
    <div class="space-y-4">
      <div v-for="req in requests" :key="req.id" :class="['card border-l-4', req.urgent ? 'border-red-400' : 'border-secondary']">
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span v-if="req.urgent" class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">🚨 URGENT</span>
              <p class="font-bold text-gray-800">{{ req.client }} — Chambre {{ req.chambre }}</p>
            </div>
            <p class="text-gray-600 text-sm">{{ req.demande }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ req.heure }}</p>
          </div>
          <div class="flex gap-2 ml-4">
            <button @click="markDone(req)" class="text-xs px-3 py-1.5 rounded-lg bg-green-50 text-green-700 hover:bg-green-100">✅ Traité</button>
            <button class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">💬 Répondre</button>
          </div>
        </div>
      </div>
      <div v-if="!requests.length" class="card text-center py-10 text-gray-400">
        <p class="text-3xl mb-2">✅</p>
        <p>Aucune demande en attente</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
const requests = ref([
  { id:1, client:'Sophie Martin', chambre:'202', demande:'Lit bébé et baignoire nécessaires pour ce soir', heure:'13:45', urgent:true },
  { id:2, client:'Ahmed Benali', chambre:'305', demande:'Oreiller supplémentaire et serviettes', heure:'14:10', urgent:false },
  { id:3, client:'Laura Dupont', chambre:'112', demande:'Allergie aux fleurs, retirer toute décoration florale', heure:'14:30', urgent:true },
])
function markDone(req) { requests.value = requests.value.filter(r => r.id !== req.id) }
</script>
