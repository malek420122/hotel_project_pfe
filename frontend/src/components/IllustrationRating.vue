<template>
  <svg :width="size" :height="size" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Rating illustration">
    <defs>
      <linearGradient id="ratingTop" x1="20" y1="20" x2="140" y2="140" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FEF3C7"/>
        <stop offset="0.5" stop-color="#F59E0B"/>
        <stop offset="1" stop-color="#EA580C"/>
      </linearGradient>
      <linearGradient id="ratingSide" x1="20" y1="80" x2="140" y2="150" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#D97706"/>
        <stop offset="0.55" stop-color="#FB923C"/>
        <stop offset="1" stop-color="#FDBA74"/>
      </linearGradient>
      <filter id="ratingGlow" x="0" y="0" width="160" height="160" filterUnits="userSpaceOnUse">
        <feGaussianBlur stdDeviation="2.5" result="blur"/>
        <feMerge>
          <feMergeNode in="blur"/>
          <feMergeNode in="SourceGraphic"/>
        </feMerge>
      </filter>
    </defs>

    <ellipse cx="80" cy="131" rx="43" ry="10" fill="#F59E0B" fill-opacity="0.16"/>

    <path d="M33 74L80 49L127 74L80 98L33 74Z" fill="url(#ratingTop)"/>
    <path d="M33 74V112L80 136V98L33 74Z" fill="url(#ratingSide)"/>
    <path d="M127 74V112L80 136V98L127 74Z" fill="#C2410C"/>
    <path d="M33 74L80 49L127 74L80 98L33 74Z" stroke="#FFF7E7" stroke-width="2"/>

    <g filter="url(#ratingGlow)">
      <path d="M80 66l5.7 11.6 12.9 1.9-9.3 9.1 2.2 12.8L80 95.5 68.5 101l2.2-12.8-9.3-9.1 12.9-1.9L80 66Z" fill="#FFF7E7"/>
      <path d="M80 71l4.3 8.7 9.6 1.4-6.9 6.7 1.6 9.5L80 92.7 71.4 97.3l1.6-9.5-6.9-6.7 9.6-1.4L80 71Z" fill="#F59E0B"/>
    </g>

    <circle cx="113" cy="52" r="10" fill="#FFF7E7"/>
    <path d="M113 46v12M107 52h12" stroke="#EA580C" stroke-width="3" stroke-linecap="round"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 160 } })
</script>
