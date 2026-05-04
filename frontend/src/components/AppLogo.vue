<template>
  <div :class="['inline-flex items-center gap-2.5 select-none', sizeClass]" role="img" aria-label="HotelEase logo">
    <svg
      :width="iconSize"
      :height="iconSize"
      viewBox="0 0 64 64"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      class="shrink-0"
    >
      <path d="M12 48V22L32 11L52 22V48" :fill="iconFill" :stroke="iconStroke" stroke-width="2" stroke-linejoin="round" />
      <path d="M22 48V34H42V48" :fill="innerFill" :stroke="iconStroke" stroke-width="2" stroke-linejoin="round" />
      <rect x="28" y="36" width="8" height="12" rx="2" :fill="accent" />
      <path d="M18 24L32 16L46 24" :stroke="accent" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M26 30H38" :stroke="iconStroke" stroke-width="2.2" stroke-linecap="round" />
      <circle cx="45.5" cy="16.5" r="4.2" :fill="accent" />
      <path d="M45.5 12.6L46.4 14.9L48.9 15.1L47 16.7L47.6 19L45.5 17.8L43.4 19L44 16.7L42.1 15.1L44.6 14.9L45.5 12.6Z" fill="#fff8ea" />
    </svg>

    <div class="leading-none brand-title">
      <span class="word-hotel" :style="{ color: textMain }">Hotel</span><span class="word-ease" :style="{ color: accent }">Ease</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'light',
    validator: (value) => ['light', 'dark'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
})

const accent = '#D4820A'
const iconFill = computed(() => (props.variant === 'dark' ? 'rgba(255,255,255,0.16)' : 'rgba(212,130,10,0.10)'))
const iconStroke = computed(() => (props.variant === 'dark' ? '#FFFFFF' : '#8B4513'))
const innerFill = computed(() => (props.variant === 'dark' ? 'rgba(255,255,255,0.12)' : 'rgba(255,248,232,0.9)'))
const textMain = '#8B4513'

const iconSize = computed(() => {
  if (props.size === 'sm') return 26
  if (props.size === 'lg') return 42
  return 32
})

const sizeClass = computed(() => {
  if (props.size === 'sm') return 'logo-sm'
  if (props.size === 'lg') return 'logo-lg'
  return 'logo-md'
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap');

:host,
div {
  font-family: 'DM Sans', sans-serif;
}

.word-hotel {
  font-family: 'Playfair Display', serif;
  letter-spacing: 0.1px;
  font-weight: 700;
}

.word-ease {
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  margin-left: 1px;
}

.logo-sm .word-hotel,
.logo-sm .word-ease {
  font-size: 1rem;
}

.logo-md .word-hotel,
.logo-md .word-ease {
  font-size: 1.22rem;
}

.logo-lg .word-hotel,
.logo-lg .word-ease {
  font-size: 2rem;
}
</style>
