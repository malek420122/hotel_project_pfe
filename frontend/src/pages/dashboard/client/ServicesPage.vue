<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Mes Services</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="svc in services" :key="svc.id" class="card flex items-center gap-4 hover:shadow-lg transition-shadow">
        <div class="text-4xl">{{ svc.icon }}</div>
        <div class="flex-1">
          <p class="font-bold text-gray-800">{{ svc.nom }}</p>
          <p class="text-sm text-gray-500">{{ svc.hotel }}</p>
          <StatusBadge :status="svc.statut" class="mt-1" />
        </div>
        <p class="font-bold text-secondary">{{ svc.prix }}€</p>
      </div>
    </div>
    <div v-if="!services.length" class="card text-center py-10 text-gray-400 mt-6">
      <p>Aucun service disponible pour le moment.</p>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'

const services = ref([])
const iconByCategory = {
  SPA: '🧖',
  RESTAURANT: '🍳',
  ACTIVITE: '🚗',
  CONCIERGERIE: '🛎️',
}

async function loadServices() {
  try {
    const [servicesRes, hotelsRes] = await Promise.all([
      api.get('/services'),
      api.get('/hotels', { params: { per_page: 200 } }),
    ])

    const rows = Array.isArray(servicesRes.data) ? servicesRes.data : []
    const hotels = Array.isArray(hotelsRes.data?.data) ? hotelsRes.data.data : []
    const hotelMap = new Map(hotels.map((hotel) => [String(hotel._id), hotel.nom]))

    services.value = rows.map((service) => ({
      id: String(service._id || service.nom),
      nom: service.nom,
      hotel: hotelMap.get(String(service.hotelId || '')) || 'Hotel',
      icon: iconByCategory[service.categorie] || '✨',
      prix: Number(service.prix || 0),
      statut: service.estActif ? 'CONFIRMEE' : 'ANNULEE',
    }))
  } catch {
    services.value = []
  }
}

onMounted(loadServices)
</script>
