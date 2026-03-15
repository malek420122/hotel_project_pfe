<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    public function index()
    {
        return response()->json(Promotion::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string',
            'remise_pourcent' => 'required|integer|between:5,80',
            'dateDebut' => 'required|date',
            'dateFin' => 'required|date|after:dateDebut',
        ]);

        $codePromo = $request->codePromo ?? strtoupper(Str::random(8));

        $promo = Promotion::create(array_merge($request->all(), ['codePromo' => $codePromo]));
        return response()->json($promo, 201);
    }

    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);
        $promo->update($request->all());
        return response()->json($promo);
    }

    public function destroy($id)
    {
        Promotion::findOrFail($id)->delete();
        return response()->json(['message' => 'Promotion supprimée']);
    }

    public function validate(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $promo = Promotion::where('codePromo', $request->code)
            ->where('estActive', true)
            ->where('dateDebut', '<=', now())
            ->where('dateFin', '>=', now())
            ->first();

        if (!$promo) {
            return response()->json(['error' => 'Code promo invalide ou expiré'], 404);
        }
        return response()->json($promo);
    }
}
