<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Chambre;
use App\Models\Avis;
use App\Models\Reservation;
use Illuminate\Http\Request;

use App\Http\Requests\HotelStoreRequest;
use Illuminate\Http\JsonResponse;

class HotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $middlewares = $request->route()?->gatherMiddleware() ?? [];
        $isAdminContext = in_array('role:admin', $middlewares, true);
        $query = Hotel::query();
        if (!$isAdminContext) {
            $query->where('estActif', true);
        }

        if ($request->has('ville')) {
            $query->where('ville', 'like', '%' . $request->ville . '%');
        }
        if ($request->has('etoiles')) {
            $query->whereIn('etoiles', (array) $request->etoiles);
        }

        $hotels = $query->orderBy('noteMoyenne', 'desc')->paginate($request->get('per_page', 10));
        return response()->json($hotels);
    }

    public function show($id): JsonResponse
    {
        $hotel = Hotel::findOrFail($id);
        return response()->json($hotel);
    }

    public function store(HotelStoreRequest $request): JsonResponse
    {
        $hotel = Hotel::create($request->validated());
        return response()->json($hotel, 201);
    }

    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update($request->all());
        return response()->json($hotel);
    }

    public function destroy($id)
    {
        Hotel::findOrFail($id)->delete();
        return response()->json(['message' => 'Hôtel supprimé']);
    }

    public function chambresDisponibles(Request $request, $id)
    {
        $query = Chambre::where('hotelId', $id)->where('estDisponible', true);

        if ($request->has('dateArrivee') && $request->has('dateDepart')) {
            $dateArrivee = $request->dateArrivee;
            $dateDepart = $request->dateDepart;

            $reservedIds = Reservation::where('hotelId', $id)
                ->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'EN_ATTENTE'])
                ->where(function ($q) use ($dateArrivee, $dateDepart) {
                    $q->where('dateArrivee', '<', $dateDepart)
                      ->where('dateDepart', '>', $dateArrivee);
                })
                ->pluck('chambreId')
                ->toArray();

            $query->whereNotIn('_id', $reservedIds);
        }

        return response()->json($query->get());
    }

    public function avis($id)
    {
        $avis = Avis::where('hotelId', $id)->where('statut', 'PUBLIE')->orderBy('created_at', 'desc')->get();
        return response()->json($avis);
    }

    public function toggle($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['estActif' => !$hotel->estActif]);
        return response()->json($hotel);
    }
}
