<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Hotel;
use App\Models\User;
use App\Models\Chambre;
use App\Models\Avis;
use App\Models\LoyaltyPoint;
use App\Models\Paiement;
use App\Models\Notification;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class StatistiqueController extends Controller
{
    public function clientStats()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = (string) $user->_id;
        $today = Carbon::today();

        $reservations = Reservation::where('clientId', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $reservationIds = $reservations->pluck('_id')->map(fn ($id) => (string) $id)->all();

        $paymentRows = Paiement::whereIn('reservationId', $reservationIds)->get();

        $paidStatuses = ['PAYE', 'PAID', 'COMPLETED'];

        $totalDepenses = (float) $paymentRows
            ->filter(fn (Paiement $payment) => in_array(strtoupper((string) ($payment->statut ?? '')), $paidStatuses, true))
            ->sum('montant');

        if ($totalDepenses <= 0) {
            $fallbackSpentStatuses = ['CONFIRMEE', 'CHECKIN', 'EN_COURS', 'CHECKOUT', 'TERMINEE'];
            $totalDepenses = (float) $reservations
                ->filter(fn (Reservation $reservation) => in_array(strtoupper((string) ($reservation->statut ?? '')), $fallbackSpentStatuses, true))
                ->sum('prixTotal');
        }

        $sejoursCompletes = $reservations
            ->filter(fn (Reservation $reservation) => in_array(strtoupper((string) ($reservation->statut ?? '')), ['CHECKOUT', 'TERMINEE'], true))
            ->count();

        $reservationsActives = $reservations
            ->filter(function (Reservation $reservation) use ($today) {
                $status = strtoupper((string) ($reservation->statut ?? ''));
                if ($status !== 'CONFIRMEE') {
                    return false;
                }

                $dateDepart = $reservation->dateDepart ? Carbon::parse((string) $reservation->dateDepart) : null;

                return $dateDepart && $dateDepart->greaterThanOrEqualTo($today);
            })
            ->count();

        $loyaltyRecord = LoyaltyPoint::where('user_id', $userId)->first();
        if (! $loyaltyRecord) {
            $loyaltyRecord = LoyaltyPoint::create([
                'user_id' => $userId,
                'points_total' => (int) ($user->points_fidelite ?? 0),
            ]);
        }

        $loyaltyPoints = (int) ($loyaltyRecord->points_total ?? 0);

        $prochainSejour = $reservations
            ->filter(function (Reservation $reservation) {
                $status = strtoupper((string) ($reservation->statut ?? ''));
                if (! in_array($status, ['CONFIRMEE', 'EN_ATTENTE'], true)) {
                    return false;
                }

                if (! $reservation->dateArrivee) {
                    return false;
                }

                return Carbon::parse((string) $reservation->dateArrivee)->greaterThanOrEqualTo(Carbon::now()->startOfDay());
            })
            ->sortBy('dateArrivee')
            ->first();

        $recentReservations = $reservations
            ->take(3)
            ->map(fn (Reservation $reservation) => $this->presentReservation($reservation))
            ->values();

        return response()->json([
            'total_depenses' => round($totalDepenses, 2),
            'points_fidelite' => $loyaltyPoints,
            'loyalty_points' => $loyaltyPoints,
            'sejours_completes' => $sejoursCompletes,
            'reservations_actives' => $reservationsActives,
            'prochain_sejour' => $prochainSejour ? $this->presentReservation($prochainSejour) : null,
            'recent_reservations' => $recentReservations,
        ]);
    }

    public function receptionStats()
    {
        $today = Carbon::today();

        $checkinsToday = Reservation::query()
            ->whereIn('statut', ['CONFIRMEE', 'EN_COURS'])
            ->whereDate('dateArrivee', $today)
            ->count();

        $checkoutsToday = Reservation::query()
            ->whereIn('statut', ['EN_COURS', 'CHECKIN'])
            ->whereDate('dateDepart', $today)
            ->count();

        $pendingReservations = Reservation::query()
            ->where('statut', 'EN_ATTENTE')
            ->count();

        $specialRequests = Reservation::query()
            ->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE', 'EN_COURS'])
            ->where('demandesSpeciales', '!=', '')
            ->count();

        return response()->json([
            'checkins_today' => $checkinsToday,
            'checkouts_today' => $checkoutsToday,
            'pending_reservations' => $pendingReservations,
            'special_requests' => $specialRequests,
        ]);
    }

    public function queueStats()
    {
        $today = Carbon::today();

        return response()->json([
            'checkins_today' => Reservation::query()
                ->whereIn('statut', ['CONFIRMEE', 'EN_COURS'])
                ->whereDate('dateArrivee', $today)
                ->count(),
            'checkouts_today' => Reservation::query()
                ->whereIn('statut', ['EN_COURS'])
                ->whereDate('dateDepart', $today)
                ->count(),
            'pending_reservations' => Reservation::query()->where('statut', 'EN_ATTENTE')->count(),
        ]);
    }

    public function marketingStats()
    {
        $reservations = Reservation::query()->get();
        $totalReservations = $reservations->count();
        $confirmedReservations = $reservations->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'TERMINEE', 'CHECKOUT'])->count();

        $conversionRate = $totalReservations > 0
            ? round(($confirmedReservations / $totalReservations) * 100, 1)
            : 0.0;

        $avgRating = Avis::query()->avg('note');
        $monthStart = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->startOfMonth()->addMonth();

        $promoCodesUsed = $reservations
            ->filter(fn (Reservation $reservation) => trim((string) ($reservation->codePromoApplique ?? '')) !== '')
            ->count();

        $revenusParHotel = $reservations
            ->groupBy('hotelId')
            ->map(function ($items, $hotelId) {
                $hotel = Hotel::find((string) $hotelId);
                return [
                    'hotel_id' => (string) $hotelId,
                    'hotel_nom' => (string) ($hotel?->nom ?? 'Hotel'),
                    'reservation_count' => $items->count(),
                    'revenu_total' => round((float) $items->sum('prixTotal'), 2),
                ];
            })
            ->sortByDesc('revenu_total')
            ->take(5)
            ->values();

        return response()->json([
            'taux_conversion' => $conversionRate,
            'note_moyenne' => $avgRating !== null ? round((float) $avgRating, 1) : 0.0,
            'codes_promo_utilises' => (int) $promoCodesUsed,
            'promotions_actives' => (int) Promotion::query()->where(function ($query) {
                $query->where('estActive', true)->orWhere('actif', true);
            })->count(),
            'total_avis' => (int) Avis::query()->count(),
            'avis_en_attente' => (int) Avis::query()->whereIn('statut', ['EN_ATTENTE', 'pending'])->count(),
            'avis_ce_mois' => (int) Avis::query()->where('created_at', '>=', $monthStart)->where('created_at', '<', $nextMonth)->count(),
            'membres_fidelite' => (int) LoyaltyPoint::query()->count(),
            'revenus_par_hotel' => $revenusParHotel,

            // Compatibility aliases for existing frontend code
            'avg_rating' => $avgRating !== null ? round((float) $avgRating, 1) : 0.0,
            'total_reviews' => (int) Avis::query()->count(),
            'reviews_this_month' => (int) Avis::query()->where('created_at', '>=', $monthStart)->where('created_at', '<', $nextMonth)->count(),
            'active_promotions' => (int) Promotion::query()->where(function ($query) {
                $query->where('estActive', true)->orWhere('actif', true);
            })->count(),
            'loyalty_members' => (int) LoyaltyPoint::query()->count(),
        ]);
    }

    private function presentReservation(Reservation $reservation): array
    {
        $hotel = Hotel::find($reservation->hotelId);
        $chambre = Chambre::find($reservation->chambreId);
        $client = User::find($reservation->clientId);

        return array_merge($reservation->toArray(), [
            'hotel' => $hotel,
            'chambre' => $chambre,
            'client' => $client ? [
                '_id' => (string) $client->_id,
                'nom' => $client->nom,
                'prenom' => $client->prenom,
            ] : null,
        ]);
    }

    public function dashboard()
    {
        $hotels = Hotel::all();
        $chambres = Chambre::all();
        $users = User::all();
        $reservations = Reservation::all();

        $hotelsActifs = $hotels->where('estActif', true)->count();
        $chambresDispos = $chambres->where('estDisponible', true)->count();
        $utilisateurs = $users->count();

        $reservationsTerminees = $reservations->where('statut', 'TERMINEE');
        $revenusMois = (float) $reservationsTerminees
            ->filter(function (Reservation $reservation) {
                if (! $reservation->created_at) {
                    return false;
                }

                return Carbon::parse((string) $reservation->created_at)
                    ->greaterThanOrEqualTo(Carbon::now()->startOfMonth());
            })
            ->sum('prixTotal');

        $reservationsMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = $reservations
                ->filter(function (Reservation $reservation) use ($date) {
                    if (! $reservation->dateArrivee) {
                        return false;
                    }

                    $reservationDate = Carbon::parse((string) $reservation->dateArrivee);

                    return $reservationDate->year === $date->year
                        && $reservationDate->month === $date->month;
                })
                ->count();
            $reservationsMois[] = ['mois' => $date->format('M Y'), 'count' => $count];
        }

        $revenusMensuel = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $total = $reservationsTerminees
                ->filter(function (Reservation $reservation) use ($date) {
                    if (! $reservation->created_at) {
                        return false;
                    }

                    $createdAt = Carbon::parse((string) $reservation->created_at);

                    return $createdAt->year === $date->year
                        && $createdAt->month === $date->month;
                })
                ->sum('prixTotal');
            $revenusMensuel[] = ['mois' => $date->format('M Y'), 'total' => $total];
        }

        $recentReservations = $reservations
            ->sortByDesc('created_at')
            ->take(6)
            ->map(fn (Reservation $reservation) => $this->presentReservation($reservation))
            ->values();

        $topHotels = $reservations
            ->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'TERMINEE'])
            ->groupBy('hotelId')
            ->map(function ($items, $hotelId) {
                $hotel = Hotel::find($hotelId);
                $revenue = (float) $items->sum('prixTotal');

                return [
                    'id' => (string) $hotelId,
                    'nom' => $hotel?->nom ?? 'Hotel',
                    'revenu' => round($revenue, 2),
                    'pct' => min(100, max(10, (int) round(($revenue / max(1, $items->count() * 1500)) * 100))),
                ];
            })
            ->sortByDesc('revenu')
            ->take(5)
            ->values();

        return response()->json([
            'kpis' => [
                'hotelsActifs' => $hotelsActifs,
                'chambresDispos' => $chambresDispos,
                'utilisateurs' => $utilisateurs,
                'revenusMois' => $revenusMois,
                'reservationsMois' => $reservations
                    ->filter(function (Reservation $reservation) {
                        return $reservation->dateArrivee
                            && Carbon::parse((string) $reservation->dateArrivee)->year === Carbon::now()->year;
                    })
                    ->count(),
            ],
            'reservationsMois' => $reservationsMois,
            'revenusMensuel' => $revenusMensuel,
            'recentReservations' => $recentReservations,
            'topHotels' => $topHotels,
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

        $reservations = Reservation::query()->where('created_at', '>=', $dateDebut)->get();
        $allReservations = Reservation::query()->get();
        $promotions = Promotion::query()->orderBy('created_at', 'desc')->get();
        $allAvis = Avis::query()->orderBy('created_at', 'desc')->get();
        $publishedAvis = $allAvis->where('statut', 'PUBLIE');

        $topHotels = $reservations
            ->groupBy('hotelId')
            ->map(function ($items, $hotelId) {
                $hotel = Hotel::find($hotelId);
                $revenue = (float) $items->sum('prixTotal');

                return [
                    'id' => (string) $hotelId,
                    'nom' => $hotel?->nom ?? 'Hotel',
                    'revenu' => round($revenue, 2),
                    'reservationsCount' => $items->count(),
                    'pct' => min(100, max(10, (int) round(($revenue / max(1, $items->count() * 1200)) * 100))),
                ];
            })
            ->sortByDesc('revenu')
            ->take(5)
            ->values();

        $promoByCode = $promotions
            ->filter(fn (Promotion $promotion) => trim((string) ($promotion->codePromo ?? '')) !== '')
            ->keyBy(fn (Promotion $promotion) => (string) $promotion->codePromo);

        $promotionEfficiency = $reservations
            ->filter(fn (Reservation $reservation) => trim((string) ($reservation->codePromoApplique ?? '')) !== '')
            ->groupBy(fn (Reservation $reservation) => (string) $reservation->codePromoApplique)
            ->map(function ($items, $promoCode) use ($promoByCode) {
                $promotion = $promoByCode->get((string) $promoCode);
                return [
                    'code' => (string) $promoCode,
                    'label' => (string) ($promotion?->titre ?? $promoCode),
                    'used' => $items->count(),
                    'score' => $items->count(),
                ];
            })
            ->sortByDesc('used')
            ->take(8)
            ->values();

        $recentAvis = $allAvis
            ->take(4)
            ->map(function (Avis $avis) {
                $hotel = Hotel::find($avis->hotelId);
                $client = User::find($avis->clientId);

                return [
                    'id' => (string) $avis->_id,
                    'client' => trim(($client?->prenom ?? '') . ' ' . ($client?->nom ?? '')) ?: 'Client',
                    'hotel' => $hotel?->nom ?? 'Hotel',
                    'note' => (int) $avis->note,
                    'comment' => (string) $avis->commentaire,
                    'statut' => (string) $avis->statut,
                ];
            })
            ->values();

        $tauxConversion = $allReservations->count() > 0
            ? round($allReservations->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'TERMINEE', 'CHECKOUT'])->count() / $allReservations->count() * 100, 1)
            : 0;

        $reviewsThisWeek = (int) Avis::query()->where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        $totalAvis = $allAvis->count();
        $repliedAvis = $allAvis->filter(function (Avis $avis) {
            $reply = trim((string) ($avis->reponse_marketing ?? $avis->reponseHotel ?? ''));
            return $reply !== '';
        })->count();
        $responseRate = $totalAvis > 0 ? round(($repliedAvis / $totalAvis) * 100, 1) : 0;

        $modeRating = 0;
        if ($totalAvis > 0) {
            $modeRating = (int) $allAvis
                ->groupBy(fn (Avis $avis) => (int) ($avis->note ?? 0))
                ->sortByDesc(fn ($items) => $items->count())
                ->keys()
                ->first();
        }

        $bestRatedHotel = $publishedAvis
            ->groupBy('hotelId')
            ->map(function ($items, $hotelId) {
                $hotel = Hotel::find((string) $hotelId);
                return [
                    'hotel_id' => (string) $hotelId,
                    'hotel_nom' => (string) ($hotel?->nom ?? 'Hotel'),
                    'moyenne' => round((float) $items->avg('note'), 1),
                ];
            })
            ->sortByDesc('moyenne')
            ->first();

        $codesPromoUtilises = $allReservations
            ->filter(fn (Reservation $reservation) => trim((string) ($reservation->codePromoApplique ?? '')) !== '')
            ->count();

        $promotionsActives = (int) Promotion::query()->where(function ($query) {
            $query->where('estActive', true)->orWhere('actif', true);
        })->count();

        $noteMoyenne = round((float) ($allAvis->avg('note') ?? 0), 1);

        return response()->json([
            'kpis' => [
                'promotionsActives' => $promotionsActives,
                'codesPromoUtilises' => $codesPromoUtilises,
                'noteMoyenne' => $noteMoyenne,
                'tauxConversion' => $tauxConversion,
            ],
            'topHotels' => $topHotels,
            'promotionEfficiency' => $promotionEfficiency,
            'recentAvis' => $recentAvis,
            'extraStats' => [
                'reviewsThisWeek' => $reviewsThisWeek,
                'responseRate' => $responseRate,
                'modeRating' => $modeRating,
                'bestRatedHotel' => $bestRatedHotel['hotel_nom'] ?? '—',
            ],
            'reservationsParMois' => $reservations
                ->groupBy(fn (Reservation $reservation) => Carbon::parse($reservation->created_at)->format('M Y'))
                ->map(fn ($items, $label) => ['label' => $label, 'count' => $items->count()])
                ->values(),
        ]);
    }

    public function loyalty()
    {
        $clients = User::where('role', 'client')->orderByDesc('points_fidelite')->get();

        $counts = [
            'Bronze' => $clients->where('niveau_fidelite', 'Bronze')->count(),
            'Argent' => $clients->where('niveau_fidelite', 'Argent')->count(),
            'Or' => $clients->where('niveau_fidelite', 'Or')->count(),
        ];

        $topMembers = $clients->take(5)->map(function (User $user) {
            $sejours = Reservation::where('clientId', (string) $user->_id)->where('statut', 'TERMINEE')->count();

            return [
                'name' => trim($user->prenom . ' ' . $user->nom),
                'pts' => (int) ($user->points_fidelite ?? 0),
                'sejours' => $sejours,
                'level' => $user->niveau_fidelite ?? 'Bronze',
            ];
        })->values();

        return response()->json([
            'kpis' => [
                'Bronze' => $counts['Bronze'],
                'Argent' => $counts['Argent'],
                'Or' => $counts['Or'],
            ],
            'levels' => [
                ['name' => 'Bronze', 'icon' => '🥉'],
                ['name' => 'Argent', 'icon' => '🥈'],
                ['name' => 'Or', 'icon' => '🥇'],
            ],
            'topMembers' => $topMembers,
        ]);
    }
}
