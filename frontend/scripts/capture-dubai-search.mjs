import { chromium } from '@playwright/test'

const baseUrl = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:5173'
const outPath = './test-results/dubai-search-fix.png'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage({ baseURL: baseUrl })

await page.addInitScript(() => localStorage.setItem('hotelease_locale', 'fr'))
await page.goto('/hotels?ville=Duba%C3%AF&nbVoyageurs=1', { waitUntil: 'networkidle', timeout: 120000 })

await page.locator('.results-count').waitFor({ state: 'visible', timeout: 20000 })
await page.setViewportSize({ width: 1600, height: 1200 })
await page.screenshot({ path: outPath, fullPage: true })

const countText = (await page.locator('.results-count').innerText()).trim()
const hotelCards = await page.locator('.premium-hotel-card').count()
console.log(`countText=${countText}`)
console.log(`visibleCards=${hotelCards}`)
console.log(`screenshot=${outPath}`)

await browser.close()
