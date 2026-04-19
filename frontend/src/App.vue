<template>
  <RouterView />
  <ChatbotWidget v-if="showClientChatbot" />
</template>

<script setup>
import { computed, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { applyLocale } from './i18n'
import { useAuthStore } from './stores/auth'
import ChatbotWidget from './components/ChatbotWidget.vue'

const { locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const showClientChatbot = computed(() => {
  if (auth.user?.role !== 'client') return false

  const path = String(route.path || '')
  if (path === '/login' || path === '/register') return false

  return path.startsWith('/dashboard/client') || path.startsWith('/hotels')
})

watchEffect(() => {
  const normalized = applyLocale(locale.value)
  if (locale.value !== normalized) {
    locale.value = normalized
  }
})
</script>
