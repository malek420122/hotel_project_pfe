<template>
  <div :class="['card border-l-4', colorClasses.border]">
    <div class="flex items-center justify-between mb-2">
      <p class="text-sm font-medium text-gray-500">{{ label }}</p>
      <div :class="['w-10 h-10 rounded-xl flex items-center justify-center text-xl', colorClasses.bg]">{{ icon }}</div>
    </div>
    <p class="text-3xl font-extrabold text-gray-800">{{ value }}</p>
    <div v-if="trend !== undefined" class="flex items-center gap-1 mt-1">
      <span :class="trend >= 0 ? 'text-green-500' : 'text-red-500'" class="text-xs font-semibold">
        {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
      </span>
      <span class="text-xs text-gray-400">vs mois dernier</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  icon: String,
  label: String,
  value: [String, Number],
  color: { type: String, default: 'blue' },
  trend: Number,
})
const colorClasses = computed(() => {
  const colors = {
    blue: { border: 'border-blue-400', bg: 'bg-blue-50' },
    green: { border: 'border-green-400', bg: 'bg-green-50' },
    gold: { border: 'border-yellow-400', bg: 'bg-yellow-50' },
    purple: { border: 'border-purple-400', bg: 'bg-purple-50' },
    red: { border: 'border-red-400', bg: 'bg-red-50' },
  }
  return colors[props.color] || colors.blue
})
</script>
