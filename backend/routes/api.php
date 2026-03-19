<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\ChambreController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt.auth');
});

// Public routes
Route::get('hotels', [HotelController::class, 'index']);
Route::get('hotels/{id}', [HotelController::class, 'show']);
Route::get('hotels/{id}/chambres', [HotelController::class, 'chambresDisponibles']);
Route::get('hotels/{id}/avis', [HotelController::class, 'avis']);
Route::post('promotions/validate', [PromotionController::class, 'validate']);

// Client routes (JWT + role:client or admin)
Route::middleware(['jwt.auth', 'role:client,admin'])->group(function () {
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::get('client/reservations', [ReservationController::class, 'myReservations']);
    Route::delete('reservations/{id}', [ReservationController::class, 'cancel']);
    Route::get('reservations/{id}/invoice', [ReservationController::class, 'invoice']);
    Route::post('avis', [AvisController::class, 'store']);
    Route::get('client/fidelite', [UserController::class, 'fidelite']);
    Route::put('profile', [UserController::class, 'updateProfile']);
    Route::get('services', [ServiceController::class, 'index']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
    // Payment processing
    Route::post('payments/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('payments/process', [PaymentController::class, 'process']);
});

// Receptionniste routes
Route::middleware(['jwt.auth', 'role:receptionniste,admin'])->group(function () {
    Route::get('reservations', [ReservationController::class, 'adminIndex']);
    Route::get('reservations/pending', [ReservationController::class, 'pending']);
    Route::put('reservations/{id}/confirmer', [ReservationController::class, 'confirmer']);
    Route::put('reservations/{id}/rejeter', [ReservationController::class, 'rejeter']);
    Route::put('reservations/{id}/checkin', [ReservationController::class, 'checkin']);
    Route::put('reservations/{id}/checkout', [ReservationController::class, 'checkout']);
    Route::get('chambres/grille', [ChambreController::class, 'grille']);
    Route::get('reservations/{id}', [ReservationController::class, 'show']);
});

// Admin routes
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::apiResource('admin/hotels', HotelController::class);
    Route::put('admin/hotels/{hotel}/toggle', [HotelController::class, 'toggle']);
    Route::apiResource('admin/chambres', ChambreController::class);
    Route::apiResource('admin/services', ServiceController::class);
    Route::get('admin/users', [UserController::class, 'index']);
    Route::put('admin/users/{id}', [UserController::class, 'update']);
    Route::get('admin/users/{id}/reservations', [UserController::class, 'reservations']);
    // Admin payments
    Route::get('admin/payments', [PaymentController::class, 'index']);
    Route::get('admin/payments/{id}', [PaymentController::class, 'show']);
    Route::get('admin/statistiques', [StatistiqueController::class, 'dashboard']);
});

// Marketing routes
Route::middleware(['jwt.auth', 'role:marketing,admin'])->group(function () {
    Route::apiResource('marketing/promotions', PromotionController::class);
    Route::post('marketing/notifications/broadcast', [NotificationController::class, 'sendToAll']);
    Route::get('marketing/statistiques', [StatistiqueController::class, 'marketing']);
    Route::get('marketing/avis', [AvisController::class, 'moderation']);
    Route::put('marketing/avis/{id}/moderer', [AvisController::class, 'moderer']);
});
