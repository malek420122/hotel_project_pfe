<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des Chambres</h2>
      <button @click="showModal=true" class="btn-primary">+ Ajouter chambre</button>
    </div>
    <p v-if="errorMsg" class="mb-4 text-sm text-red-600">{{ errorMsg }}</p>
    <div class="card mb-4">
      <label class="block text-sm font-semibold text-gray-600 mb-1">Filtrer par hôtel</label>
      <select v-model="selectedHotel" class="input-field max-w-xs" @change="loadRooms">
        <option value="">Tous les hôtels</option>
        <option v-for="h in hotels" :key="h._id" :value="h._id">{{ h.nom }}</option>
      </select>
    </div>
    <DataTable :columns="cols" :data="rooms">
      <template #statut="{ row }">
        <StatusBadge :status="row.statut" />
      </template>
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button @click="editRoom(row)" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600">✏️ Modifier</button>
          <button @click="deleteRoom(row)" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600">🗑️ Supprimer</button>
        </div>
      </template>
    </DataTable>
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl p-6">
          <h3 class="text-xl font-bold mb-4">{{ form._id ? 'Modifier chambre' : 'Nouvelle chambre' }}</h3>
          <form @submit.prevent="saveRoom" class="space-y-3">
            <div>
              <label for="room-hotel" class="block text-sm font-semibold text-gray-600 mb-1">{{ $t('dashboard.hotel') }}</label>
              <select id="room-hotel" v-model="form.hotelId" class="input-field" required>
                <option value="" disabled>Choisir hôtel</option>
                <option v-for="h in hotels" :key="h._id" :value="h._id">{{ h.nom }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.nom" placeholder="Nom chambre" class="input-field" required />
              <input v-model="form.numero" placeholder="N° chambre" class="input-field" />
            </div>
            <select v-model="form.type" class="input-field">
              <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
            </select>
            <div class="grid grid-cols-3 gap-3">
              <input v-model="form.prix_base" type="number" placeholder="Prix/nuit €" class="input-field" required />
              <input v-model="form.maxVoyageurs" type="number" placeholder="Capacité" class="input-field" required />
              <input v-model="form.etage" type="number" placeholder="Étage" class="input-field" />
            </div>
            <textarea v-model="form.description" rows="2" :placeholder="$t('dashboard.description')" class="input-field"></textarea>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showModal=false" class="btn-outline">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
    <ConfirmModal
      :show="deleteModal.show"
      title="Supprimer la chambre"
      :message="`Voulez-vous vraiment supprimer ${deleteModal.room?.nom} ?`"
      danger
      @confirm="confirmDeleteRoom"
      @cancel="deleteModal.show = false"
    />
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import ConfirmModal from '../../../components/ConfirmModal.vue'
import api from '../../../api'
const rooms = ref([])
const hotels = ref([])
const showModal = ref(false)
const selectedHotel = ref('')
const errorMsg = ref('')
const deleteModal = ref({ show: false, room: null })
const types = ['SIMPLE','DOUBLE','SUITE','FAMILIALE','DELUXE','PRESIDENTIELLE']
const form = reactive({ _id: null, hotelId: '', nom: '', numero: '', type: 'DOUBLE', prix_base: 0, maxVoyageurs: 2, etage: 0, description: '' })
const cols = [
  { key: 'nom', label: 'Nom' }, { key: 'type', label: 'Type' }, { key: 'prix_base', label: 'Prix/nuit' },
  { key: 'maxVoyageurs', label: 'Capacité' }, { key: 'etage', label: 'Étage' }, { key: 'statut', label: 'Statut' }, { key: 'actions', label: 'Actions' }
]
async function loadRooms() {
  try {
    const { data } = await api.get('/admin/chambres', { params: selectedHotel.value ? { hotelId: selectedHotel.value } : {} })
    rooms.value = data.data || data
  } catch {}
}
function editRoom(r) {
  Object.assign(form, {
    ...r,
    _id: r._id,
    hotelId: r.hotelId ?? '',
  })
  showModal.value = true
}
async function saveRoom() {
  try {
    errorMsg.value = ''
    if (form._id) await api.put(`/admin/chambres/${form._id}`, form)
    else await api.post('/admin/chambres', form)
    showModal.value = false; await loadRooms()
  } catch(e) { errorMsg.value = e.response?.data?.message || 'Erreur' }
}
function deleteRoom(r) {
  deleteModal.value = { show: true, room: r }
}

async function confirmDeleteRoom() {
  const room = deleteModal.value.room
  if (!room?._id) return

  try {
    errorMsg.value = ''
    await api.delete(`/admin/chambres/${room._id}`)
    deleteModal.value.show = false
    await loadRooms()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Impossible de supprimer la chambre.'
  }
}
onMounted(async () => {
  const { data } = await api.get('/admin/hotels')
  hotels.value = data.data || data
  await loadRooms()
})
</script>
