<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\StatisticsService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct(private StatisticsService $statisticsService)
    {
    }

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'message' => __('messages.dashboard_loaded'),
            'data' => $this->statisticsService->summary(),
        ]);
    }

    public function bookings(): JsonResponse
    {
        $bookings = Booking::query()->orderBy('check_in', 'desc')->limit(50)->get();

        return response()->json(['data' => $bookings]);
    }
}
