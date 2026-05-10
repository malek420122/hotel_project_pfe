<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromotionController extends Controller
{
    public function index()
    {
        $promos = Promotion::orderBy('created_at', 'desc')->get();
        return response()->json($promos->map(function($p) {
            return [
                '_id' => (string) $p->_id,
                'id' => (string) $p->_id,
                'titre' => $p->titre,
                'description' => $p->description,
                'remise_pourcent' => (int) ($p->remise_pourcent ?? 0),
                'type' => $p->type ?? 'POURCENTAGE',
                'valeur' => $p->valeur ?? 0,
                'codePromo' => $p->codePromo,
                'dateDebut' => $p->dateDebut instanceof \DateTimeInterface ? $p->dateDebut->format('Y-m-d') : (is_string($p->dateDebut) ? substr($p->dateDebut, 0, 10) : null),
                'dateFin' => $p->dateFin instanceof \DateTimeInterface ? $p->dateFin->format('Y-m-d') : (is_string($p->dateFin) ? substr($p->dateFin, 0, 10) : null),
                'estActive' => (bool) $p->estActive,
                'nbUtilisations' => (int) ($p->nbUtilisations ?? 0),
                'limiteUtilisations' => (int) ($p->limiteUtilisations ?? 100),
            ];
        }));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titre' => 'required|string',
            'dateDebut' => 'required',
            'dateFin' => 'required',
        ]);

        $data = $this->prepareData($request->all());
        $promo = Promotion::create($data);
        return response()->json($promo, 201);
    }

    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);
        $data = $this->prepareData($request->all());
        $promo->update($data);
        return response()->json($promo);
    }

    private function prepareData(array $data): array
    {
        // Handle value/type mapping
        if (isset($data['type']) && $data['type'] === 'MONTANT_FIXE') {
            $data['valeur'] = (float) ($data['valeur'] ?? $data['remise_pourcent'] ?? 0);
            $data['remise_pourcent'] = 0;
        } else {
            $data['type'] = 'POURCENTAGE';
            $data['remise_pourcent'] = (int) ($data['remise_pourcent'] ?? $data['valeur'] ?? 10);
            $data['valeur'] = 0;
        }

        // Ensure codePromo is set
        if (!isset($data['codePromo']) && isset($data['code'])) {
            $data['codePromo'] = $data['code'];
        }
        if (empty($data['codePromo'])) {
            $data['codePromo'] = strtoupper(Str::random(8));
        }

        // Ensure estActive is boolean
        if (isset($data['estActive'])) {
            $data['estActive'] = filter_var($data['estActive'], FILTER_VALIDATE_BOOLEAN);
        }

        // Ensure numeric fields
        $data['nbUtilisations'] = (int) ($data['nbUtilisations'] ?? 0);
        $data['limiteUtilisations'] = (int) ($data['limiteUtilisations'] ?? 100);

        return $data;
    }

    public function destroy($id)
    {
        Promotion::findOrFail($id)->delete();
        return response()->json(['message' => 'Promotion supprimée']);
    }

    public function validate(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $now = now();
        
        $promo = Promotion::where('codePromo', $request->code)
            ->where('estActive', true)
            ->first();

        if (!$promo) {
            return response()->json(['error' => 'Code promo invalide'], 404);
        }

        // Manual date check if they are stored as strings or UTCDateTime
        $start = $promo->dateDebut;
        $end = $promo->dateFin;

        if ($start instanceof \DateTimeInterface) {
            if ($now < $start) return response()->json(['error' => 'Promotion pas encore commencée'], 403);
        } elseif (is_string($start)) {
            if ($now->format('Y-m-d') < substr($start, 0, 10)) return response()->json(['error' => 'Promotion pas encore commencée'], 403);
        }

        if ($end instanceof \DateTimeInterface) {
            if ($now > $end) return response()->json(['error' => 'Promotion expirée'], 403);
        } elseif (is_string($end)) {
            if ($now->format('Y-m-d') > substr($end, 0, 10)) return response()->json(['error' => 'Promotion expirée'], 403);
        }

        return response()->json($promo);
    }
}
