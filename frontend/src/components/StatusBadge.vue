<template>
  <span :class="['inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold', colors[status] || 'bg-gray-100 text-gray-700']">
    <span class="w-1.5 h-1.5 rounded-full" :class="dots[status] || 'bg-gray-400'"></span>
    {{ t(`status.${status}`, status) }}
  </span>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
defineProps({ status: { type: String, default: '' } })
const colors = {
  EN_ATTENTE: 'bg-yellow-100 text-yellow-800',
  CONFIRMEE: 'bg-blue-100 text-blue-800',
  EN_COURS: 'bg-green-100 text-green-800',
  TERMINEE: 'bg-gray-100 text-gray-800',
  ANNULEE: 'bg-red-100 text-red-700',
  PAYE: 'bg-green-100 text-green-800',
  EN_COURS_PAIEMENT: 'bg-blue-100 text-blue-800',
  ECHOUE: 'bg-red-100 text-red-700',
  REMBOURSE: 'bg-purple-100 text-purple-700',
  PUBLIE: 'bg-green-100 text-green-800',
  REJETE: 'bg-red-100 text-red-700',
  EN_ATTENTE_MODERATION: 'bg-yellow-100 text-yellow-800',
  ACTIVE: 'bg-green-100 text-green-800',
  INACTIVE: 'bg-gray-100 text-gray-600',
  EXPIREE: 'bg-orange-100 text-orange-700',
}
const dots = {
  EN_ATTENTE: 'bg-yellow-500',
  CONFIRMEE: 'bg-blue-500',
  EN_COURS: 'bg-green-500',
  TERMINEE: 'bg-gray-500',
  ANNULEE: 'bg-red-500',
  PAYE: 'bg-green-500',
  ACTIVE: 'bg-green-500',
}
</script>
