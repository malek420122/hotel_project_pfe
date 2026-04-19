import { chromium } from '@playwright/test'
import { mkdir } from 'node:fs/promises'

const baseURL = process.env.BASE_URL || 'http://localhost:5173'
const outputDir = 'test-results/dashboard-shots'

await mkdir(outputDir, { recursive: true })

async function setSession(page, role, prenom) {
  await page.addInitScript(({ role, prenom }) => {
    localStorage.setItem('token', 'dashboard-theme-demo-token')
    localStorage.setItem('user', JSON.stringify({
      _id: 'demo-user-id',
      role,
      prenom,
      nom: 'Demo',
      email: `${role}@demo.local`,
    }))
  }, { role, prenom })
}

function addDebugLogs(page, label) {
  page.on('pageerror', (error) => {
    console.error(`[${label}] pageerror:`, error.message)
  })
}

async function captureRoute(browser, {
  filePath,
  route,
  role,
  prenom,
  viewport,
}) {
  const context = await browser.newContext({ viewport })
  const page = await context.newPage()
  addDebugLogs(page, filePath)

  await setSession(page, role, prenom)
  await page.goto(`${baseURL}${route}`, { waitUntil: 'domcontentloaded' })
  await page.waitForSelector('.dashboard-shell', { timeout: 15000 })
  await page.waitForTimeout(1200)
  await page.screenshot({ path: `${outputDir}/${filePath}`, fullPage: true })

  await context.close()
}

const browser = await chromium.launch({ headless: true })

await captureRoute(browser, {
  filePath: '1-admin-overview.png',
  route: '/dashboard/admin/overview',
  role: 'admin',
  prenom: 'Admin',
  viewport: { width: 1600, height: 1000 },
})

await captureRoute(browser, {
  filePath: '2-receptionniste-reservations-table.png',
  route: '/dashboard/receptionniste/queue',
  role: 'receptionniste',
  prenom: 'Reception',
  viewport: { width: 1600, height: 1000 },
})

await captureRoute(browser, {
  filePath: '3-client-loyalty.png',
  route: '/dashboard/client/loyalty',
  role: 'client',
  prenom: 'Client',
  viewport: { width: 1600, height: 1000 },
})

await captureRoute(browser, {
  filePath: '4-marketing-reviews.png',
  route: '/dashboard/marketing/reviews',
  role: 'marketing',
  prenom: 'Marketing',
  viewport: { width: 1600, height: 1000 },
})

await captureRoute(browser, {
  filePath: '5-mobile-dashboard.png',
  route: '/dashboard/client/overview',
  role: 'client',
  prenom: 'Mobile',
  viewport: { width: 390, height: 844 },
})

await browser.close()
console.log(`Saved screenshots to ${outputDir}`)
