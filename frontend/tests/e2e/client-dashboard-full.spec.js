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

function pickRoom(rooms) {
  return rooms.find((room) => Number(room.maxVoyageurs || room.capacite || 1) >= 2) || rooms[0]
}

test('client dashboard full journey', async ({ page }) => {
  const apiBase = 'http://127.0.0.1:8010'
  const api = await playwrightRequest.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  })

  const stamp = Date.now()
  const email = `client.${stamp}@example.com`
  const password = 'Password123!'
  const updatedPassword = 'Password456!'

  await api.post('/api/auth/register', {
    data: {
      nom: 'Client',
      prenom: 'E2E',
      email,
      password,
      password_confirmation: password,
      telephone: '0600000009',
      role: 'client',
    },
  })

  const hotelsResponse = await api.get('/api/hotels')
  expect(hotelsResponse.ok()).toBeTruthy()
  const hotels = unwrapList(await hotelsResponse.json())
  expect(hotels.length).toBeGreaterThan(0)

  const hotelId = String(hotels[0]._id || hotels[0].id)
  const futureArrival = isoDatePlus(365)
  const futureDeparture = isoDatePlus(368)
  const todayArrival = isoDatePlus(0)
  const tomorrowDeparture = isoDatePlus(1)
  const pastArrival = isoDatePlus(-3)
  const pastDeparture = isoDatePlus(-2)

  const roomsResponse = await api.get(`/api/hotels/${hotelId}/chambres?dateArrivee=${futureArrival}&dateDepart=${futureDeparture}`)
  expect(roomsResponse.ok()).toBeTruthy()
  const rooms = unwrapList(await roomsResponse.json())
  expect(rooms.length).toBeGreaterThan(0)

  const room = pickRoom(rooms)
  const roomId = String(room._id || room.id)

  await page.addInitScript(() => {
    localStorage.setItem('hotelease_locale', 'fr')
  })

  await page.goto('/login')
  await page.locator('input[type="email"]').fill(email)
  await page.locator('input[type="password"]').fill(password)
  await page.getByRole('button', { name: /Connexion|Login/i }).click()
  await page.waitForURL(/\/hotels|\/dashboard\/client/)

  await page.goto('/dashboard/client/overview')
  await expect(page).toHaveURL(/\/dashboard\/client\/overview/)

  const clientToken = await page.evaluate(() => localStorage.getItem('token') || '')
  expect(clientToken).toBeTruthy()

  const clientApi = await playwrightRequest.newContext({
    baseURL: apiBase,
    extraHTTPHeaders: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${clientToken}`,
    },
  })

  const futureCreate = await clientApi.post('/api/reservations', {
    data: {
      hotelId,
      chambreId: roomId,
      dateArrivee: futureArrival,
      dateDepart: futureDeparture,
      nbVoyageurs: 2,
      demandesSpeciales: 'Oreiller supplémentaire',
      servicesChoisis: [],
      methodePaiement: 'carte',
    },
  })
  expect(futureCreate.ok()).toBeTruthy()
  const futureCreateData = await futureCreate.json()

  await clientApi.post('/api/payments/process', {
    data: { reservationId: futureCreateData._id, methode: 'carte' },
  })

  await page.goto(`/dashboard/client/new-booking?hotelId=${hotelId}&roomId=${roomId}&dateArrivee=${futureArrival}&dateDepart=${futureDeparture}&nbVoyageurs=2`)
  await expect(page.getByText('Demandes spéciales')).toBeVisible()
  await page.locator('textarea').fill('Oreiller supplémentaire')

  const futureListResponse = await clientApi.get('/api/client/reservations')
  expect(futureListResponse.ok()).toBeTruthy()
  const futureList = unwrapList(await futureListResponse.json())
  const futureReservation = futureList.find((item) => item.demandesSpeciales === 'Oreiller supplémentaire' && String(item.dateArrivee || '').slice(0, 10) === futureArrival)
  expect(futureReservation).toBeTruthy()

  const ongoingCreate = await clientApi.post('/api/reservations', {
    data: {
      hotelId,
      chambreId: roomId,
      dateArrivee: todayArrival,
      dateDepart: tomorrowDeparture,
      nbVoyageurs: 1,
      demandesSpeciales: 'Arrivée anticipée',
      servicesChoisis: [],
      methodePaiement: 'carte',
    },
  })
  expect(ongoingCreate.ok()).toBeTruthy()
  const ongoingReservation = await ongoingCreate.json()

  const pastCreate = await clientApi.post('/api/reservations', {
    data: {
      hotelId,
      chambreId: roomId,
      dateArrivee: pastArrival,
      dateDepart: pastDeparture,
      nbVoyageurs: 1,
      demandesSpeciales: 'Séjour terminé',
      servicesChoisis: [],
      methodePaiement: 'carte',
    },
  })
  expect(pastCreate.ok()).toBeTruthy()
  const pastReservation = await pastCreate.json()

  await clientApi.post('/api/payments/process', {
    data: { reservationId: ongoingReservation._id, methode: 'carte' },
  })
  await clientApi.post('/api/payments/process', {
    data: { reservationId: pastReservation._id, methode: 'carte' },
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

  await adminApi.put(`/api/reservations/${ongoingReservation._id}/checkin`)
  await adminApi.put(`/api/reservations/${pastReservation._id}/checkin`)
  await adminApi.put(`/api/reservations/${pastReservation._id}/checkout`)

  const finalListResponse = await clientApi.get('/api/client/reservations')
  expect(finalListResponse.ok()).toBeTruthy()
  const finalList = unwrapList(await finalListResponse.json())
  const futureRowData = finalList.find((item) => item._id === futureReservation._id)
  const ongoingRowData = finalList.find((item) => item._id === ongoingReservation._id)
  const pastRowData = finalList.find((item) => item._id === pastReservation._id)

  expect(futureRowData).toBeTruthy()
  expect(ongoingRowData?.statut).toBe('EN_COURS')
  expect(pastRowData?.statut).toBe('TERMINEE')

  await page.goto('/dashboard/client/reservations')
  await expect(page).toHaveURL(/\/dashboard\/client\/reservations/)
  await expect(page.getByText(String(futureReservation.reference))).toBeVisible()
  await expect(page.getByText(String(ongoingReservation.reference))).toBeVisible()
  await expect(page.getByText(String(pastReservation.reference))).toBeVisible()

  await page.getByRole('button', { name: 'À venir' }).click()
  await expect(page.getByText(String(futureReservation.reference))).toBeVisible()
  await expect(page.getByText(String(ongoingReservation.reference))).not.toBeVisible()

  await page.getByRole('button', { name: 'En cours' }).click()
  await expect(page.getByText(String(ongoingReservation.reference))).toBeVisible()

  await page.getByRole('button', { name: 'Passées' }).click()
  await expect(page.getByText(String(pastReservation.reference))).toBeVisible()

  await page.getByRole('button', { name: 'Toutes' }).click()
  const futureRow = page.locator('tbody tr').filter({ hasText: String(futureReservation.reference) }).first()
  await expect(futureRow).toBeVisible()

  const invoiceResponse = await clientApi.get(`/api/reservations/${futureReservation._id}/invoice`)
  expect(invoiceResponse.ok()).toBeTruthy()
  expect(String(invoiceResponse.headers()['content-type'] || '')).toContain('pdf')
  await futureRow.getByRole('button', { name: /Facture/ }).click()

  await futureRow.getByRole('button', { name: /Modifier/ }).click()
  await expect(page.getByText('Modifier la réservation')).toBeVisible()
  const modifyInputs = page.locator('.modify-modal input[type="date"]')
  await modifyInputs.nth(0).fill(isoDatePlus(366))
  await modifyInputs.nth(1).fill(isoDatePlus(369))
  await page.getByRole('button', { name: /Enregistrer|Save/i }).click()
  await expect(page.getByText('Réservation modifiée avec succès.')).toBeVisible()

  await futureRow.getByRole('button', { name: /Annuler/ }).click()
  await expect(page.getByText('Annuler la réservation')).toBeVisible()
  await page.getByRole('button', { name: /Confirmer l'annulation/ }).click()
  await expect(page.getByText('Réservation annulée avec succès.')).toBeVisible()

  await page.goto('/dashboard/client/profile')
  await expect(page.locator('h1.dashboard-title')).toHaveText(/Mon profil/i)
  const profileInputs = page.locator('.profile-card form input.input-field')
  await profileInputs.nth(0).fill('ClientE2E')
  await profileInputs.nth(1).fill('Test')
  await profileInputs.nth(3).fill('+33111111111')
  await page.getByRole('button', { name: /Enregistrer les modifications/ }).click()
  await expect(page.getByText('Profil mis à jour !')).toBeVisible()

  await page.getByPlaceholder('Requis pour changer le mot de passe').fill(password)
  await page.getByPlaceholder('Laisser vide pour ne pas changer').fill(updatedPassword)
  await page.getByPlaceholder('Confirmez le mot de passe').fill(updatedPassword)
  await page.getByRole('button', { name: /Enregistrer les modifications/ }).click()
  await expect(page.getByText('Profil mis à jour !')).toBeVisible()

  await page.getByRole('button', { name: /Déconnexion/ }).click()
  await expect(page).toHaveURL(/\/login/)

  await page.locator('input[type="email"]').fill(email)
  await page.locator('input[type="password"]').fill(updatedPassword)
  await page.getByRole('button', { name: /Connexion|Login/i }).click()
  await page.waitForURL(/\/hotels|\/dashboard\/client/)

  await page.goto('/dashboard/client/overview')
  await expect(page).toHaveURL(/\/dashboard\/client\/overview/)
})