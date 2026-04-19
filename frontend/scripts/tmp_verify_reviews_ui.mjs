import { chromium } from 'playwright'

const baseApi = 'http://127.0.0.1:8010/api'
const baseUi = process.env.BASE_URL || 'http://localhost:5174'

const loginResp = await fetch(`${baseApi}/auth/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  body: JSON.stringify({ email: 'mimi@gmail.com', password: 'Mimi1234' }),
})

if (!loginResp.ok) {
  throw new Error(`Login failed: ${loginResp.status}`)
}

const loginJson = await loginResp.json()
const token = loginJson.token
if (!token) throw new Error('No token in login response')

const comments = [
  'Séjour absolument fantastique! Le service était impeccable et la vue sur Dubai était à couper le souffle.',
  'Très belle expérience globale, personnel accueillant et chambre confortable.',
  'Séjour correct avec quelques points à améliorer, mais bon rapport qualité-prix.',
]

const hotels = [
  '69d181434f4b26aa2e0f5bc3',
  '69d19b569ac74c940909fdbc',
  '69d19b569ac74c940909fdba',
]

const browser = await chromium.launch({ headless: true })
const page = await browser.newPage()

await page.addInitScript((t) => {
  localStorage.setItem('token', t)
  localStorage.setItem('user', JSON.stringify({
    _id: '69d18c897cdd929db808b2cc',
    role: 'client',
    prenom: 'Mimi',
    nom: 'Test',
    email: 'mimi@gmail.com',
  }))
}, token)

await page.goto(`${baseUi}/dashboard/client/reviews`, { waitUntil: 'networkidle', timeout: 60000 })
const dashboardText = await page.locator('body').innerText()
let dashboardMatches = 0
for (const c of comments) {
  if (dashboardText.includes(c)) dashboardMatches += 1
}
console.log(`DASHBOARD_REVIEWS_MATCH=${dashboardMatches}/3`)

for (let i = 0; i < hotels.length; i += 1) {
  await page.goto(`${baseUi}/hotels/${hotels[i]}`, { waitUntil: 'networkidle', timeout: 60000 })
  const txt = await page.locator('body').innerText()
  console.log(`HOTEL_${i + 1}_HAS_REVIEW=${txt.includes(comments[i])}`)
}

await browser.close()
