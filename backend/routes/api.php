<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MarketingEmailController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ClientSignalController;
use App\Http\Controllers\Api\ChatbotController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt.auth');
});

// Public routes
Route::get('hotels', [HotelController::class, 'index']);
Route::get('stats', [HotelController::class, 'homepageStats']);
Route::get('hotels/homepage-stats', [HotelController::class, 'homepageStats']);
Route::get('hotels/cities', [HotelController::class, 'cities']);
Route::get('hotels/suggestions', [HotelController::class, 'suggestions']);
Route::get('recommendations', [RecommendationController::class, 'index'])->middleware('jwt.auth');
Route::post('record-activity', [RecommendationController::class, 'recordActivity'])->middleware('jwt.auth');
Route::get('hotels/price-range', [HotelController::class, 'priceRange']);
Route::get('hotels/{id}', [HotelController::class, 'show']);
Route::get('hotels/{id}/rooms', [HotelController::class, 'chambresDisponibles']);
Route::get('hotels/{id}/chambres', [HotelController::class, 'chambresDisponibles']);
Route::get('hotels/{id}/reviews', [HotelController::class, 'avis']);
Route::get('hotels/{id}/avis', [HotelController::class, 'avis']);
Route::get('promotions', [PromotionController::class, 'index']);
Route::post('promotions/validate', [PromotionController::class, 'validate']);
Route::post('payments/webhook/stripe', [PaymentController::class, 'stripeWebhook'])->middleware('stripe.webhook');

// Notifications (shared by all authenticated dashboard roles)
Route::middleware('jwt.auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Incidents are structured tickets created by reception/admin/manager only.
    Route::middleware('role:receptionniste,admin,manager')->group(function () {
        Route::get('rooms', [ChambreController::class, 'index']);
        Route::post('incidents', [IncidentController::class, 'store']);
    });

    // Incidents management (admin + manager)
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('incidents', [IncidentController::class, 'index']);
        Route::patch('incidents/{id}', [IncidentController::class, 'updateStatus']);
        Route::get('incidents/stats', [IncidentController::class, 'stats']);
    });
});

// Client routes (JWT + role:client or admin)
Route::middleware(['jwt.auth', 'role:client,admin'])->group(function () {
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::get('client/reservations', [ReservationController::class, 'myReservations']);
    Route::put('reservations/{id}/reschedule', [ReservationController::class, 'reschedule']);
    Route::get('client/loyalty', [UserController::class, 'fidelite']);
    Route::post('client/loyalty/redeem', [UserController::class, 'redeemPoints']);
    Route::delete('reservations/{id}', [ReservationController::class, 'cancel']);
    Route::get('reservations/{id}/invoice', [ReservationController::class, 'invoice']);
    Route::post('avis', [AvisController::class, 'store']);
    Route::post('reviews', [AvisController::class, 'store']);
    Route::get('client/reviews', [AvisController::class, 'myReviews']);
    Route::get('client/reviews/reviewable', [AvisController::class, 'reviewable']);
    Route::post('client/reviews', [AvisController::class, 'store']);
    Route::get('client/stats', [StatistiqueController::class, 'clientStats']);
    Route::put('profile', [UserController::class, 'updateProfile']);
    Route::post('client/reset-preferences', [UserController::class, 'resetPreferences']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('client/notifications', [NotificationController::class, 'index']);
    Route::post('payments/checkout-session', [PaymentController::class, 'createCheckoutSession']);
    Route::post('payments/process', [PaymentController::class, 'process']);
    Route::get('payments/verify-session/{sessionId}', [PaymentController::class, 'verifyCheckoutSession']);
    Route::get('payments/history', [PaymentController::class, 'history']);
    Route::post('clients/signal', [ClientSignalController::class, 'store']);
    Route::get('clients/me/signals', [ClientSignalController::class, 'mySignals']);
    Route::get('clients/me/incidents', [IncidentController::class, 'myIncidents']);
    Route::post('client/chatbot', [ChatbotController::class, 'chat']);
});

// Receptionniste routes (STRICT: receptionniste only, not admin)
Route::middleware(['jwt.auth', 'role:receptionniste,admin'])->group(function () {
    Route::get('receptionniste/stats', [StatistiqueController::class, 'receptionStats']);
    Route::get('receptionniste/queue/stats', [StatistiqueController::class, 'queueStats']);
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::get('reception/reservations', [ReservationController::class, 'index']);
    Route::get('receptionniste/reservations', [ReservationController::class, 'index']);
    Route::get('receptionniste/checkin/search', [ReservationController::class, 'checkinSearch']);
    Route::get('receptionniste/checkin/today', [ReservationController::class, 'checkinToday']);
    Route::get('reservations/pending', [ReservationController::class, 'pending']);
    Route::get('reservations/special-requests', [ReservationController::class, 'specialRequests']);
    Route::put('reservations/{id}/special-request/done', [ReservationController::class, 'markSpecialRequestDone']);
    Route::put('reservations/{id}/confirmer', [ReservationController::class, 'confirmer']);
    Route::put('receptionniste/reservations/{id}/accept', [ReservationController::class, 'confirmer']);
    Route::put('reservations/{id}/rejeter', [ReservationController::class, 'rejeter']);
    Route::put('reservations/{id}/checkin', [ReservationController::class, 'checkin']);
    Route::put('receptionniste/reservations/{id}/checkin', [ReservationController::class, 'checkin']);
    Route::put('reservations/{id}/checkout', [ReservationController::class, 'checkout']);
    Route::get('chambres/grille', [ChambreController::class, 'grille']);
    Route::get('reception/room-grid', [ChambreController::class, 'grille']);
    Route::get('reception/signals', [ClientSignalController::class, 'pending']);
    Route::get('reservations/{id}', [ReservationController::class, 'show']);
});

// Admin routes
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::apiResource('admin/hotels', HotelController::class);
    Route::put('admin/hotels/{hotel}/toggle', [HotelController::class, 'toggle']);
    Route::apiResource('admin/chambres', ChambreController::class);
    Route::apiResource('admin/services', ServiceController::class);
    Route::get('admin/users', [UserController::class, 'index']);
    Route::post('admin/users', [UserController::class, 'store']);
    Route::put('admin/users/{id}', [UserController::class, 'update']);
    Route::delete('admin/users/{id}', [UserController::class, 'destroy']);
    Route::get('admin/users/{id}/reservations', [UserController::class, 'reservations']);
    Route::get('admin/stats', [StatistiqueController::class, 'dashboard']);
    Route::get('admin/overview', [StatistiqueController::class, 'dashboard']);
    Route::get('admin/statistiques', [StatistiqueController::class, 'dashboard']);
    Route::get('admin/statistics', [StatistiqueController::class, 'dashboard']);
    Route::get('admin/payments', [PaymentController::class, 'index']);
    Route::get('admin/payments/export-csv', [PaymentController::class, 'exportCsv']);
    Route::get('admin/payments/{id}', [PaymentController::class, 'show']);
});

// Marketing routes
Route::middleware(['jwt.auth', 'role:marketing,admin'])->group(function () {
    Route::apiResource('marketing/promotions', PromotionController::class);
    Route::get('marketing/statistiques', [StatistiqueController::class, 'marketing']);
    Route::get('marketing/statistics', [StatistiqueController::class, 'marketing']);
    Route::get('marketing/stats', [StatistiqueController::class, 'marketingStats']);
    Route::get('marketing/loyalty', [StatistiqueController::class, 'loyalty']);
    Route::get('marketing/avis', [AvisController::class, 'moderation']);
    Route::get('marketing/reviews', [AvisController::class, 'moderation']);
    Route::get('marketing/reviews/export-csv', [AvisController::class, 'exportCsv']);
    Route::put('marketing/reviews/{id}/approve', [AvisController::class, 'approve']);
    Route::put('marketing/reviews/{id}/reject', [AvisController::class, 'reject']);
    Route::put('marketing/reviews/{id}/reply', [AvisController::class, 'reply']);
    Route::put('marketing/avis/{id}/moderer', [AvisController::class, 'moderer']);
    Route::post('marketing/offers/email', [MarketingEmailController::class, 'sendSpecialOffer']);
});
