<template>
  <div ref="mapContainer" class="w-full h-64 rounded-xl overflow-hidden border"></div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import L from 'leaflet'

const props = defineProps({
  hotel: { type: Object, required: true }
})

const mapContainer = ref(null)

onMounted(() => {
  if (!props.hotel?.latitude || !props.hotel?.longitude) return
  const map = L.map(mapContainer.value).setView([props.hotel.latitude, props.hotel.longitude], 14)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
  const icon = L.divIcon({
    html: `<div style="background:#8B4513;color:white;padding:6px 10px;border-radius:8px;font-weight:bold;font-size:12px;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,0.12)">🏨 ${props.hotel.nom}</div>`,
    className: '',
    iconAnchor: [60, 20],
  })
  L.marker([props.hotel.latitude, props.hotel.longitude], { icon }).addTo(map)
})
</script>
