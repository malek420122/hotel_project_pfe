<template>
  <div ref="rootRef" class="relative">
    <button @click="open = !open" :class="buttonClass" type="button" aria-haspopup="listbox" :aria-expanded="open ? 'true' : 'false'">
      <span>{{ currentLang?.flag }}</span>
      <span>{{ currentLang?.code.toUpperCase() }}</span>
      <span :class="['lang-arrow', open && 'lang-arrow-open', arrowClass]">▾</span>
    </button>
    <transition name="lang-switcher-drop">
      <div v-if="open" :class="dropdownClass" role="listbox">
      <button v-for="lang in langs" :key="lang.code"
        @click="setLang(lang.code)"
        :class="itemClass(lang.code)"
        type="button"
      >
        <span>{{ lang.flag }}</span>
        <span>{{ lang.label }}</span>
        <span v-if="locale === lang.code" class="ml-auto text-secondary">✓</span>
      </button>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { changeLanguage } from '../i18n'

const props = defineProps({
  variant: {
    type: String,
    default: 'light',
    validator: (value) => ['light', 'dark'].includes(value),
  },
})

const { locale } = useI18n()
const open = ref(false)
const rootRef = ref(null)
const langs = [
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'ar', label: 'العربية', flag: '🇲🇦' },
]

const currentLang = computed(() => langs.find(l => l.code === locale.value) || langs[0])

const buttonClass = computed(() => {
  if (props.variant === 'dark') {
    return 'flex items-center gap-1.5 px-[10px] py-[6px] rounded-lg border border-white/25 bg-transparent hover:bg-white/10 transition-colors text-[13px] font-medium text-white'
  }

  return 'flex items-center gap-1.5 px-[10px] py-[6px] rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors text-[13px] font-medium text-gray-700'
})

const arrowClass = computed(() => (props.variant === 'dark' ? 'text-white/90' : 'text-gray-400'))

const dropdownClass = computed(() => {
  if (props.variant === 'dark') {
    return 'absolute right-0 mt-2 bg-[#1e293b] border border-white/10 rounded-xl shadow-2xl z-50 py-1 min-w-36'
  }
  return 'absolute right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1 min-w-36'
})

function itemClass(code) {
  const selected = locale.value === code
  if (props.variant === 'dark') {
    return [
      'w-full text-left px-4 py-2 text-sm hover:bg-amber-500/20 flex items-center gap-2 transition-colors',
      selected ? 'bg-amber-500/20 text-amber-300 font-semibold' : 'text-white',
    ]
  }
  return [
    'w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2 transition-colors',
    selected ? 'bg-blue-50 text-secondary font-semibold' : 'text-gray-700',
  ]
}

function setLang(code) {
  const nextLanguage = changeLanguage(code)
  locale.value = nextLanguage
  console.log('i18n.language', nextLanguage)
  open.value = false
}

function onDocumentClick(event) {
  if (!rootRef.value) return
  if (!rootRef.value.contains(event.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})
</script>

<style scoped>
.lang-switcher-drop-enter-active,
.lang-switcher-drop-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.lang-switcher-drop-enter-from,
.lang-switcher-drop-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.lang-switcher-drop-enter-to,
.lang-switcher-drop-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.lang-arrow {
  transform: rotate(0deg);
  transition: transform 0.2s ease;
}

.lang-arrow-open {
  transform: rotate(180deg);
}
</style>
