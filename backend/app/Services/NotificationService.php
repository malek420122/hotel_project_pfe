<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendBookingConfirmation(Booking $booking): void
    {
        Log::info('Booking confirmation sent.', ['booking_id' => $booking->getKey()]);
    }

    public function sendBookingCancellation(Booking $booking): void
    {
        Log::info('Booking cancellation sent.', ['booking_id' => $booking->getKey()]);
    }
}
