<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des Hôtels</h2>
      <button @click="openModal()" class="btn-primary">+ Ajouter hôtel</button>
    </div>
    <DataTable :columns="cols" :data="hotels">
      <template #etoiles="{ row }">
        <span>{{ '★'.repeat(row.etoiles || 0) }}</span>
      </template>
      <template #statut="{ row }">
        <StatusBadge :status="row.estActif ? 'ACTIVE' : 'ANNULEE'" />
      </template>
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button @click="openModal(row)" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">✏️ Modifier</button>
          <button @click="confirmDelete(row)" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100">🗑️ Supprimer</button>
        </div>
      </template>
    </DataTable>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
          <h3 class="text-xl font-bold text-gray-800 mb-4">{{ form._id ? 'Modifier hôtel' : 'Ajouter un hôtel' }}</h3>
          <form @submit.prevent="saveHotel" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Nom</label><input v-model="form.nom" class="input-field" required /></div>
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Ville</label><input v-model="form.ville" class="input-field" required /></div>
            </div>
            <div><label class="block text-xs font-semibold text-gray-500 mb-1">Adresse</label><input v-model="form.adresse" class="input-field" required /></div>
            <div><label class="block text-xs font-semibold text-gray-500 mb-1">{{ $t('dashboard.description') }}</label><textarea v-model="form.description" rows="3" class="input-field"></textarea></div>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Étoiles</label>
                <select v-model="form.etoiles" class="input-field"><option v-for="i in 5" :key="i" :value="i">{{ i }}</option></select>
              </div>
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Prix min (€/nuit)</label><input v-model="form.prix_min" type="number" class="input-field" /></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Latitude</label><input v-model="form.latitude" type="number" step="any" class="input-field" required /></div>
              <div><label class="block text-xs font-semibold text-gray-500 mb-1">Longitude</label><input v-model="form.longitude" type="number" step="any" class="input-field" required /></div>
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showModal=false" class="btn-outline">{{ $t('common.cancel') }}</button>
              <button type="submit" class="btn-primary">{{ form._id ? 'Enregistrer' : 'Créer' }}</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
    <ConfirmModal :show="deleteModal.show" title="Supprimer l'hôtel"
      :message="`Voulez-vous vraiment supprimer ${deleteModal.hotel?.nom} ?`"
      danger @confirm="doDelete" @cancel="deleteModal.show=false" />
  </div>
</template>
<script setup>
import { ref, reactive, onMounted } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import ConfirmModal from '../../../components/ConfirmModal.vue'
import api from '../../../api'

const hotels = ref([])
const showModal = ref(false)
const deleteModal = ref({ show: false, hotel: null })
const form = reactive({ _id: null, nom: '', ville: '', adresse: '', description: '', etoiles: 4, prix_min: 0, latitude: null, longitude: null })
const cols = [
  { key: 'nom', label: 'Nom' }, { key: 'ville', label: 'Ville' }, { key: 'etoiles', label: 'Étoiles' },
  { key: 'prix_min', label: 'Prix min' }, { key: 'noteMoyenne', label: 'Note' }, { key: 'statut', label: 'Statut' }, { key: 'actions', label: 'Actions' }
]
async function fetchHotels() { const { data } = await api.get('/admin/hotels'); hotels.value = data.data || data }
function openModal(hotel) {
  if (hotel) Object.assign(form, { ...hotel, _id: hotel._id })
  else Object.assign(form, { _id: null, nom: '', ville: '', adresse: '', description: '', etoiles: 4, prix_min: 0, latitude: null, longitude: null })
  showModal.value = true
}
async function saveHotel() {
  try {
    if (form._id) await api.put(`/admin/hotels/${form._id}`, form)
    else await api.post('/admin/hotels', form)
    showModal.value = false
    await fetchHotels()
  } catch(e) { alert(e.response?.data?.message || 'Erreur') }
}
function confirmDelete(hotel) { deleteModal.value = { show: true, hotel } }
async function doDelete() {
  await api.delete(`/admin/hotels/${deleteModal.value.hotel._id}`)
  deleteModal.value.show = false
  await fetchHotels()
}
onMounted(fetchHotels)
</script>
