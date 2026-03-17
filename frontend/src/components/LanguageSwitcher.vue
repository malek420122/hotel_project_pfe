<template>
  <label class="language-switcher">
    <span class="sr-only">{{ $t('nav.language') }}</span>
    <select v-model="currentLocale" @change="updateLocale">
      <option v-for="locale in locales" :key="locale" :value="locale">
        {{ locale.toUpperCase() }}
      </option>
    </select>
  </label>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocaleStore } from '../stores/localeStore'
import { api } from '../services/api'

const localeStore = useLocaleStore()
const { locale } = useI18n()

const locales = computed(() => localeStore.available)
const currentLocale = computed({
  get: () => localeStore.locale,
  set: (value) => localeStore.setLocale(value),
})

const updateLocale = () => {
  locale.value = localeStore.locale
  document.documentElement.lang = localeStore.locale
  document.documentElement.dir = localeStore.locale === 'ar' ? 'rtl' : 'ltr'
  api.defaults.headers.common['Accept-Language'] = localeStore.locale
}

watch(
  () => localeStore.locale,
  () => updateLocale(),
  { immediate: true },
)
</script>

<style scoped>
.language-switcher select {
  padding: 0.3rem 0.6rem;
  border-radius: 0.5rem;
  border: 1px solid #d7d7e0;
  background: #fff;
  font-weight: 600;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>
