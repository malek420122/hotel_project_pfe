<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvisController extends Controller
{
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $request->validate([
            'hotelId' => 'required|string',
            'reservationId' => 'required|string',
            'note' => 'required|integer|between:1,10',
            'commentaire' => 'required|string|min:10',
        ]);

        $avis = Avis::create([
            'clientId' => (string) $user->_id,
            'hotelId' => $request->hotelId,
            'reservationId' => $request->reservationId,
            'note' => $request->note,
            'commentaire' => $request->commentaire,
        ]);

        return response()->json($avis, 201);
    }

    public function moderation()
    {
        return response()->json(Avis::where('statut', 'EN_ATTENTE')->orderBy('created_at', 'desc')->get());
    }

    public function moderer(Request $request, $id)
    {
        $request->validate(['action' => 'required|in:PUBLIE,REJETE', 'reponse' => 'nullable|string']);
        $avis = Avis::findOrFail($id);
        $avis->update([
            'statut' => $request->action,
            'reponseHotel' => $request->reponse ?? '',
        ]);

        if ($request->action === 'PUBLIE') {
            $hotel = Hotel::find($avis->hotelId);
            if ($hotel) {
                $avgNote = Avis::where('hotelId', $avis->hotelId)->where('statut', 'PUBLIE')->avg('note');
                $hotel->update(['noteMoyenne' => round($avgNote, 1)]);
            }
        }

        return response()->json($avis);
    }
}
