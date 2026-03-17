<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingService
{
    public function calculatePrice(Room $room, Carbon $checkIn, Carbon $checkOut, array $services = []): float
    {
        $nights = max($checkIn->diffInDays($checkOut), 1);
        $serviceTotal = collect($services)->sum('price');

        return ($room->price_per_night * $nights) + $serviceTotal;
    }

    public function createBooking(array $payload): Booking
    {
        $room = Room::findOrFail($payload['room_id']);
        $checkIn = Carbon::parse($payload['check_in']);
        $checkOut = Carbon::parse($payload['check_out']);
        $totalAmount = $this->calculatePrice($room, $checkIn, $checkOut, $payload['services'] ?? []);

        return Booking::create([
            'user_id' => $payload['user_id'],
            'room_id' => $payload['room_id'],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $payload['guests'],
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => $totalAmount,
            'services' => $payload['services'] ?? [],
            'special_requests' => $payload['special_requests'] ?? null,
        ]);
    }

    public function cancelBooking(Booking $booking): Booking
    {
        $booking->update([
            'status' => 'cancelled',
        ]);

        return $booking;
    }

    public function updateBooking(Booking $booking, array $payload): Booking
    {
        $data = [];

        if (array_key_exists('room_id', $payload)) {
            $data['room_id'] = $payload['room_id'];
        }

        if (array_key_exists('check_in', $payload)) {
            $data['check_in'] = Carbon::parse($payload['check_in']);
        }

        if (array_key_exists('check_out', $payload)) {
            $data['check_out'] = Carbon::parse($payload['check_out']);
        }

        if (array_key_exists('guests', $payload)) {
            $data['guests'] = $payload['guests'];
        }

        if (array_key_exists('services', $payload)) {
            $data['services'] = $payload['services'];
        }

        if (array_key_exists('special_requests', $payload)) {
            $data['special_requests'] = $payload['special_requests'];
        }

        if (! empty($data)) {
            $room = Room::findOrFail($data['room_id'] ?? $booking->room_id);
            $checkIn = $data['check_in'] ?? Carbon::parse($booking->check_in);
            $checkOut = $data['check_out'] ?? Carbon::parse($booking->check_out);
            $services = $data['services'] ?? ($booking->services ?? []);

            $data['total_amount'] = $this->calculatePrice($room, $checkIn, $checkOut, $services);

            $booking->update($data);
        }

        return $booking;
    }

    public function listBookingsForUser(string $userId): Collection
    {
        return Booking::where('user_id', $userId)->orderBy('check_in', 'desc')->get();
    }
}
