import { chromium } from 'playwright'

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

const apiResponse = await fetch('http://localhost:5173/api/hotels?page=1&per_page=50')
const apiJson = await apiResponse.json()
const apiHotels = Array.isArray(apiJson?.data) ? apiJson.data : (Array.isArray(apiJson) ? apiJson : [])
const apiNames = apiHotels.map((h) => h?.nom).filter(Boolean)

await page.goto('http://localhost:5173/hotels', { waitUntil: 'networkidle' })
const uiNames = await page.$$eval('.card p.font-bold', (nodes) =>
  nodes
    .map((n) => n.textContent?.trim())
    .filter((t) => t && !['Trier par:'].includes(t)),
)

const normalizedUi = [...new Set(uiNames)]
const missingInApi = normalizedUi.filter((name) => !apiNames.includes(name))

console.log(`API_COUNT=${apiNames.length}`)
console.log(`UI_COUNT=${normalizedUi.length}`)
console.log(`MISSING_IN_API=${missingInApi.length}`)
console.log(`API_NAMES=${JSON.stringify(apiNames)}`)
console.log(`UI_NAMES=${JSON.stringify(normalizedUi)}`)

await browser.close()
