<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();
        if ($request->has('hotelId')) {
            $query->where('hotelId', $request->hotelId);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'hotelId' => 'required|string',
            'categorie' => 'required|in:SPA,RESTAURANT,ACTIVITE,CONCIERGERIE',
            'nom' => 'required|string',
            'prix' => 'required|numeric|min:0',
        ]);
        return response()->json(Service::create($request->all()), 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->all());
        return response()->json($service);
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return response()->json(['message' => 'Service supprimé']);
    }
}
