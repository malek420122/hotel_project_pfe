<template>
  <div class="card flex gap-4 hover:shadow-lg transition-shadow">
    <div class="w-32 h-28 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
      <img :src="room.photos?.[0] || '/placeholder-room.jpg'" :alt="room.nom" class="w-full h-full object-cover" />
    </div>
    <div class="flex-1">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="font-bold text-gray-800 text-lg">{{ room.nom }}</h3>
          <p class="text-gray-500 text-sm">{{ room.type }} · Étage {{ room.etage }} · {{ room.maxVoyageurs }} pers. max</p>
        </div>
        <StatusBadge :status="room.statut" />
      </div>
      <p class="text-gray-600 text-sm mt-2 line-clamp-2">{{ room.description }}</p>
      <div v-if="room.equipements?.length" class="flex flex-wrap gap-1 mt-2">
        <span v-for="eq in room.equipements.slice(0,4)" :key="eq"
          class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ eq }}</span>
      </div>
      <div class="flex items-center justify-between mt-3">
        <div>
          <span class="text-2xl font-bold text-secondary">{{ room.prix_base }}</span>
          <span class="text-gray-400 text-xs">€/nuit</span>
        </div>
        <button v-if="room.statut !== 'OCCUPEE'" @click="$emit('reserve', room)" class="btn-primary text-sm py-2 px-4">
          Réserver cette chambre
        </button>
        <span v-else class="text-red-500 font-semibold text-sm">Indisponible</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue'
defineProps({ room: { type: Object, required: true } })
defineEmits(['reserve'])
</script>
