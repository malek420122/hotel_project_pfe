import { createI18n } from 'vue-i18n'
import fr from './fr.json'
import en from './en.json'
import ar from './ar.json'

const savedLocale = localStorage.getItem('locale') || 'fr'

document.documentElement.setAttribute('lang', savedLocale)
document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr')

export default createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'fr',
  messages: { fr, en, ar }
})
