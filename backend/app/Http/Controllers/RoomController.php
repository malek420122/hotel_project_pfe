<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'check_in' => 'sometimes|date',
            'check_out' => 'sometimes|date|after:check_in',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $excludedRoomIds = [];

        if ($request->filled(['check_in', 'check_out'])) {
            $checkIn = Carbon::parse($request->input('check_in'));
            $checkOut = Carbon::parse($request->input('check_out'));

            $excludedRoomIds = Booking::query()
                ->where('status', '!=', 'cancelled')
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->pluck('room_id')
                ->toArray();
        }

        $rooms = Room::query()
            ->when($request->integer('capacity'), fn ($query, $capacity) => $query->where('capacity', '>=', $capacity))
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when(! empty($excludedRoomIds), fn ($query) => $query->whereNotIn('_id', $excludedRoomIds))
            ->orderBy('price_per_night')
            ->get();

        return response()->json(['data' => $rooms]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'amenities' => 'array',
            'status' => 'nullable|string|in:available,unavailable,maintenance',
            'images' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $room = Room::create([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'capacity' => $request->integer('capacity'),
            'price_per_night' => $request->input('price_per_night'),
            'amenities' => $request->input('amenities', []),
            'status' => $request->input('status', 'available'),
            'images' => $request->input('images', []),
        ]);

        return response()->json(['message' => __('messages.room_created'), 'data' => $room], 201);
    }
}
