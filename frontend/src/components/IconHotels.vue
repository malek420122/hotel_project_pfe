<template>
  <svg :width="size" :height="size" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="3" y="10" width="22" height="16" rx="3" :stroke="color" stroke-width="2" fill="none"/>
    <path d="M3 14h22" :stroke="color" stroke-width="1.5"/>
    <rect x="7"  y="17" width="4" height="4" rx="1" :fill="color" opacity=".7"/>
    <rect x="12" y="17" width="4" height="4" rx="1" :fill="color" opacity=".7"/>
    <rect x="17" y="17" width="4" height="4" rx="1" :fill="color" opacity=".7"/>
    <path d="M9 10V6a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v4" :stroke="color" stroke-width="2" fill="none"/>
    <rect x="11" y="22" width="6" height="4" rx="1" :fill="color" opacity=".5"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 28 }, color: { type: String, default: '#4A6CF7' } })
</script>
