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
      <path
        d="M14 52V24L32 13L50 24V52"
        :fill="iconMain"
        :fill-opacity="variant === 'dark' ? '0.18' : '0.1'"
        :stroke="iconMain"
        stroke-width="2"
        stroke-linejoin="round"
      />
      <rect x="21" y="29" width="6" height="6" rx="1" :fill="iconMain" :fill-opacity="0.92" />
      <rect x="37" y="29" width="6" height="6" rx="1" :fill="iconMain" :fill-opacity="0.92" />
      <path d="M28 52V44C28 41.8 29.8 40 32 40V40C34.2 40 36 41.8 36 44V52" :stroke="iconMain" stroke-width="2.5" stroke-linecap="round" />
      <path d="M26 52H38" :stroke="iconMain" stroke-width="2.5" stroke-linecap="round" />
      <path d="M32 8L33.8 11.8L38 12.4L35 15.3L35.7 19.5L32 17.6L28.3 19.5L29 15.3L26 12.4L30.2 11.8L32 8Z" :fill="accent" />
      <circle cx="48.5" cy="46.5" r="5.5" :stroke="accent" stroke-width="2" />
      <path d="M52.8 50.8L57 55" :stroke="accent" stroke-width="2" stroke-linecap="round" />
    </svg>

    <div class="leading-none">
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

const accent = '#f59e0b'
const iconMain = computed(() => (props.variant === 'dark' ? '#ffffff' : '#1a3a6b'))
const textMain = computed(() => (props.variant === 'dark' ? '#ffffff' : '#1a3a6b'))

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
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap');

.word-hotel {
  font-family: 'Playfair Display', serif;
  letter-spacing: 0.2px;
}

.word-ease {
  font-family: 'Inter', sans-serif;
  font-weight: 800;
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
  font-size: 1.95rem;
}
</style>
