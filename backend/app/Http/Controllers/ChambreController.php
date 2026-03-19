<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
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

    public function grille()
    {
        $chambres = Chambre::all();
        return response()->json($chambres);
    }
}
