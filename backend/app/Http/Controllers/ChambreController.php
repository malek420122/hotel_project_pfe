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

        $rooms = $query->get()
            ->map(fn (Chambre $chambre) => $this->presentRoom($chambre))
            ->sortBy(fn (array $room) => $this->roomSortValue((string) $room['number']))
            ->values();

        return response()->json($rooms);
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
            $row['hotel'] = Hotel::find($chambre->hotel_id);
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

    private function presentRoom(Chambre $chambre): array
    {
        $row = $chambre->toArray();
        $number = $row['number'] ?? $row['numero'] ?? $row['room_number'] ?? $this->deriveRoomNumber($chambre);

        $row['number'] = (string) $number;
        $row['numero'] = (string) ($row['numero'] ?? $number);
        $row['type'] = $this->presentRoomType((string) ($row['type'] ?? 'Standard'));
        $row['floor'] = (int) ($row['etage'] ?? $this->deriveFloor((string) $number));
        $row['etage'] = $row['floor'];

        return $row;
    }

    private function presentRoomType(string $type): string
    {
        $normalized = strtolower(trim($type));
        $map = [
            'simple' => 'Simple',
            'single' => 'Simple',
            'double' => 'Double',
            'standard' => 'Standard',
            'suite' => 'Suite',
            'familiale' => 'Familiale',
            'family' => 'Familiale',
            'deluxe' => 'Deluxe',
            'presidentielle' => 'Présidentielle',
            'presidential' => 'Présidentielle',
        ];

        return $map[$normalized] ?? ucfirst(strtolower($type ?: 'Standard'));
    }

    private function deriveFloor(string $number): int
    {
        if (preg_match('/^\d+/', $number, $matches)) {
            return max(0, (int) floor(((int) $matches[0]) / 100));
        }

        return 0;
    }

    private function roomSortValue(string $number): int
    {
        if (preg_match('/\d+/', $number, $matches)) {
            return (int) $matches[0];
        }

        return PHP_INT_MAX;
    }
}
