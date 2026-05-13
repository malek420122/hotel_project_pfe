<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des Hôtels</h2>
      <button @click="openModal()" class="btn-primary">+ Ajouter hôtel</button>
    </div>
    <p v-if="errorMsg" class="mb-4 text-sm text-red-600">{{ errorMsg }}</p>
    <DataTable :columns="cols" :data="hotels">
      <template #etoiles="{ row }">
        <span>{{ '★'.repeat(row.etoiles || 0) }}</span>
      </template>
      <template #statut="{ row }">
        <StatusBadge :status="row.estActif ? 'ACTIVE' : 'ANNULEE'" />
      </template>
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button
            @click="toggleHotel(row)"
            :class="['text-xs px-3 py-1.5 rounded-lg', row.estActif ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100']"
          >
            {{ row.estActif ? '🔒 Masquer' : '👁️ Afficher' }}
          </button>
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

            <!-- Images Management -->
            <div class="space-y-3 pt-2 border-t">
              <label class="block text-xs font-semibold text-gray-500">Photos de l'hôtel</label>
              
              <div class="flex flex-col gap-3">
                <!-- File Upload -->
                <div class="flex gap-2">
                  <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="onFileChange" />
                  <button type="button" @click="$refs.fileInput.click()" :disabled="uploading" class="flex-1 px-4 py-2 bg-secondary/10 text-secondary rounded-lg hover:bg-secondary/20 text-sm font-medium transition-colors flex items-center justify-center gap-2 border border-secondary/20">
                    <span v-if="uploading" class="w-4 h-4 border-2 border-secondary border-t-transparent rounded-full animate-spin"></span>
                    {{ uploading ? 'Upload en cours...' : '📁 Choisir un fichier' }}
                  </button>
                </div>

                <div class="relative">
                  <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                  <div class="relative flex justify-center text-xs uppercase"><span class="bg-white px-2 text-gray-400">ou par URL</span></div>
                </div>

                <!-- URL Input -->
                <div class="flex gap-2">
                  <input v-model="newPhotoUrl" placeholder="https://..." class="input-field flex-1" @keyup.enter="addPhoto" />
                  <button type="button" @click="addPhoto" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">Ajouter</button>
                </div>
              </div>
              
              <div v-if="form.photos && form.photos.length" class="grid grid-cols-4 gap-3 mt-3">
                <div v-for="(photo, index) in form.photos" :key="index" class="relative group aspect-video rounded-lg overflow-hidden bg-gray-100 border">
                  <img :src="photo" class="w-full h-full object-cover" />
                  <button type="button" @click="removePhoto(index)" class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400 italic">Aucune photo personnalisée. Les images par défaut seront utilisées.</p>
            </div>

            <div class="flex gap-3 justify-end pt-4">
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
const errorMsg = ref('')
const newPhotoUrl = ref('')
const uploading = ref(false)
const fileInput = ref(null)

const form = reactive({ _id: null, nom: '', ville: '', adresse: '', description: '', etoiles: 4, prix_min: 0, latitude: null, longitude: null, photos: [] })

async function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return

  try {
    uploading.value = true
    errorMsg.value = ''
    
    const formData = new FormData()
    formData.append('image', file)

    const { data } = await api.post('/admin/hotels/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (data.url) {
      if (!form.photos) form.photos = []
      form.photos.push(data.url)
    }
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Erreur lors de l\'upload'
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}
const cols = [
  { key: 'nom', label: 'Nom' }, { key: 'ville', label: 'Ville' }, { key: 'etoiles', label: 'Étoiles' },
  { key: 'prix_min', label: 'Prix min' }, { key: 'noteMoyenne', label: 'Note' }, { key: 'statut', label: 'Statut' }, { key: 'actions', label: 'Actions' }
]
async function fetchHotels() {
  const { data } = await api.get('/admin/hotels', { params: { per_page: 100 } })
  hotels.value = data.data || data
}
function openModal(hotel) {
  if (hotel) {
    Object.assign(form, { 
      ...hotel, 
      _id: hotel._id,
      photos: Array.isArray(hotel.photos) ? [...hotel.photos] : []
    })
  } else {
    Object.assign(form, { _id: null, nom: '', ville: '', adresse: '', description: '', etoiles: 4, prix_min: 0, latitude: null, longitude: null, photos: [] })
  }
  newPhotoUrl.value = ''
  showModal.value = true
}
function addPhoto() {
  if (!newPhotoUrl.value.trim()) return
  if (!form.photos) form.photos = []
  form.photos.push(newPhotoUrl.value.trim())
  newPhotoUrl.value = ''
}
function removePhoto(index) {
  form.photos.splice(index, 1)
}
async function saveHotel() {
  try {
    errorMsg.value = ''
    if (form._id) await api.put(`/admin/hotels/${form._id}`, form)
    else await api.post('/admin/hotels', form)
    showModal.value = false
    await fetchHotels()
  } catch(e) { errorMsg.value = e.response?.data?.message || 'Erreur' }
}
async function toggleHotel(hotel) {
  try {
    errorMsg.value = ''
    await api.put(`/admin/hotels/${hotel._id}/toggle`)
    await fetchHotels()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Erreur lors du changement de statut'
  }
}
function confirmDelete(hotel) { deleteModal.value = { show: true, hotel } }
async function doDelete() {
  try {
    errorMsg.value = ''
    await api.delete(`/admin/hotels/${deleteModal.value.hotel._id}`)
    deleteModal.value.show = false
    await fetchHotels()
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Erreur lors de la suppression'
    deleteModal.value.show = false
  }
}
onMounted(fetchHotels)
</script>
