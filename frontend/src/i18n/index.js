import { createI18n } from 'vue-i18n'

import en from './en.json'
import fr from './fr.json'
import ar from './ar.json'

const messages = { en, fr, ar }
const defaultLocale = 'fr'

export const i18n = createI18n({
  legacy: false,
  locale: defaultLocale,
  fallbackLocale: 'en',
  messages,
})
