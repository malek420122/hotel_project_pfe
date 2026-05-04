<template>
  <svg :width="size" :height="size" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M14 3c3 0 6 2 7 5 1 3 0 6-3 8-2 1-4 2-4 6" :stroke="color" stroke-width="1.6" fill="none"/>
    <circle cx="9" cy="17" r="2" :fill="color" opacity=".7"/>
    <circle cx="19" cy="12" r="1.6" :fill="color" opacity=".7"/>
    <path d="M2 24h24" :stroke="color" stroke-width="1" opacity=".6"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 28 }, color: { type: String, default: '#4A6CF7' } })
</script>
