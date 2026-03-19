<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

onMounted(() => {
  const savedLocale = localStorage.getItem('locale') || 'fr'
  locale.value = savedLocale
  document.documentElement.setAttribute('lang', savedLocale)
  document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr')
})
</script>
