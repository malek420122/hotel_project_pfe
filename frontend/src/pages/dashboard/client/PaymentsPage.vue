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
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
const cols = [
  { key: 'reference', label: 'Référence' },
  { key: 'hotel', label: 'Hôtel' },
  { key: 'montant', label: 'Montant' },
  { key: 'methode', label: 'Méthode' },
  { key: 'date', label: 'Date' },
  { key: 'statut', label: 'Statut' },
]
const payments = [
  { reference: 'PAY-001', hotel: 'Hôtel Atlas', montant: '450€', methode: 'Carte Visa', date: '10/03/2025', statut: 'PAYE' },
  { reference: 'PAY-002', hotel: 'Riad Al Baraka', montant: '320€', methode: 'PayPal', date: '22/02/2025', statut: 'PAYE' },
]
</script>
