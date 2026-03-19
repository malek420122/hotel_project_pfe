<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Mes Services</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="svc in services" :key="svc.type" class="card flex items-center gap-4 hover:shadow-lg transition-shadow">
        <div class="text-4xl">{{ svc.icon }}</div>
        <div class="flex-1">
          <p class="font-bold text-gray-800">{{ svc.nom }}</p>
          <p class="text-sm text-gray-500">{{ svc.date }}</p>
          <StatusBadge :status="svc.statut" class="mt-1" />
        </div>
        <p class="font-bold text-secondary">{{ svc.prix }}€</p>
      </div>
    </div>
  </div>
</template>
<script setup>
import StatusBadge from '../../../components/StatusBadge.vue'
const services = [
  { type: 'spa', nom: 'Accès Spa & Wellness', icon: '🧖', prix: 30, statut: 'CONFIRMEE', date: '12 Mars 2025' },
  { type: 'navette', nom: 'Navette aéroport', icon: '🚗', prix: 25, statut: 'EN_ATTENTE', date: '14 Mars 2025' },
  { type: 'pdj', nom: 'Petit-déjeuner (2 pers.)', icon: '🍳', prix: 30, statut: 'CONFIRMEE', date: '12-14 Mars 2025' },
]
</script>
