import { test, expect } from '@playwright/test';

function unwrapList(payload) {
  if (!payload) return [];
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload.data)) return payload.data;
  return [];
}

test('home -> register -> login -> reservation -> dashboard', async ({ page, request }) => {
  const stamp = Date.now();
  const email = `e2e.${stamp}@example.com`;
  const password = 'Password123!';
  const apiBase = 'http://127.0.0.1:8010/api';
  const apiTimeout = 30_000;

  const preflight = await request.get(`${apiBase}/hotels`, { timeout: apiTimeout });
  expect(preflight.ok()).toBeTruthy();

  const registerRes = await request.post(`${apiBase}/auth/register`, {
    timeout: apiTimeout,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    data: {
      nom: 'User',
      prenom: 'E2E',
      email,
      password,
      password_confirmation: password,
      telephone: '0612345678',
      role: 'client',
    },
  });
  if (!registerRes.ok()) {
    throw new Error(`Register failed (${registerRes.status()}): ${await registerRes.text()}`);
  }

  await page.goto('/');
  await expect(page).toHaveURL(/\/$/);
  await expect(page.getByText('HotelEase').first()).toBeVisible();

  const loginRes = await request.post(`${apiBase}/auth/login`, {
    timeout: apiTimeout,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    data: {
      email,
      password,
    },
  });
  if (!loginRes.ok()) {
    throw new Error(`Login failed (${loginRes.status()}): ${await loginRes.text()}`);
  }

  const loginPayload = await loginRes.json();
  const token = loginPayload?.token;
  expect(token).toBeTruthy();

  await page.goto('/');
  await page.evaluate(({ user, authToken }) => {
    localStorage.setItem('token', authToken);
    localStorage.setItem('user', JSON.stringify(user));
  }, { user: loginPayload?.user ?? null, authToken: token });

  await page.goto('/dashboard/client/reservations');
  await expect(page).toHaveURL(/\/dashboard\/client\/reservations/);

  const hotelsResponse = await request.get(`${apiBase}/hotels`, { timeout: apiTimeout });
  expect(hotelsResponse.ok()).toBeTruthy();
  const hotels = unwrapList(await hotelsResponse.json());

  let reservationCreated = false;

  if (hotels.length > 0) {
    const hotelId = hotels[0]._id || hotels[0].id;
    if (hotelId) {
      const roomsResponse = await request.get(`${apiBase}/hotels/${hotelId}/chambres`, { timeout: apiTimeout });
      if (roomsResponse.ok()) {
        const rooms = unwrapList(await roomsResponse.json());
        if (rooms.length > 0) {
          const roomId = rooms[0]._id || rooms[0].id;
          const dateArrivee = new Date(Date.now() + 86400000 * 2).toISOString().slice(0, 10);
          const dateDepart = new Date(Date.now() + 86400000 * 4).toISOString().slice(0, 10);

          const reservationResponse = await request.post(`${apiBase}/reservations`, {
            timeout: apiTimeout,
            headers: {
              Authorization: `Bearer ${token}`,
              'Content-Type': 'application/json',
            },
            data: {
              hotelId,
              chambreId: roomId,
              dateArrivee,
              dateDepart,
              nbVoyageurs: 1,
            },
          });

          reservationCreated = reservationResponse.ok();
        }
      }
    }
  }

  await expect(page.getByRole('heading', { level: 2 })).toBeVisible();

  if (reservationCreated) {
    const rows = page.locator('tbody tr');
    await expect(rows.first()).toBeVisible();
  }
});
