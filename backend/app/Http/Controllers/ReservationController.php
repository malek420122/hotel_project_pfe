<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Paiement;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\User;
use App\Mail\BookingConfirmationMail;
use App\Mail\BookingCancelledMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReservationController extends Controller
{
    private function generateReference(): string
    {
        $year = date('Y');
        $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "R-{$year}-{$rand}";
    }

    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'chambreId' => 'required|string',
            'hotelId' => 'required|string',
            'dateArrivee' => 'required|date',
            'dateDepart' => 'required|date|after:dateArrivee',
            'nbVoyageurs' => 'required|integer|min:1',
        ]);

        $chambre = Chambre::findOrFail($request->chambreId);
        $nuits = Carbon::parse($request->dateArrivee)->diffInDays(Carbon::parse($request->dateDepart));
        $prixTotal = $chambre->prix_base * $nuits;

        $remise = 0;
        if ($request->has('codePromo')) {
            $promo = Promotion::where('codePromo', $request->codePromo)
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
        if ($request->has('servicesChoisis') && is_array($request->servicesChoisis)) {
            foreach ($request->servicesChoisis as $service) {
                $servicesPrix += $service['prix'] ?? 0;
            }
            $prixTotal += $servicesPrix;
        }

        $reservation = Reservation::create([
            'reference' => $this->generateReference(),
            'clientId' => (string) $user->_id,
            'chambreId' => $request->chambreId,
            'hotelId' => $request->hotelId,
            'dateArrivee' => $request->dateArrivee,
            'dateDepart' => $request->dateDepart,
            'nbVoyageurs' => $request->nbVoyageurs,
            'prixTotal' => round($prixTotal, 2),
            'demandesSpeciales' => $request->demandesSpeciales ?? '',
            'servicesChoisis' => $request->servicesChoisis ?? [],
            'codePromoApplique' => $request->codePromo ?? '',
            'remiseAppliquee' => $remise,
        ]);

        Paiement::create([
            'reservationId' => (string) $reservation->_id,
            'montant' => $reservation->prixTotal,
            'statut' => 'EN_COURS',
            'methode' => $request->methodePaiement ?? 'carte',
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

        Mail::to($user->email)->queue(new BookingConfirmationMail($reservation, $chambre));

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

        Mail::to($user->email)->queue(new BookingCancelledMail($reservation));

        return response()->json($reservation);
    }

    public function pending()
    {
        $reservations = Reservation::where('statut', 'EN_ATTENTE')
            ->orderBy('dateArrivee', 'asc')
            ->get();
        return response()->json($reservations);
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
