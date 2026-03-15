<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Grille des Chambres</h2>
    <div class="flex flex-wrap gap-3 mb-6">
      <div v-for="s in statuses" :key="s.val" class="flex items-center gap-2">
        <div :class="['w-4 h-4 rounded', s.color]"></div>
        <span class="text-sm text-gray-600">{{ s.label }}</span>
      </div>
    </div>
    <div class="card">
      <div v-for="floor in floors" :key="floor" class="mb-6">
        <h3 class="text-sm font-bold text-gray-500 mb-3">ÉTAGE {{ floor }}</h3>
        <div class="flex flex-wrap gap-3">
          <div v-for="room in roomsByFloor(floor)" :key="room.num"
            :class="['w-20 h-20 rounded-xl flex flex-col items-center justify-center cursor-pointer transition-all hover:scale-105 border-2', roomColor(room)]"
            @click="selectRoom(room)">
            <span class="text-xl">{{ room.icon }}</span>
            <span class="text-xs font-bold">{{ room.num }}</span>
            <span class="text-xs">{{ room.type }}</span>
          </div>
        </div>
      </div>
    </div>
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="selected=null">
        <div class="bg-white rounded-xl p-6 w-80 shadow-2xl">
          <h3 class="text-lg font-bold mb-3">Chambre {{ selected.num }}</h3>
          <p>Type: {{ selected.type }}</p>
          <p>Statut: {{ selected.statut }}</p>
          <p v-if="selected.client">Client: {{ selected.client }}</p>
          <button @click="selected=null" class="btn-outline w-full mt-4">Fermer</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
<script setup>
import { ref } from 'vue'
const floors = [1, 2, 3]
const statuses = [
  { val: 'libre', label: 'Libre', color: 'bg-green-400' },
  { val: 'occupe', label: 'Occupé', color: 'bg-red-400' },
  { val: 'menage', label: 'En ménage', color: 'bg-yellow-400' },
  { val: 'maintenance', label: 'Maintenance', color: 'bg-gray-400' },
]
const selected = ref(null)
const rooms = [
  { num:'101', type:'SGL', statut:'libre', etage:1, icon:'🟢' }, { num:'102', type:'DBL', statut:'occupe', etage:1, client:'Sophie M.', icon:'🔴' },
  { num:'103', type:'STE', statut:'menage', etage:1, icon:'🟡' }, { num:'104', type:'DBL', statut:'libre', etage:1, icon:'🟢' },
  { num:'201', type:'DBL', statut:'occupe', etage:2, client:'Ahmed B.', icon:'🔴' }, { num:'202', type:'SGL', statut:'libre', etage:2, icon:'🟢' },
  { num:'203', type:'DLX', statut:'maintenance', etage:2, icon:'⚫' }, { num:'204', type:'STE', statut:'occupe', etage:2, client:'Laura D.', icon:'🔴' },
  { num:'301', type:'PRE', statut:'libre', etage:3, icon:'🟢' }, { num:'302', type:'FAM', statut:'occupe', etage:3, client:'Robert P.', icon:'🔴' },
]
function roomsByFloor(f) { return rooms.filter(r => r.etage === f) }
function roomColor(r) {
  const m = { libre: 'border-green-400 bg-green-50 hover:border-green-500', occupe: 'border-red-400 bg-red-50', menage: 'border-yellow-400 bg-yellow-50', maintenance: 'border-gray-400 bg-gray-50' }
  return m[r.statut] || 'border-gray-200 bg-white'
}
function selectRoom(r) { selected.value = r }
</script>
