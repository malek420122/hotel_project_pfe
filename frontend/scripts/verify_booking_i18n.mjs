import { chromium } from '@playwright/test'

const baseURL = process.env.BASE_URL || 'http://localhost:5174'

const expected = {
  en: {
    dir: 'ltr',
    texts: ['Booking Summary', 'Room (1 night(s))', 'Services', 'Total', 'Confirm & Pay'],
  },
  fr: {
    dir: 'ltr',
    texts: ['Récapitulatif', 'Chambre (1 nuit(s))', 'Services', 'Total', 'Confirmer et payer'],
  },
  ar: {
    dir: 'rtl',
    texts: ['ملخص الحجز', 'غرفة (1 ليلة)', 'الخدمات', 'المجموع', 'تأكيد والدفع'],
  },
}

const browser = await chromium.launch({ headless: true })

const normalize = (value) => String(value)
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')

for (const locale of ['en', 'fr', 'ar']) {
  const context = await browser.newContext({ viewport: { width: 1400, height: 1000 } })
  const page = await context.newPage()

  await page.addInitScript((loc) => {
    localStorage.setItem('hotelease_locale', loc)
    localStorage.setItem('token', 'demo-token')
    localStorage.setItem('user', JSON.stringify({
      _id: '1',
      role: 'client',
      prenom: 'Demo',
      nom: 'User',
      email: 'demo@example.com',
    }))
  }, locale)

  await page.goto(`${baseURL}/dashboard/client/new-booking`, { waitUntil: 'domcontentloaded' })
  await page.waitForSelector('.card')

  await page.locator('input[type="text"]').first().fill('Paris')
  await page.locator('div.flex.justify-end.mt-6 button.btn-primary').click()
  await page.waitForSelector('div.card.space-y-4')
  await page.locator('div.flex.gap-3.justify-between.mt-4 button.btn-primary').click()
  await page.waitForSelector('div.grid.grid-cols-1.sm\\:grid-cols-2.gap-3')
  await page.locator('div.flex.gap-3.justify-between.mt-6 button.btn-primary').click()

  await page.waitForTimeout(500)

  const dir = await page.evaluate(() => document.documentElement.getAttribute('dir'))
  const bodyText = await page.locator('body').innerText()

  const normalizedBody = normalize(bodyText)
  const missing = expected[locale].texts.filter((txt) => !normalizedBody.includes(normalize(txt)))
  const dirOk = dir === expected[locale].dir

  if (!dirOk || missing.length) {
    console.error(`Locale ${locale.toUpperCase()} FAIL`)
    console.error(`dir=${dir}, expected=${expected[locale].dir}`)
    if (missing.length) console.error(`Missing: ${missing.join(' | ')}`)
    await context.close()
    await browser.close()
    process.exit(1)
  }

  console.log(`Locale ${locale.toUpperCase()} OK`) 
  await context.close()
}

await browser.close()
console.log('Booking i18n verification passed for EN/FR/AR')
