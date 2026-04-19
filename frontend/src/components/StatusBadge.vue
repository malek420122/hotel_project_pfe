<template>
  <span :class="['dashboard-status-badge', statusClass]">
    <span class="dashboard-status-dot"></span>
    {{ statusText }}
  </span>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
import { computed } from 'vue'

const props = defineProps({ status: { type: String, default: '' } })

const normalizedStatus = computed(() => String(props.status || '').toUpperCase())

const statusText = computed(() => {
  const keyMap = {
    CHECKOUT: 'status.completed',
    TERMINEE: 'status.completed',
  }

  const key = keyMap[normalizedStatus.value] || `status.${normalizedStatus.value}`
  const label = t(key, normalizedStatus.value)

  if (['CONFIRMEE', 'PAYE', 'ACTIVE', 'PUBLIE'].includes(normalizedStatus.value)) {
    return `✓ ${label}`
  }

  return label
})

const statusClass = computed(() => {
  const map = {
    CONFIRMEE: 'status-confirmed',
    EN_ATTENTE: 'status-pending',
    ANNULEE: 'status-cancelled',
    EN_COURS: 'status-checkin',
    CHECKIN: 'status-checkin',
    TERMINEE: 'status-completed',
    CHECKOUT: 'status-completed',
    PAYE: 'status-confirmed',
    ACTIVE: 'status-confirmed',
    PUBLIE: 'status-confirmed',
    REJETE: 'status-cancelled',
    ECHOUE: 'status-cancelled',
    EXPIREE: 'status-pending',
  }

  return map[normalizedStatus.value] || 'status-default'
})
</script>

<style scoped>
.dashboard-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.42rem;
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
}

.dashboard-status-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: currentColor;
}

.status-confirmed {
  color: var(--success);
  background: rgba(16, 185, 129, 0.15);
}

.status-pending {
  color: var(--warning);
  background: rgba(245, 158, 11, 0.15);
}

.status-cancelled {
  color: var(--danger);
  background: rgba(239, 68, 68, 0.15);
}

.status-checkin {
  color: var(--blue);
  background: rgba(26, 86, 219, 0.15);
}

.status-completed {
  color: #64748b;
  background: rgba(148, 163, 184, 0.22);
}

.status-default {
  color: var(--text-secondary);
  background: rgba(148, 163, 184, 0.15);
}
</style>
