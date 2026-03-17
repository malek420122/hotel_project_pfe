# hotel_project_pfe

Application web pour la réservation d'hôtels avec backend Laravel (MongoDB) et frontend Vue 3.

## Fonctionnalités principales
- Inscription et connexion des clients.
- Réservation de chambres en ligne avec paiement sécurisé (mode sandbox).
- Consultation des services (spa, restaurant, activités).
- Tableau de bord d'administration pour les réservations et revenus.
- Interface responsive et multilingue (FR/EN/AR).

## Démarrage rapide

### Backend (Laravel)
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan serve
```

> MongoDB doit être accessible selon les variables définies dans `.env`.

### Frontend (Vue 3)
```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

### API principale
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/rooms`
- `GET /api/services`
- `POST /api/bookings`
- `PATCH /api/bookings/{booking}`
- `PATCH /api/bookings/{booking}/cancel`
- `POST /api/payments`
- `GET /api/admin/dashboard`
