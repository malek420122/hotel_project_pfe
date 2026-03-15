<template>
  <div class="relative">
    <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors text-sm font-medium text-gray-700">
      <span>{{ langs.find(l => l.code === locale)?.flag }}</span>
      <span class="hidden sm:inline">{{ langs.find(l => l.code === locale)?.label }}</span>
      <span class="text-gray-400">▾</span>
    </button>
    <div v-if="open" class="absolute right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1 min-w-32">
      <button v-for="lang in langs" :key="lang.code"
        @click="setLang(lang.code)"
        :class="['w-full text-left px-4 py-2 text-sm hover:bg-gray-50 flex items-center gap-2', locale === lang.code ? 'text-secondary font-semibold' : 'text-gray-700']">
        <span>{{ lang.flag }}</span>
        <span>{{ lang.label }}</span>
        <span v-if="locale === lang.code" class="ml-auto text-secondary">✓</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
const open = ref(false)
const langs = [
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'ar', label: 'العربية', flag: '🇲🇦' },
]
function setLang(code) {
  locale.value = code
  document.documentElement.setAttribute('lang', code)
  document.documentElement.setAttribute('dir', code === 'ar' ? 'rtl' : 'ltr')
  localStorage.setItem('locale', code)
  open.value = false
}
</script>
