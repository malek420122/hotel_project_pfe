<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

Route::get('rooms', [RoomController::class, 'index']);
Route::post('rooms', [RoomController::class, 'store']);

Route::get('services', [ServiceController::class, 'index']);
Route::post('services', [ServiceController::class, 'store']);

Route::get('bookings', [BookingController::class, 'index']);
Route::post('bookings', [BookingController::class, 'store']);
Route::patch('bookings/{booking}', [BookingController::class, 'update']);
Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

Route::post('payments', [PaymentController::class, 'store']);

Route::prefix('admin')->group(function (): void {
    Route::get('dashboard', [AdminController::class, 'dashboard']);
    Route::get('bookings', [AdminController::class, 'bookings']);
});
