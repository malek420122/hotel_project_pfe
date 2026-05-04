<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-gray-800">Suivi des paiements</h2>
      <div class="flex gap-2">
        <button @click="fetchPayments" class="btn-primary text-sm">Actualiser</button>
        <button @click="exportPaymentsCSV" class="btn-outline text-sm">📥 {{ $t('dashboard.exportCsv') }}</button>
      </div>
    </div>
    <p v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</p>

    <div class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left border-b">
            <th class="p-2">Reservation</th>
            <th class="p-2">Client</th>
            <th class="p-2">Hotel</th>
            <th class="p-2">Montant</th>
            <th class="p-2">Methode</th>
            <th class="p-2">{{ $t('dashboard.status') }}</th>
            <th class="p-2">Transaction</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in payments" :key="p._id" class="border-b last:border-b-0">
            <td class="p-2">{{ p.reservationReference || p.reservationId || '-' }}</td>
            <td class="p-2">{{ p.clientName || '-' }}</td>
            <td class="p-2">{{ p.hotelName || '-' }}</td>
            <td class="p-2">{{ p.montant }} EUR</td>
            <td class="p-2">{{ p.methode }}</td>
            <td class="p-2"><StatusBadge :status="p.statut" /></td>
            <td class="p-2">{{ p.transactionId || '-' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="!payments.length" class="text-gray-500 py-4">Aucun paiement trouve.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '../../../api'
import StatusBadge from '../../../components/StatusBadge.vue'

const payments = ref([])
const errorMsg = ref('')

async function fetchPayments() {
  try {
    errorMsg.value = ''
    const { data } = await api.get('/admin/payments')
    const rows = Array.isArray(data) ? data : []
    payments.value = rows.map((row) => ({
      ...row,
      reservationReference: row.reservationReference || row.reference || row.reservationId || '-',
      clientName: row.clientName || [row?.client?.prenom, row?.client?.nom].filter(Boolean).join(' ') || '-',
      hotelName: row.hotelName || row?.hotel?.nom || '-',
      montant: Number(row?.montant || 0).toFixed(2),
    }))
  } catch {
    payments.value = []
    errorMsg.value = 'Impossible de charger les paiements.'
  }
}

async function exportPaymentsCSV() {
  try {
    errorMsg.value = ''
    const response = await api.get('/admin/payments/export-csv', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `paiements-${new Date().toISOString().slice(0, 10)}.csv`)
    document.body.appendChild(link)
    link.click()
    link.parentNode.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch {
    errorMsg.value = 'Impossible d\'exporter les paiements.'
  }
}

onMounted(fetchPayments)
</script>
