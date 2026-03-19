<template>
  <RouterLink :to="`/hotels/${hotel._id}`" class="block group">
    <div class="card overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer p-0">
      <div class="relative h-48 overflow-hidden">
        <img :src="hotel.photos?.[0] || '/placeholder-hotel.jpg'" :alt="hotel.nom"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        <div class="absolute top-3 left-3 flex gap-2">
          <span class="bg-white/90 text-xs font-bold text-gray-800 px-2 py-1 rounded-lg">
            {{ '★'.repeat(hotel.etoiles || 0) }}
          </span>
        </div>
        <div v-if="hotel.noteMoyenne" class="absolute top-3 right-3">
          <span class="bg-secondary text-white text-sm font-bold px-2 py-1 rounded-lg">
            ⭐ {{ hotel.noteMoyenne?.toFixed(1) }}
          </span>
        </div>
        <div class="absolute bottom-3 left-3 text-white">
          <p class="font-bold text-lg leading-tight">{{ hotel.nom }}</p>
          <p class="text-white/80 text-xs">📍 {{ hotel.ville }}</p>
        </div>
      </div>
      <div class="p-4">
        <p class="text-gray-500 text-sm line-clamp-2 mb-3">{{ hotel.description }}</p>
        <div v-if="hotel.equipements?.length" class="flex flex-wrap gap-1 mb-3">
          <span v-for="eq in hotel.equipements.slice(0,3)" :key="eq"
            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ eq }}</span>
          <span v-if="hotel.equipements.length > 3" class="text-xs text-gray-400">+{{ hotel.equipements.length - 3 }}</span>
        </div>
        <div class="flex justify-between items-center">
          <div>
            <span class="text-2xl font-bold text-secondary">{{ hotel.prix_min || '—' }}</span>
            <span v-if="hotel.prix_min" class="text-gray-400 text-xs">€/nuit</span>
          </div>
          <span class="btn-primary text-sm py-1.5 px-4">Voir l'hôtel</span>
        </div>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
defineProps({ hotel: { type: Object, required: true } })
</script>
