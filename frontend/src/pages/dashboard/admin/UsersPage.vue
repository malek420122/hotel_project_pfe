<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des Utilisateurs</h2>
      <button @click="openCreateModal" class="btn-primary text-sm">➕ Ajouter un utilisateur</button>
    </div>
    <DataTable :columns="cols" :data="users">
      <template #role="{ row }">
        <span :class="roleColors[row.role] || 'bg-gray-100 text-gray-600'" class="px-2 py-1 rounded-full text-xs font-semibold">{{ row.role }}</span>
      </template>
      <template #est_actif="{ row }">
        <StatusBadge :status="row.est_actif ? 'ACTIVE' : 'ANNULEE'" />
      </template>
      <template #actions="{ row }">
        <div class="flex gap-2">
          <button @click="toggleUser(row)" :class="['text-xs px-3 py-1.5 rounded-lg', row.est_actif ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600']">
            {{ row.est_actif ? '🚫 Désactiver' : '✅ Activer' }}
          </button>
          <button @click="openRoleModal(row)" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600">🔑 Rôle</button>
          <button
            v-if="!isCurrentUser(row)"
            @click="confirmDelete(row)"
            class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100"
          >
            🗑️ Supprimer
          </button>
        </div>
      </template>
    </DataTable>

    <ConfirmModal
      :show="deleteModal.show"
      title="Supprimer l'utilisateur"
      :message="`Voulez-vous vraiment supprimer ${deleteModal.user?.prenom} ${deleteModal.user?.nom} ? Cette action est définitive.`"
      danger
      @confirm="doDelete"
      @cancel="deleteModal.show = false"
    />

    <div v-if="createModal.show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-xl w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Ajouter un utilisateur</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
            <input v-model="createModal.form.prenom" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
            <input v-model="createModal.form.nom" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="createModal.form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
            <input v-model="createModal.form.telephone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
            <input v-model="createModal.form.password" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
            <select v-model="createModal.form.role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option value="client">Client</option>
              <option value="receptionniste">Réceptionniste</option>
              <option value="admin">Admin</option>
              <option value="marketing">Marketing</option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex gap-3 justify-end">
          <button @click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
            Annuler
          </button>
          <button @click="doCreate" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
            Créer
          </button>
        </div>
      </div>
    </div>

    <!-- Role Change Modal -->
    <div v-if="roleModal.show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Changer le rôle</h3>
        <p class="text-sm text-gray-600 mb-4">
          Utilisateur: <strong>{{ roleModal.user?.prenom }} {{ roleModal.user?.nom }}</strong>
        </p>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau rôle</label>
          <select v-model="roleModal.newRole" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="client">Client</option>
            <option value="receptionniste">Réceptionniste</option>
            <option value="admin">Admin</option>
            <option value="marketing">Marketing</option>
          </select>
        </div>
        <div class="flex gap-3 justify-end">
          <button @click="roleModal.show = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
            Annuler
          </button>
          <button @click="doChangeRole" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import ConfirmModal from '../../../components/ConfirmModal.vue'
import api from '../../../api'
import { useAuthStore } from '../../../stores/auth'
const users = ref([])
const deleteModal = ref({ show: false, user: null })
const roleModal = ref({ show: false, user: null, newRole: '' })
const createModal = ref({
  show: false,
  form: {
    nom: '',
    prenom: '',
    email: '',
    telephone: '',
    password: '',
    role: 'client',
  },
})
const auth = useAuthStore()
const cols = [
  { key: 'prenom', label: 'Prénom' }, { key: 'nom', label: 'Nom' }, { key: 'email', label: 'Email' },
  { key: 'role', label: 'Rôle' }, { key: 'est_actif', label: 'Statut' }, { key: 'actions', label: 'Actions' }
]
const roleColors = { admin: 'bg-red-100 text-red-700', client: 'bg-blue-100 text-blue-700', receptionniste: 'bg-green-100 text-green-700', marketing: 'bg-purple-100 text-purple-700' }
async function fetchUsers() { try { const { data } = await api.get('/admin/users'); users.value = data } catch {} }
function openCreateModal() {
  createModal.value = {
    show: true,
    form: {
      nom: '',
      prenom: '',
      email: '',
      telephone: '',
      password: '',
      role: 'client',
    },
  }
}
function closeCreateModal() {
  createModal.value.show = false
}
async function doCreate() {
  try {
    const payload = {
      ...createModal.value.form,
      telephone: createModal.value.form.telephone || '',
    }
    await api.post('/admin/users', payload)
    closeCreateModal()
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Impossible de créer cet utilisateur.')
  }
}
async function toggleUser(user) { await api.put(`/admin/users/${user._id}`, { est_actif: !user.est_actif }); await fetchUsers() }
function openRoleModal(user) {
  roleModal.value = { show: true, user, newRole: user.role }
}
async function doChangeRole() {
  if (!roleModal.value.user || !roleModal.value.newRole) return
  try {
    await api.put(`/admin/users/${roleModal.value.user._id}`, { role: roleModal.value.newRole })
    roleModal.value = { show: false, user: null, newRole: '' }
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Impossible de changer le rôle.')
  }
}
function confirmDelete(user) { deleteModal.value = { show: true, user } }
function isCurrentUser(user) {
  return String(user?._id) === String(auth.user?._id)
}
async function doDelete() {
  if (!deleteModal.value.user) return
  try {
    await api.delete(`/admin/users/${deleteModal.value.user._id}`)
    deleteModal.value = { show: false, user: null }
    await fetchUsers()
  } catch (error) {
    alert(error.response?.data?.message || 'Impossible de supprimer cet utilisateur.')
  }
}
onMounted(fetchUsers)
</script>
