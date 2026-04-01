<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Historique des Paiements</h2>
    <DataTable :columns="cols" :data="payments">
      <template #statut="{ row }">
        <StatusBadge :status="row.statut" />
      </template>
    </DataTable>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import api from '../../../api'
const cols = [
  { key: 'reference', label: 'Référence' },
  { key: 'hotel', label: 'Hôtel' },
  { key: 'montant', label: 'Montant' },
  { key: 'methode', label: 'Méthode' },
  { key: 'date', label: 'Date' },
  { key: 'statut', label: 'Statut' },
]
const payments = ref([])

onMounted(async () => {
  const { data } = await api.get('/payments/history')
  payments.value = (data || []).map(row => ({
    ...row,
    montant: `${row.montant}€`,
    hotel: row.hotel || '-',
  }))
})
</script>
