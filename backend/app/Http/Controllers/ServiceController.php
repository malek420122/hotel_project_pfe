<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'services:index:' . ($request->hotelId ?? 'all');
        $services = Cache::remember($cacheKey, 60, function () use ($request) {
            $query = Service::query();
            if ($request->has('hotelId')) {
                $query->where('hotelId', $request->hotelId);
            }
            return $query->get();
        });

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $request->validate([
            'hotelId' => 'required|string',
            'categorie' => 'required|in:SPA,RESTAURANT,ACTIVITE,CONCIERGERIE',
            'nom' => 'required|string',
            'prix' => 'required|numeric|min:0',
        ]);
        $created = Service::create($request->all());
        Cache::flush();
        return response()->json($created, 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->all());
        Cache::flush();
        return response()->json($service);
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        Cache::flush();
        return response()->json(['message' => 'Service supprimé']);
    }
}
