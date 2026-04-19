import { chromium } from '@playwright/test'

const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:5173'

const candidates = [
  { email: 'reception@example.com', password: 'Reception123!' },
  { email: 'recep@hotelease.com', password: 'Reception123!' },
  { email: 'recep@hotelease.com', password: 'Password1' },
]

const tabs = [
  { path: '/dashboard/receptionniste/queue', expected: /File d'attente/i },
  { path: '/dashboard/receptionniste/checkin', expected: /Check-In/i },
  { path: '/dashboard/receptionniste/checkout', expected: /Check-Out/i },
  { path: '/dashboard/receptionniste/rooms', expected: /Grille chambres/i },
  { path: '/dashboard/receptionniste/special-requests', expected: /Demandes spéciales/i },
]

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage({ baseURL: baseUrl })
await page.addInitScript(() => localStorage.setItem('hotelease_locale', 'fr'))

let loggedIn = false
for (const cred of candidates) {
  await page.goto('/login')
  await page.locator('input[type="email"]').fill(cred.email)
  await page.locator('input[type="password"]').fill(cred.password)
  await page.getByRole('button', { name: /Connexion|Login/i }).click()
  try {
    await page.waitForURL(/\/dashboard\/receptionniste\//, { timeout: 7000 })
    console.log(`LOGIN OK: ${cred.email}`)
    loggedIn = true
    break
  } catch {
    // try next account
  }
}

if (!loggedIn) {
  console.log('LOGIN FAILED: no receptionist credentials worked')
  await browser.close()
  process.exit(1)
}

for (const tab of tabs) {
  await page.goto(tab.path)
  await page.waitForURL(new RegExp(tab.path.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')))
  const title = page.locator('h1.dashboard-title')
  await title.waitFor({ state: 'visible', timeout: 10000 })
  const text = (await title.innerText()).trim()
  const ok = tab.expected.test(text)
  console.log(`${ok ? 'OK' : 'FAIL'} ${tab.path} -> ${text}`)
}

await browser.close()
