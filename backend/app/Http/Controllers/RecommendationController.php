<?php
namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected RecommendationService $svc;

    public function __construct(RecommendationService $svc)
    {
        $this->svc = $svc;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $userId = (string) ($user->_id ?? $user->id ?? $user->getKey());
        $suggestions = $this->svc->getPersonalizedFeed($userId, 10);

        return response()->json(['data' => $suggestions]);
    }

    public function recordActivity(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'hotel_id' => ['nullable', 'string'],
            'action_type' => ['required', 'string', 'in:view,click,reservation'],
            'category_tags' => ['sometimes', 'array'],
            'category_tags.*' => ['string'],
        ]);

        $hotelId = $data['hotel_id'] ?? null;
        $tags = array_values(array_filter(array_map('strval', $data['category_tags'] ?? [])));

        if ($hotelId && $tags === []) {
            $hotel = Hotel::find($hotelId);
            if ($hotel) {
                $tags = array_values(array_filter(array_merge(
                    [$hotel->ville ?? null],
                    is_array($hotel->equipements ?? null) ? $hotel->equipements : [],
                    is_array($hotel->services ?? null) ? $hotel->services : []
                )));
            }
        }

        $userId = (string) ($user->_id ?? $user->id ?? $user->getKey());
        $this->svc->recordActivity($userId, $hotelId, (string) $data['action_type'], $tags);

        return response()->json([
            'message' => 'Activity recorded',
            'user_id' => $userId,
            'hotel_id' => $hotelId,
            'action_type' => $data['action_type'],
        ], 201);
    }
}
