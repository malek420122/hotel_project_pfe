<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;

class StatisticsService
{
    public function summary(): array
    {
        return [
            'total_rooms' => Room::count(),
            'total_bookings' => Booking::count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'revenue' => Payment::where('status', 'paid')->sum('amount'),
        ];
    }
}
