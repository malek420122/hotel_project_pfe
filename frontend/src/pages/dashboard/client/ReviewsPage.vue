<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Mes Avis</h2>
    <div class="space-y-4">
      <div v-for="avis in reviews" :key="avis.id" class="card">
        <div class="flex justify-between items-start">
          <div>
            <p class="font-bold text-gray-800">{{ avis.hotel }}</p>
            <StarRating :modelValue="avis.note" class="mt-1" />
            <p class="text-gray-600 text-sm mt-2 italic">"{{ avis.commentaire }}"</p>
            <p class="text-xs text-gray-400 mt-1">{{ avis.date }}</p>
          </div>
          <StatusBadge :status="avis.statut" />
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import StarRating from '../../../components/StarRating.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
const reviews = [
  { id: 1, hotel: 'Hôtel Atlas Marrakech', note: 5, commentaire: 'Séjour exceptionnel, personnel aux petits soins !', date: '15 Mar 2025', statut: 'PUBLIE' },
  { id: 2, hotel: 'Riad Al Baraka', note: 4, commentaire: 'Très beau riad, cuisine délicieuse.', date: '25 Feb 2025', statut: 'EN_ATTENTE' },
]
</script>
