<template>
  <svg :width="size" :height="size" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="14" cy="9" r="4" :stroke="color" stroke-width="1.6" fill="none" />
    <path d="M4 24c1-4 6-6 10-6s9 2 10 6" :stroke="color" stroke-width="1.6" fill="none"/>
    <circle cx="8" cy="12" r="2" :fill="color" opacity=".7" />
    <circle cx="20" cy="12" r="2" :fill="color" opacity=".7" />
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 28 }, color: { type: String, default: '#4A6CF7' } })
</script>
