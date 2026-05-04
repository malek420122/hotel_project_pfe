# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: receptionniste-dashboard-full.spec.js >> receptionniste dashboard full journey
- Location: tests\e2e\receptionniste-dashboard-full.spec.js:29:1

# Error details

```
Error: expect(received).toBeGreaterThan(expected)

Expected: > 0
Received:   0
```

# Test source

```ts
  1   | import { test, expect, request as playwrightRequest } from '@playwright/test'
  2   | 
  3   | function unwrapList(payload) {
  4   |   if (!payload) return []
  5   |   if (Array.isArray(payload)) return payload
  6   |   if (Array.isArray(payload.data)) return payload.data
  7   |   return []
  8   | }
  9   | 
  10  | function isoDatePlus(days) {
  11  |   const date = new Date()
  12  |   date.setHours(0, 0, 0, 0)
  13  |   date.setDate(date.getDate() + days)
  14  |   return date.toISOString().slice(0, 10)
  15  | }
  16  | 
  17  | async function loginWithCandidates(api, candidates) {
  18  |   for (const candidate of candidates) {
  19  |     const res = await api.post('/api/auth/login', { data: candidate })
  20  |     if (res.ok()) {
  21  |       const payload = await res.json()
  22  |       return { ...candidate, payload }
  23  |     }
  24  |   }
  25  | 
  26  |   throw new Error('No receptionist credentials worked. Tried reception@example.com and recep@hotelease.com')
  27  | }
  28  | 
  29  | test('receptionniste dashboard full journey', async ({ page }) => {
  30  |   const apiBase = 'http://127.0.0.1:8010'
  31  |   const api = await playwrightRequest.newContext({
  32  |     baseURL: apiBase,
  33  |     extraHTTPHeaders: {
  34  |       Accept: 'application/json',
  35  |       'Content-Type': 'application/json',
  36  |     },
  37  |   })
  38  | 
  39  |   const adminLogin = await api.post('/api/auth/login', {
  40  |     data: { email: 'admin@hotelease.com', password: 'Admin123!' },
  41  |   })
  42  |   expect(adminLogin.ok()).toBeTruthy()
  43  |   const adminPayload = await adminLogin.json()
  44  |   const adminToken = String(adminPayload?.token || '')
  45  |   expect(adminToken).toBeTruthy()
  46  | 
  47  |   const adminApi = await playwrightRequest.newContext({
  48  |     baseURL: apiBase,
  49  |     extraHTTPHeaders: {
  50  |       Accept: 'application/json',
  51  |       'Content-Type': 'application/json',
  52  |       Authorization: `Bearer ${adminToken}`,
  53  |     },
  54  |   })
  55  | 
  56  |   const stamp = Date.now()
  57  |   const clientEmail = `recep.flow.${stamp}@example.com`
  58  |   const clientPassword = 'Password123!'
  59  | 
  60  |   const clientRegister = await api.post('/api/auth/register', {
  61  |     data: {
  62  |       nom: 'Flow',
  63  |       prenom: 'Client',
  64  |       email: clientEmail,
  65  |       password: clientPassword,
  66  |       password_confirmation: clientPassword,
  67  |       telephone: '0600000010',
  68  |       langue: 'fr',
  69  |     },
  70  |   })
  71  |   expect(clientRegister.ok()).toBeTruthy()
  72  |   const clientRegisterPayload = await clientRegister.json()
  73  |   const clientToken = String(clientRegisterPayload?.token || '')
  74  |   expect(clientToken).toBeTruthy()
  75  | 
  76  |   const clientApi = await playwrightRequest.newContext({
  77  |     baseURL: apiBase,
  78  |     extraHTTPHeaders: {
  79  |       Accept: 'application/json',
  80  |       'Content-Type': 'application/json',
  81  |       Authorization: `Bearer ${clientToken}`,
  82  |     },
  83  |   })
  84  | 
  85  |   const hotelsResponse = await api.get('/api/hotels')
  86  |   expect(hotelsResponse.ok()).toBeTruthy()
  87  |   const hotels = unwrapList(await hotelsResponse.json())
  88  |   expect(hotels.length).toBeGreaterThan(0)
  89  | 
  90  |   const hotelId = String(hotels[0]._id || hotels[0].id)
  91  |   const today = isoDatePlus(0)
  92  |   const yesterday = isoDatePlus(-1)
  93  |   const tomorrow = isoDatePlus(1)
  94  | 
  95  |   const roomsForCheckinResponse = await api.get(`/api/hotels/${hotelId}/chambres?dateArrivee=${today}&dateDepart=${tomorrow}`)
  96  |   expect(roomsForCheckinResponse.ok()).toBeTruthy()
  97  |   const roomsForCheckin = unwrapList(await roomsForCheckinResponse.json())
> 98  |   expect(roomsForCheckin.length).toBeGreaterThan(0)
      |                                  ^ Error: expect(received).toBeGreaterThan(expected)
  99  |   const roomCheckin = String((roomsForCheckin[0]?._id || roomsForCheckin[0]?.id) ?? '')
  100 |   expect(roomCheckin).toBeTruthy()
  101 | 
  102 |   const reservationCheckinResponse = await clientApi.post('/api/reservations', {
  103 |     data: {
  104 |       hotelId,
  105 |       chambreId: roomCheckin,
  106 |       dateArrivee: today,
  107 |       dateDepart: tomorrow,
  108 |       nbVoyageurs: 1,
  109 |       demandesSpeciales: 'Arrivée matinale et oreiller supplémentaire',
  110 |       servicesChoisis: [],
  111 |       methodePaiement: 'carte',
  112 |     },
  113 |   })
  114 |   expect(reservationCheckinResponse.ok()).toBeTruthy()
  115 |   const reservationCheckin = await reservationCheckinResponse.json()
  116 | 
  117 |   const payCheckinResponse = await clientApi.post('/api/payments/process', {
  118 |     data: { reservationId: reservationCheckin._id, methode: 'carte' },
  119 |   })
  120 |   expect(payCheckinResponse.ok()).toBeTruthy()
  121 | 
  122 |   const roomsForCheckoutResponse = await api.get(`/api/hotels/${hotelId}/chambres?dateArrivee=${yesterday}&dateDepart=${tomorrow}`)
  123 |   expect(roomsForCheckoutResponse.ok()).toBeTruthy()
  124 |   const roomsForCheckout = unwrapList(await roomsForCheckoutResponse.json())
  125 |   expect(roomsForCheckout.length).toBeGreaterThan(0)
  126 |   const roomCheckout = String((roomsForCheckout[0]?._id || roomsForCheckout[0]?.id) ?? '')
  127 |   expect(roomCheckout).toBeTruthy()
  128 | 
  129 |   const reservationCheckoutResponse = await clientApi.post('/api/reservations', {
  130 |     data: {
  131 |       hotelId,
  132 |       chambreId: roomCheckout,
  133 |       dateArrivee: yesterday,
  134 |       dateDepart: tomorrow,
  135 |       nbVoyageurs: 1,
  136 |       demandesSpeciales: 'Check-out tardif',
  137 |       servicesChoisis: [],
  138 |       methodePaiement: 'carte',
  139 |     },
  140 |   })
  141 |   expect(reservationCheckoutResponse.ok()).toBeTruthy()
  142 |   const reservationCheckout = await reservationCheckoutResponse.json()
  143 | 
  144 |   const payCheckoutResponse = await clientApi.post('/api/payments/process', {
  145 |     data: { reservationId: reservationCheckout._id, methode: 'carte' },
  146 |   })
  147 |   expect(payCheckoutResponse.ok()).toBeTruthy()
  148 | 
  149 |   const checkinResponse = await adminApi.put(`/api/reservations/${reservationCheckout._id}/checkin`)
  150 |   expect(checkinResponse.ok()).toBeTruthy()
  151 | 
  152 |   await page.addInitScript(() => {
  153 |     localStorage.setItem('hotelease_locale', 'fr')
  154 |   })
  155 | 
  156 |   const receptionLogin = await loginWithCandidates(api, [
  157 |     { email: 'reception@example.com', password: 'Reception123!' },
  158 |     { email: 'recep@hotelease.com', password: 'Reception123!' },
  159 |     { email: 'recep@hotelease.com', password: 'Password1' },
  160 |   ])
  161 | 
  162 |   await page.goto('/login')
  163 |   await page.locator('input[type="email"]').fill(receptionLogin.email)
  164 |   await page.locator('input[type="password"]').fill(receptionLogin.password)
  165 |   await page.locator('button[type="submit"]').click()
  166 |   await page.waitForURL(/\/dashboard\/receptionniste\/(queue|checkin|checkout|rooms|special-requests)/)
  167 | 
  168 |   await page.goto('/dashboard/receptionniste/queue')
  169 |   await expect(page).toHaveURL(/\/dashboard\/receptionniste\/queue/)
  170 |   await expect(page.locator('h1.dashboard-title').first()).toHaveText(/File d'attente/i)
  171 |   await expect(page.getByText('File d\'attente en temps réel')).toBeVisible()
  172 | 
  173 |   await page.goto('/dashboard/receptionniste/checkin')
  174 |   await expect(page).toHaveURL(/\/dashboard\/receptionniste\/checkin/)
  175 |   await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Check-In/i)
  176 |   await expect(page.getByText('Check-ins du jour')).toBeVisible()
  177 |   await page.locator('input[placeholder*="Nom"]').fill(reservationCheckin._id)
  178 |   await page.getByRole('button', { name: /Rechercher/i }).click()
  179 |   await expect(page.getByText('Réservation trouvée')).toBeVisible()
  180 |   await expect(page.getByText(String(reservationCheckin._id)).first()).toBeVisible()
  181 | 
  182 |   await page.goto('/dashboard/receptionniste/checkout')
  183 |   await expect(page).toHaveURL(/\/dashboard\/receptionniste\/checkout/)
  184 |   await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Check-Out/i)
  185 |   await expect(page.getByText("Check-outs du jour").first()).toBeVisible()
  186 |   // Wait a bit for the data to load if needed
  187 |   await page.waitForTimeout(1000)
  188 |   await page.locator('input[placeholder*="Nom"]').fill(reservationCheckout._id)
  189 |   await page.getByRole('button', { name: /Rechercher/i }).click()
  190 |   await expect(page.getByRole('button', { name: /Checkout/i }).first()).toBeVisible()
  191 | 
  192 |   await page.goto('/dashboard/receptionniste/rooms')
  193 |   await expect(page).toHaveURL(/\/dashboard\/receptionniste\/rooms/)
  194 |   await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Grille chambres/i)
  195 |   await expect(page.getByText('Étage', { exact: true })).toBeVisible()
  196 | 
  197 |   await page.goto('/dashboard/receptionniste/special-requests')
  198 |   await expect(page).toHaveURL(/\/dashboard\/receptionniste\/special-requests/)
```