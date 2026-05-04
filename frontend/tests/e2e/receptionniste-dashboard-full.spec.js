import { test, expect, request as playwrightRequest } from '@playwright/test'

function unwrapList(payload) {
  if (!payload) return []
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload.data)) return payload.data
  return []
}

function isoDatePlus(days) {
  const date = new Date()
  date.setHours(0, 0, 0, 0)
  date.setDate(date.getDate() + days)
  return date.toISOString().slice(0, 10)
}

async function loginWithCandidates(api, candidates) {
  for (const candidate of candidates) {
    const res = await api.post('/api/auth/login', { data: candidate })
    if (res.ok()) {
      const payload = await res.json()
      return { ...candidate, payload }
    }
  }

  throw new Error('No receptionist credentials worked. Tried reception@example.com and recep@hotelease.com')
}

test('receptionniste dashboard full journey', async ({ page }) => {
  const apiBase = 'http://127.0.0.1:8010'
  const api = await playwrightRequest.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  })

  const adminLogin = await api.post('/api/auth/login', {
    data: { email: 'admin@hotelease.com', password: 'Admin123!' },
  })
  expect(adminLogin.ok()).toBeTruthy()
  const adminPayload = await adminLogin.json()
  const adminToken = String(adminPayload?.token || '')
  expect(adminToken).toBeTruthy()

  const adminApi = await playwrightRequest.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${adminToken}`,
    },
  })

  const stamp = Date.now()
  const clientEmail = `recep.flow.${stamp}@example.com`
  const clientPassword = 'Password123!'

  const clientRegister = await api.post('/api/auth/register', {
    data: {
      nom: 'Flow',
      prenom: 'Client',
      email: clientEmail,
      password: clientPassword,
      password_confirmation: clientPassword,
      telephone: '0600000010',
      langue: 'fr',
    },
  })
  expect(clientRegister.ok()).toBeTruthy()
  const clientRegisterPayload = await clientRegister.json()
  const clientToken = String(clientRegisterPayload?.token || '')
  expect(clientToken).toBeTruthy()

  const clientApi = await playwrightRequest.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${clientToken}`,
    },
  })

  const hotelsResponse = await api.get('/api/hotels')
  expect(hotelsResponse.ok()).toBeTruthy()
  const hotels = unwrapList(await hotelsResponse.json())
  expect(hotels.length).toBeGreaterThan(0)

  const hotelId = String(hotels[0]._id || hotels[0].id)
  const today = isoDatePlus(0)
  const yesterday = isoDatePlus(-1)
  const tomorrow = isoDatePlus(1)

  const roomsForCheckinResponse = await api.get(`/api/hotels/${hotelId}/chambres?dateArrivee=${today}&dateDepart=${tomorrow}`)
  expect(roomsForCheckinResponse.ok()).toBeTruthy()
  const roomsForCheckin = unwrapList(await roomsForCheckinResponse.json())
  expect(roomsForCheckin.length).toBeGreaterThan(0)
  const roomCheckin = String((roomsForCheckin[0]?._id || roomsForCheckin[0]?.id) ?? '')
  expect(roomCheckin).toBeTruthy()

  const reservationCheckinResponse = await clientApi.post('/api/reservations', {
    data: {
      hotelId,
      chambreId: roomCheckin,
      dateArrivee: today,
      dateDepart: tomorrow,
      nbVoyageurs: 1,
      demandesSpeciales: 'Arrivée matinale et oreiller supplémentaire',
      servicesChoisis: [],
      methodePaiement: 'carte',
    },
  })
  expect(reservationCheckinResponse.ok()).toBeTruthy()
  const reservationCheckin = await reservationCheckinResponse.json()

  const payCheckinResponse = await clientApi.post('/api/payments/process', {
    data: { reservationId: reservationCheckin._id, methode: 'carte' },
  })
  expect(payCheckinResponse.ok()).toBeTruthy()

  const roomsForCheckoutResponse = await api.get(`/api/hotels/${hotelId}/chambres?dateArrivee=${yesterday}&dateDepart=${tomorrow}`)
  expect(roomsForCheckoutResponse.ok()).toBeTruthy()
  const roomsForCheckout = unwrapList(await roomsForCheckoutResponse.json())
  expect(roomsForCheckout.length).toBeGreaterThan(0)
  const roomCheckout = String((roomsForCheckout[0]?._id || roomsForCheckout[0]?.id) ?? '')
  expect(roomCheckout).toBeTruthy()

  const reservationCheckoutResponse = await clientApi.post('/api/reservations', {
    data: {
      hotelId,
      chambreId: roomCheckout,
      dateArrivee: yesterday,
      dateDepart: tomorrow,
      nbVoyageurs: 1,
      demandesSpeciales: 'Check-out tardif',
      servicesChoisis: [],
      methodePaiement: 'carte',
    },
  })
  expect(reservationCheckoutResponse.ok()).toBeTruthy()
  const reservationCheckout = await reservationCheckoutResponse.json()

  const payCheckoutResponse = await clientApi.post('/api/payments/process', {
    data: { reservationId: reservationCheckout._id, methode: 'carte' },
  })
  expect(payCheckoutResponse.ok()).toBeTruthy()

  const checkinResponse = await adminApi.put(`/api/reservations/${reservationCheckout._id}/checkin`)
  expect(checkinResponse.ok()).toBeTruthy()

  await page.addInitScript(() => {
    localStorage.setItem('hotelease_locale', 'fr')
  })

  const receptionLogin = await loginWithCandidates(api, [
    { email: 'reception@example.com', password: 'Reception123!' },
    { email: 'recep@hotelease.com', password: 'Reception123!' },
    { email: 'recep@hotelease.com', password: 'Password1' },
  ])

  await page.goto('/login')
  await page.locator('input[type="email"]').fill(receptionLogin.email)
  await page.locator('input[type="password"]').fill(receptionLogin.password)
  await page.locator('button[type="submit"]').click()
  await page.waitForURL(/\/dashboard\/receptionniste\/(queue|checkin|checkout|rooms|special-requests)/)

  await page.goto('/dashboard/receptionniste/queue')
  await expect(page).toHaveURL(/\/dashboard\/receptionniste\/queue/)
  await expect(page.locator('h1.dashboard-title').first()).toHaveText(/File d'attente/i)
  await expect(page.getByText('File d\'attente en temps réel')).toBeVisible()

  await page.goto('/dashboard/receptionniste/checkin')
  await expect(page).toHaveURL(/\/dashboard\/receptionniste\/checkin/)
  await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Check-In/i)
  await expect(page.getByText('Check-ins du jour')).toBeVisible()
  await page.locator('input[placeholder*="Nom"]').fill(reservationCheckin._id)
  await page.getByRole('button', { name: /Rechercher/i }).click()
  await expect(page.getByText('Réservation trouvée')).toBeVisible()
  await expect(page.getByText(String(reservationCheckin._id)).first()).toBeVisible()

  await page.goto('/dashboard/receptionniste/checkout')
  await expect(page).toHaveURL(/\/dashboard\/receptionniste\/checkout/)
  await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Check-Out/i)
  await expect(page.getByText("Check-outs du jour").first()).toBeVisible()
  // Wait a bit for the data to load if needed
  await page.waitForTimeout(1000)
  await page.locator('input[placeholder*="Nom"]').fill(reservationCheckout._id)
  await page.getByRole('button', { name: /Rechercher/i }).click()
  await expect(page.getByRole('button', { name: /Checkout/i }).first()).toBeVisible()

  await page.goto('/dashboard/receptionniste/rooms')
  await expect(page).toHaveURL(/\/dashboard\/receptionniste\/rooms/)
  await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Grille chambres/i)
  await expect(page.getByText('Étage', { exact: true })).toBeVisible()

  await page.goto('/dashboard/receptionniste/special-requests')
  await expect(page).toHaveURL(/\/dashboard\/receptionniste\/special-requests/)
  await expect(page.locator('h1.dashboard-title').first()).toHaveText(/Demandes spéciales/i)
  await expect(page.getByRole('button', { name: 'En attente' })).toBeVisible()
  const requestCards = page.locator('.card.border-l-4')
  const cardCount = await requestCards.count()
  if (cardCount > 0) {
    await expect(requestCards.first()).toBeVisible()
  } else {
    await expect(page.getByText('Aucune demande en attente')).toBeVisible()
  }

  await page.getByRole('button', { name: /Déconnexion/i }).first().click()
  await expect(page).toHaveURL(/\/login/)
})