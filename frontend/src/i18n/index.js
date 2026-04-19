import { createI18n } from 'vue-i18n'
import fr from './fr.json'
import en from './en.json'
import ar from './ar.json'

const STORAGE_KEY = 'hotelease_locale'
const LEGACY_STORAGE_KEY = 'locale'
const SUPPORTED_LOCALES = ['fr', 'en', 'ar']

function normalizeLocale(locale) {
  const code = String(locale || '').toLowerCase().split(/[-_]/)[0]
  return SUPPORTED_LOCALES.includes(code) ? code : 'fr'
}

export function applyLocale(locale) {
  const next = normalizeLocale(locale)
  document.documentElement.setAttribute('lang', next)
  document.documentElement.setAttribute('dir', next === 'ar' ? 'rtl' : 'ltr')
  document.documentElement.classList.toggle('locale-ar', next === 'ar')
  localStorage.setItem(STORAGE_KEY, next)
  localStorage.setItem(LEGACY_STORAGE_KEY, next)
  return next
}

export function changeLanguage(locale) {
  const next = applyLocale(locale)
  if (i18n.global?.locale) {
    i18n.global.locale.value = next
  }
  return next
}

const savedLocale = normalizeLocale(
  localStorage.getItem(STORAGE_KEY) || localStorage.getItem(LEGACY_STORAGE_KEY) || 'fr'
)
applyLocale(savedLocale)

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'fr',
  messages: { fr, en, ar }
})

export default i18n
