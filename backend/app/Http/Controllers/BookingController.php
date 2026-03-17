<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private NotificationService $notificationService
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->filled('user_id')) {
            return response()->json(['message' => __('messages.user_required')], 422);
        }

        $bookings = $this->bookingService->listBookingsForUser($request->input('user_id'));

        return response()->json(['data' => $bookings]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'room_id' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'services' => 'array',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $booking = $this->bookingService->createBooking($validator->validated());
        $this->notificationService->sendBookingConfirmation($booking);

        return response()->json([
            'message' => __('messages.booking_created'),
            'data' => $booking,
        ], 201);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $booking = $this->bookingService->cancelBooking($booking);
        $this->notificationService->sendBookingCancellation($booking);

        return response()->json([
            'message' => __('messages.booking_cancelled'),
            'data' => $booking,
        ]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => __('messages.booking_cancelled')], 409);
        }

        $validator = Validator::make($request->all(), [
            'room_id' => 'sometimes|string',
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
            'guests' => 'sometimes|integer|min:1',
            'services' => 'sometimes|array',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $booking = $this->bookingService->updateBooking($booking, $validator->validated());

        return response()->json([
            'message' => __('messages.booking_updated'),
            'data' => $booking,
        ]);
    }
}
