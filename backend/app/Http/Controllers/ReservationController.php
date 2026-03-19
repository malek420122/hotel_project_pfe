<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Paiement;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

use App\Http\Requests\ReservationStoreRequest;

class ReservationController extends Controller
{
    private function generateReference(): string
    {
        $year = date('Y');
        $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "R-{$year}-{$rand}";
    }

    public function store(ReservationStoreRequest $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $validated = $request->validated();

        // Vérifier la disponibilité (chevauchement)
        $exists = Reservation::where('chambreId', $validated['chambreId'])
            ->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE', 'EN_COURS'])
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('dateArrivee', '>=', $request->dateArrivee)
                      ->where('dateArrivee', '<', $request->dateDepart);
                })->orWhere(function ($q) use ($request) {
                    $q->where('dateDepart', '>', $request->dateArrivee)
                      ->where('dateDepart', '<=', $request->dateDepart);
                })->orWhere(function ($q) use ($request) {
                    $q->where('dateArrivee', '<=', $request->dateArrivee)
                      ->where('dateDepart', '>=', $request->dateDepart);
                });
            })->exists();

        if ($exists) {
            return response()->json(['error' => 'La chambre est déjà réservée pour ces dates.'], 422);
        }

        $chambre = Chambre::findOrFail($validated['chambreId']);
        $nuits = Carbon::parse($validated['dateArrivee'])->diffInDays(Carbon::parse($validated['dateDepart']));
        $prixTotal = $chambre->prix_base * $nuits;

        $remise = 0;
        if (isset($validated['codePromo'])) {
            $promo = Promotion::where('codePromo', $validated['codePromo'])
                ->where('estActive', true)
                ->where('dateDebut', '<=', now())
                ->where('dateFin', '>=', now())
                ->first();
            if ($promo) {
                $remise = $promo->remise_pourcent;
                $prixTotal = $prixTotal * (1 - $remise / 100);
                $promo->increment('nbUtilisations');
            }
        }

        $servicesPrix = 0;
        if (isset($validated['servicesChoisis']) && is_array($validated['servicesChoisis'])) {
            foreach ($validated['servicesChoisis'] as $service) {
                $servicesPrix += $service['prix'] ?? 0;
            }
            $prixTotal += $servicesPrix;
        }

        // Apply Loyalty Discount if no promo code is used
        if ($remise === 0) {
            if ($user->niveau_fidelite === 'Argent') {
                $remise = 5;
                $prixTotal = $prixTotal * 0.95;
            } elseif ($user->niveau_fidelite === 'Or') {
                $remise = 10;
                $prixTotal = $prixTotal * 0.90;
            }
        }

        $reservation = Reservation::create([
            'reference' => $this->generateReference(),
            'clientId' => (string) $user->_id,
            'chambreId' => $validated['chambreId'],
            'hotelId' => $validated['hotelId'],
            'dateArrivee' => $validated['dateArrivee'],
            'dateDepart' => $validated['dateDepart'],
            'nbVoyageurs' => $validated['nbVoyageurs'],
            'prixTotal' => round($prixTotal, 2),
            'demandesSpeciales' => $validated['demandesSpeciales'] ?? '',
            'servicesChoisis' => $validated['servicesChoisis'] ?? [],
            'codePromoApplique' => $validated['codePromo'] ?? '',
            'remiseAppliquee' => $remise,
        ]);

        Paiement::create([
            'reservationId' => (string) $reservation->_id,
            'montant' => $reservation->prixTotal,
            'statut' => 'EN_COURS',
            'methode' => $validated['methodePaiement'] ?? 'carte',
        ]);

        $points = (int) round($prixTotal / 10);
        $newPoints = ($user->points_fidelite ?? 0) + $points;
        $niveau = $newPoints >= 5000 ? 'Or' : ($newPoints >= 1000 ? 'Argent' : 'Bronze');
        $user->update(['points_fidelite' => $newPoints, 'niveau_fidelite' => $niveau]);

        Notification::create([
            'userId' => (string) $user->_id,
            'type' => 'RESERVATION_CREEE',
            'message' => "Votre réservation {$reservation->reference} a été créée avec succès.",
            'estLue' => false,
        ]);

        // Simulation d'envoi d'email
        \Illuminate\Support\Facades\Log::info("EMAIL SENT: Confirmation de réservation {$reservation->reference} à {$user->email}");

        return response()->json($reservation, 201);
    }

    public function myReservations()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $reservations = Reservation::where('clientId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($reservations);
    }

    public function cancel($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $reservation = Reservation::findOrFail($id);

        if ((string) $reservation->clientId !== (string) $user->_id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        if (!in_array($reservation->statut, ['EN_ATTENTE', 'CONFIRMEE'])) {
            return response()->json(['error' => 'Impossible d\'annuler cette réservation'], 400);
        }

        $reservation->update(['statut' => 'ANNULEE']);

        Notification::create([
            'userId' => (string) $user->_id,
            'type' => 'RESERVATION_ANNULEE',
            'message' => "Votre réservation {$reservation->reference} a été annulée.",
            'estLue' => false,
        ]);

        return response()->json($reservation);
    }

    public function pending()
    {
        $reservations = Reservation::where('statut', 'EN_ATTENTE')
            ->orderBy('dateArrivee', 'asc')
            ->get();
        return response()->json($reservations);
    }

    public function adminIndex(Request $request)
    {
        $query = Reservation::query()->orderBy('created_at', 'desc');

        if ($request->filled('statut') && $request->statut !== 'ALL') {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('clientId')) {
            $query->where('clientId', $request->clientId);
        }

        return response()->json($query->limit(200)->get());
    }

    public function confirmer($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update(['statut' => 'CONFIRMEE']);

        Paiement::where('reservationId', (string) $reservation->_id)
            ->update(['statut' => 'PAYE', 'transactionId' => 'TXN-' . uniqid()]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'RESERVATION_CONFIRMEE',
            'message' => "Votre réservation {$reservation->reference} est confirmée ! Bon séjour.",
            'estLue' => false,
        ]);

        return response()->json($reservation);
    }

    public function rejeter(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        if (!in_array($reservation->statut, ['EN_ATTENTE', 'CONFIRMEE'])) {
            return response()->json(['error' => 'Statut non modifiable'], 400);
        }

        $motif = (string) ($request->motif ?? '');
        $reservation->update(['statut' => 'REJETE']);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'RESERVATION_REJETEE',
            'message' => $motif !== ''
                ? "Votre réservation {$reservation->reference} a été refusée. Motif: {$motif}"
                : "Votre réservation {$reservation->reference} a été refusée.",
            'estLue' => false,
        ]);

        return response()->json($reservation);
    }

    public function checkin($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update(['statut' => 'EN_COURS', 'checkinAt' => now()]);

        Chambre::where('_id', $reservation->chambreId)->update(['estDisponible' => false]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'CHECKIN',
            'message' => "Bienvenue ! Votre check-in pour la réservation {$reservation->reference} est enregistré.",
            'estLue' => false,
        ]);

        return response()->json($reservation);
    }

    public function checkout($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update(['statut' => 'TERMINEE', 'checkoutAt' => now()]);

        Chambre::where('_id', $reservation->chambreId)->update(['estDisponible' => true]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'CHECKOUT',
            'message' => "Merci pour votre séjour ! Votre check-out est enregistré.",
            'estLue' => false,
        ]);

        return response()->json($reservation);
    }

    public function invoice($id)
    {
        $reservation = Reservation::findOrFail($id);
        $chambre = Chambre::find($reservation->chambreId);
        $hotel = Hotel::find($reservation->hotelId);
        $client = User::find($reservation->clientId);

        $pdf = Pdf::loadView('invoices.facture', compact('reservation', 'chambre', 'hotel', 'client'));
        return $pdf->download("facture-{$reservation->reference}.pdf");
    }

    public function show($id)
    {
        return response()->json(Reservation::findOrFail($id));
    }
}
