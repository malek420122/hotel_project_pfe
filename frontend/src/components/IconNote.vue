<template>
  <svg :width="size" :height="size" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M14 4l2.5 5.5L22 11l-4 3 1 6-5-3-5 3 1-6-4-3 5.5-.5L14 4z" :stroke="color" stroke-width="0.8" :fill="color" opacity=".95"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 28 }, color: { type: String, default: '#4A6CF7' } })
</script>
