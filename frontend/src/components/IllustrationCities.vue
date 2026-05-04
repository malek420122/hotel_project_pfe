<template>
  <svg :width="size" :height="size" viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Cities illustration">
    <defs>
      <linearGradient id="citiesTop" x1="20" y1="20" x2="140" y2="140" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#E0E7FF"/>
        <stop offset="0.5" stop-color="#8B5CF6"/>
        <stop offset="1" stop-color="#6D28D9"/>
      </linearGradient>
      <linearGradient id="citiesSide" x1="20" y1="80" x2="140" y2="150" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#A855F7"/>
        <stop offset="0.55" stop-color="#C084FC"/>
        <stop offset="1" stop-color="#DDD6FE"/>
      </linearGradient>
      <linearGradient id="citiesGlass" x1="50" y1="48" x2="112" y2="120" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#FFFFFF" stop-opacity="0.97"/>
        <stop offset="1" stop-color="#F5F7FB" stop-opacity="0.9"/>
      </linearGradient>
    </defs>

    <ellipse cx="80" cy="131" rx="43" ry="10" fill="#8B5CF6" fill-opacity="0.16"/>

    <path d="M33 74L80 49L127 74L80 98L33 74Z" fill="url(#citiesTop)"/>
    <path d="M33 74V112L80 136V98L33 74Z" fill="url(#citiesSide)"/>
    <path d="M127 74V112L80 136V98L127 74Z" fill="#7C3AED"/>
    <path d="M33 74L80 49L127 74L80 98L33 74Z" stroke="#F5F3FF" stroke-width="2"/>

    <path d="M52 111V91L63 84L71 89V111Z" fill="url(#citiesGlass)"/>
    <path d="M69 111V78L80 72L88 77V111Z" fill="url(#citiesGlass)"/>
    <path d="M86 111V85L97 77L108 82V111Z" fill="url(#citiesGlass)"/>

    <circle cx="61" cy="92" r="4" fill="#8B5CF6"/>
    <circle cx="80" cy="77" r="4" fill="#8B5CF6"/>
    <circle cx="98" cy="83" r="4" fill="#8B5CF6"/>

    <path d="M61 96c0 6-5 12-8 15" stroke="#6D28D9" stroke-width="3" stroke-linecap="round"/>
    <path d="M80 81c0 9 1 15 2 22" stroke="#6D28D9" stroke-width="3" stroke-linecap="round"/>
    <path d="M98 87c0 7 3 12 8 18" stroke="#6D28D9" stroke-width="3" stroke-linecap="round"/>

    <circle cx="115" cy="52" r="9" fill="#F5F3FF"/>
    <path d="M115 46c-3 0-5 2-5 5 0 4 5 9 5 9s5-5 5-9c0-3-2-5-5-5z" fill="#8B5CF6"/>
  </svg>
</template>

<script setup>
defineProps({ size: { type: [Number, String], default: 160 } })
</script>
