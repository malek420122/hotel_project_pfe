import { defineStore } from 'pinia'

export const useLocaleStore = defineStore('locale', {
  state: () => ({
    locale: 'fr',
    available: ['fr', 'en', 'ar'],
  }),
  actions: {
    setLocale(locale) {
      if (this.available.includes(locale)) {
        this.locale = locale
      }
    },
  },
})
