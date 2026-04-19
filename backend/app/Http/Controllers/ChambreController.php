<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\Hotel;
use Illuminate\Http\Request;

class ChambreController extends Controller
{
    public function index(Request $request)
    {
        $query = Chambre::query();
        if ($request->has('hotelId')) {
            $query->where('hotelId', $request->hotelId);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'hotelId' => 'required|string',
            'type' => 'required|in:SIMPLE,DOUBLE,SUITE,FAMILIALE,DELUXE,PRESIDENTIELLE',
            'nom' => 'required|string',
            'prix_base' => 'required|numeric|min:0',
            'maxVoyageurs' => 'required|integer|min:1',
            'statut' => 'nullable|in:LIBRE,OCCUPE,ENTRETIEN,NETTOYAGE',
        ]);

        $chambre = Chambre::create($request->all());
        return response()->json($chambre, 201);
    }

    public function show($id)
    {
        return response()->json(Chambre::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $chambre = Chambre::findOrFail($id);
        $chambre->update($request->all());
        return response()->json($chambre);
    }

    public function destroy($id)
    {
        Chambre::findOrFail($id)->delete();
        return response()->json(['message' => 'Chambre supprimée']);
    }

    public function grille(Request $request)
    {
        $query = Chambre::query();
        if ($request->filled('hotelId')) {
            $query->where('hotelId', $request->hotelId);
        }

        $chambres = $query->get()->map(function (Chambre $chambre) {
            $row = $chambre->toArray();
            $row['hotel'] = Hotel::find($chambre->hotelId);
            $row['numero'] = $row['numero'] ?? $row['room_number'] ?? $this->deriveRoomNumber($chambre);
            return $row;
        });

        return response()->json($chambres);
    }

    private function deriveRoomNumber(Chambre $chambre): string
    {
        $name = (string) ($chambre->nom ?? '');
        if (preg_match('/\d+/', $name, $matches)) {
            return (string) $matches[0];
        }

        return strtoupper(substr((string) $chambre->_id, -4));
    }
}
