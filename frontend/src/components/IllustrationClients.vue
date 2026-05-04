<template>
  <svg :width="size" :height="size" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Clients illustration">
    <defs>
      <linearGradient id="clientsTop" x1="20" y1="20" x2="140" y2="140" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#D1FAE5"/>
        <stop offset="0.5" stop-color="#10B981"/>
        <stop offset="1" stop-color="#0F766E"/>
      </linearGradient>
      <linearGradient id="clientsSide" x1="20" y1="80" x2="140" y2="150" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#059669"/>
        <stop offset="0.55" stop-color="#34D399"/>
        <stop offset="1" stop-color="#6EE7B7"/>
      </linearGradient>
      <linearGradient id="clientsGlass" x1="52" y1="52" x2="108" y2="116" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.98"/>
        <stop offset="1" stop-color="#F5F7FB" stop-opacity="0.9"/>
      </linearGradient>
    </defs>

    <ellipse cx="80" cy="131" rx="43" ry="10" fill="#10B981" fill-opacity="0.16"/>

    <path d="M33 74L80 49L127 74L80 98L33 74Z" fill="url(#clientsTop)"/>
    <path d="M33 74V112L80 136V98L33 74Z" fill="url(#clientsSide)"/>
    <path d="M127 74V112L80 136V98L127 74Z" fill="#047857"/>
    <path d="M33 74L80 49L127 74L80 98L33 74Z" stroke="#ECFDF5" stroke-width="2"/>

    <circle cx="80" cy="73" r="20" fill="url(#clientsGlass)"/>
    <circle cx="80" cy="67" r="8" stroke="#0F172A" stroke-width="3"/>
    <path d="M67 86c2-8 8-12 13-12s11 4 13 12" stroke="#0F172A" stroke-width="3" stroke-linecap="round"/>
    <path d="M92 64l6 4 7-8" stroke="#0F172A" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>

    <circle cx="112" cy="53" r="9" fill="#ECFDF5"/>
    <circle cx="112" cy="53" r="4" fill="#10B981"/>
    <path d="M112 60v8" stroke="#10B981" stroke-width="3" stroke-linecap="round"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 160 } })
</script>
