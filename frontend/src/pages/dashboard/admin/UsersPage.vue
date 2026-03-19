<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Gestion des Utilisateurs</h2>
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
          <button @click="changeRole(row)" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600">🔑 Rôle</button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import api from '../../../api'
const users = ref([])
const cols = [
  { key: 'prenom', label: 'Prénom' }, { key: 'nom', label: 'Nom' }, { key: 'email', label: 'Email' },
  { key: 'role', label: 'Rôle' }, { key: 'est_actif', label: 'Statut' }, { key: 'actions', label: 'Actions' }
]
const roleColors = { admin: 'bg-red-100 text-red-700', client: 'bg-blue-100 text-blue-700', receptionniste: 'bg-green-100 text-green-700', marketing: 'bg-purple-100 text-purple-700' }
async function fetchUsers() { try { const { data } = await api.get('/admin/users'); users.value = data } catch {} }
async function toggleUser(user) { await api.put(`/admin/users/${user._id}`, { est_actif: !user.est_actif }); await fetchUsers() }
async function changeRole(user) {
  const newRole = prompt('Nouveau rôle (client/admin/receptionniste/marketing):', user.role)
  if (newRole) { await api.put(`/admin/users/${user._id}`, { role: newRole }); await fetchUsers() }
}
onMounted(fetchUsers)
</script>
