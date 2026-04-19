export const serviceLabels = {
  wifi: { fr: 'Wi-Fi', en: 'Wi-Fi', ar: 'واي فاي' },
  restaurant: { fr: 'Restaurant', en: 'Restaurant', ar: 'مطعم' },
  piscine: { fr: 'Piscine', en: 'Pool', ar: 'مسبح' },
  spa: { fr: 'Spa', en: 'Spa', ar: 'سبا' },
  parking: { fr: 'Parking', en: 'Parking', ar: 'موقف سيارات' },
  climatisation: { fr: 'Climatisation', en: 'AC', ar: 'تكييف' },
  tv: { fr: 'TV', en: 'TV', ar: 'تلفزيون' },
  jacuzzi: { fr: 'Jacuzzi', en: 'Jacuzzi', ar: 'جاكوزي' },
  minibar: { fr: 'Minibar', en: 'Minibar', ar: 'ميني بار' },
}

export function getServiceLabel(service, lang) {
  const key = String(service || '').toLowerCase().trim()
  if (!key) return ''
  return serviceLabels[key]?.[lang] || String(service)
}
