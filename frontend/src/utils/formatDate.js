const LOCALE_MAP = {
  ar: 'ar-SA-u-nu-latn',
  en: 'en-US',
  fr: 'fr-FR',
  ma: 'ar-MA',
}

function normalizeLanguage(language) {
  return String(language || 'fr').toLowerCase().split(/[-_]/)[0]
}

export function formatDate(dateStr, language, options = {}) {
  if (!dateStr) return ''

  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) return ''

  const localeCode = LOCALE_MAP[normalizeLanguage(language)] || 'fr-FR'
  return date.toLocaleDateString(localeCode, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    ...options,
  })
}
