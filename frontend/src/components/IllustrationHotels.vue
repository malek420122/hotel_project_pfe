<template>
  <svg :width="size" :height="size" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Hotels illustration">
    <defs>
      <linearGradient id="hotelsTop" x1="20" y1="20" x2="140" y2="140" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FDE7B7"/>
        <stop offset="0.48" stop-color="#F59E0B"/>
        <stop offset="1" stop-color="#D97706"/>
      </linearGradient>
      <linearGradient id="hotelsSide" x1="20" y1="80" x2="140" y2="150" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#7C3AED"/>
        <stop offset="0.55" stop-color="#A855F7"/>
        <stop offset="1" stop-color="#C084FC"/>
      </linearGradient>
      <linearGradient id="hotelsGlass" x1="50" y1="50" x2="110" y2="120" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.98"/>
        <stop offset="1" stop-color="#F5F7FB" stop-opacity="0.9"/>
      </linearGradient>
      <filter id="hotelsShadow" x="0" y="0" width="160" height="160" filterUnits="userSpaceOnUse">
        <feGaussianBlur in="SourceAlpha" stdDeviation="5" result="blur"/>
        <feOffset dy="8"/>
        <feColorMatrix values="0 0 0 0 0.24 0 0 0 0 0.11 0 0 0 0 0.02 0 0 0 0.16 0"/>
        <feBlend in2="SourceGraphic" mode="normal"/>
      </filter>
    </defs>

    <ellipse cx="80" cy="131" rx="43" ry="10" fill="#F59E0B" fill-opacity="0.18"/>

    <g filter="url(#hotelsShadow)">
      <path d="M33 74L80 49L127 74L80 98L33 74Z" fill="url(#hotelsTop)"/>
      <path d="M33 74V112L80 136V98L33 74Z" fill="url(#hotelsSide)"/>
      <path d="M127 74V112L80 136V98L127 74Z" fill="#5B21B6"/>
      <path d="M33 74L80 49L127 74L80 98L33 74Z" stroke="#FFF7E7" stroke-width="2"/>

      <path d="M51 82L80 66L109 82V110L80 126L51 110V82Z" fill="url(#hotelsGlass)"/>
      <path d="M62 91H98" stroke="#7C3AED" stroke-width="3" stroke-linecap="round"/>
      <path d="M62 100H98" stroke="#7C3AED" stroke-width="3" stroke-linecap="round" opacity="0.9"/>
      <path d="M62 109H98" stroke="#7C3AED" stroke-width="3" stroke-linecap="round" opacity="0.8"/>

      <rect x="71" y="86" width="18" height="34" rx="5" fill="#F59E0B"/>
      <rect x="75" y="92" width="10" height="8" rx="2" fill="#FFF7E7"/>
      <rect x="75" y="103" width="10" height="8" rx="2" fill="#FFF7E7"/>

      <circle cx="116" cy="49" r="10" fill="#FFF7E7"/>
      <path d="M116 44v10M111 49h10" stroke="#D97706" stroke-width="3" stroke-linecap="round"/>
    </g>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 160 } })
</script>
