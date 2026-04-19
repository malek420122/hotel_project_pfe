<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ t('payments.historyTitle') }}</h2>
    <p v-if="errorMsg" class="mb-4 text-sm text-red-600">{{ errorMsg }}</p>
    <DataTable :columns="cols" :data="payments">
      <template #statut="{ row }">
        <StatusBadge :status="row.statut" />
      </template>
    </DataTable>
  </div>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DataTable from '../../../components/DataTable.vue'
import StatusBadge from '../../../components/StatusBadge.vue'
import api from '../../../api'

const { t } = useI18n()
const cols = computed(() => [
  { key: 'reference', label: t('dashboard.reference') },
  { key: 'hotel', label: t('dashboard.hotel') },
  { key: 'montant', label: t('payments.amount') },
  { key: 'methode', label: t('payments.method') },
  { key: 'date', label: t('dashboard.dates') },
  { key: 'statut', label: t('dashboard.status') },
])

const payments = ref([])
const errorMsg = ref('')
const route = useRoute()
const router = useRouter()

async function handleStripeReturn() {
  const paymentStatus = route.query.payment
  const sessionId = typeof route.query.session_id === 'string' ? route.query.session_id : ''

  if (paymentStatus !== 'success' || !sessionId) return

  try {
    await api.get(`/payments/verify-session/${encodeURIComponent(sessionId)}`)
  } catch (_error) {
    // Keep page usable even if webhook delay/verification fails.
  } finally {
    const cleanedQuery = { ...route.query }
    delete cleanedQuery.payment
    delete cleanedQuery.session_id
    delete cleanedQuery.reservation
    router.replace({ path: route.path, query: cleanedQuery })
  }
}

onMounted(async () => {
  await handleStripeReturn()
  try {
    errorMsg.value = ''
    const { data } = await api.get('/payments/history')
    payments.value = (data || []).map(row => ({
      ...row,
      montant: `${row.montant}€`,
      hotel: row.hotel || '-',
    }))
  } catch {
    payments.value = []
    errorMsg.value = t('payments.loadError')
  }
})
</script>
