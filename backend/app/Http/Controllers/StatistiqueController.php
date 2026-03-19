<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Hotel;
use App\Models\User;
use App\Models\Chambre;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatistiqueController extends Controller
{
    public function dashboard()
    {
        $hotelsActifs = Hotel::where('estActif', true)->count();
        $chambresDispos = Chambre::where('estDisponible', true)->count();
        $utilisateurs = User::count();
        $revenusMois = Reservation::where('statut', 'TERMINEE')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('prixTotal');

        $reservationsMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = Reservation::whereYear('dateArrivee', $date->year)
                ->whereMonth('dateArrivee', $date->month)
                ->count();
            $reservationsMois[] = ['mois' => $date->format('M Y'), 'count' => $count];
        }

        $revenusMensuel = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $total = Reservation::where('statut', 'TERMINEE')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('prixTotal');
            $revenusMensuel[] = ['mois' => $date->format('M Y'), 'total' => $total];
        }

        return response()->json([
            'kpis' => [
                'hotelsActifs' => $hotelsActifs,
                'chambresDispos' => $chambresDispos,
                'utilisateurs' => $utilisateurs,
                'revenusMois' => $revenusMois,
            ],
            'reservationsMois' => $reservationsMois,
            'revenusMensuel' => $revenusMensuel,
        ]);
    }

    public function marketing(Request $request)
    {
        $periode = $request->get('periode', '30j');
        $dateDebut = match($periode) {
            '7j' => Carbon::now()->subDays(7),
            '30j' => Carbon::now()->subDays(30),
            '3mois' => Carbon::now()->subMonths(3),
            '6mois' => Carbon::now()->subMonths(6),
            '1an' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };

        $tauxOccupation = Chambre::count() > 0
            ? round(Chambre::where('estDisponible', false)->count() / Chambre::count() * 100, 1)
            : 0;

        $revenus = Reservation::where('statut', 'TERMINEE')->where('created_at', '>=', $dateDebut)->sum('prixTotal');

        $topChambres = Reservation::where('created_at', '>=', $dateDebut)
            ->get()
            ->groupBy('chambreId')
            ->map(fn($g) => ['chambreId' => $g->first()->chambreId, 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $nationalites = User::whereIn('_id', Reservation::where('created_at', '>=', $dateDebut)->pluck('clientId')->toArray())
            ->get()
            ->groupBy('nationalite')
            ->map(fn($g, $k) => ['nationalite' => $k ?: 'Inconnue', 'count' => $g->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return response()->json([
            'kpis' => [
                'tauxOccupation' => $tauxOccupation,
                'revenus' => $revenus,
            ],
            'topChambres' => $topChambres,
            'nationalites' => $nationalites,
        ]);
    }
}
