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
use Throwable;

class ReservationController extends Controller
{
    private function generateReference(): string
    {
        $year = date('Y');
        $rand = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return "R-{$year}-{$rand}";
    }

    private function presentReservation(Reservation $reservation): array
    {
        $hotel = Hotel::find($reservation->hotelId);
        $chambre = Chambre::find($reservation->chambreId);
        $client = User::find($reservation->clientId);
        $paiement = Paiement::where('reservationId', (string) $reservation->_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $row = $reservation->toArray();
        $row['hotel'] = $hotel;
        $row['chambre'] = $chambre;
        $row['paiement'] = $paiement ? [
            '_id' => (string) $paiement->_id,
            'statut' => $paiement->statut,
            'methode' => $paiement->methode,
            'montant' => (float) ($paiement->montant ?? 0),
        ] : null;
        $row['client'] = $client ? [
            '_id' => (string) $client->_id,
            'nom' => $client->nom,
            'prenom' => $client->prenom,
            'email' => $client->email,
            'role' => $client->role,
        ] : null;

        return $row;
    }

    private function dispatchMailSafely(string $email, object $mailable): void
    {
        try {
            Mail::to($email)->queue($mailable);
        } catch (Throwable) {
            // Fallback to sync sending if queue storage is unavailable.
            Mail::to($email)->send($mailable);
        }
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
        if ((int) $request->nbVoyageurs > (int) ($chambre->maxVoyageurs ?? 1)) {
            return response()->json(['error' => 'Le nombre de voyageurs depasse la capacite de la chambre'], 422);
        }

        $hasConflict = Reservation::where('chambreId', $request->chambreId)
            ->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE', 'EN_COURS'])
            ->where(function ($query) use ($request) {
                $query->where('dateArrivee', '<', $request->dateDepart)
                    ->where('dateDepart', '>', $request->dateArrivee);
            })
            ->exists();

        if ($hasConflict) {
            return response()->json(['error' => 'Cette chambre n est pas disponible pour ces dates'], 409);
        }

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

        $this->dispatchMailSafely($user->email, new BookingConfirmationMail($reservation, $chambre));

        return response()->json($this->presentReservation($reservation), 201);
    }

    public function myReservations()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $reservations = Reservation::where('clientId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations->map(fn (Reservation $reservation) => $this->presentReservation($reservation)));
    }

    public function index(Request $request)
    {
        $query = Reservation::query()->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->get()->map(fn (Reservation $reservation) => $this->presentReservation($reservation)));
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

        $this->dispatchMailSafely($user->email, new BookingCancelledMail($reservation));

        return response()->json($this->presentReservation($reservation));
    }

    public function reschedule(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $reservation = Reservation::findOrFail($id);

        if ((string) $reservation->clientId !== (string) $user->_id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        if ($reservation->statut !== 'CONFIRMEE') {
            return response()->json(['error' => 'Seules les réservations confirmées peuvent être modifiées'], 422);
        }

        $arrival = Carbon::parse($reservation->dateArrivee);
        if ($arrival->diffInHours(now(), false) > -48) {
            return response()->json(['error' => 'Modification possible uniquement plus de 48h avant l arrivée'], 422);
        }

        $request->validate([
            'dateArrivee' => 'required|date|after:today',
            'dateDepart' => 'required|date|after:dateArrivee',
        ]);

        $hasConflict = Reservation::where('chambreId', (string) $reservation->chambreId)
            ->where('_id', '!=', (string) $reservation->_id)
            ->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE', 'EN_COURS'])
            ->where(function ($query) use ($request) {
                $query->where('dateArrivee', '<', $request->dateDepart)
                    ->where('dateDepart', '>', $request->dateArrivee);
            })
            ->exists();

        if ($hasConflict) {
            return response()->json(['error' => 'La chambre n est pas disponible pour les nouvelles dates'], 409);
        }

        $chambre = Chambre::find($reservation->chambreId);
        $nights = Carbon::parse($request->dateArrivee)->diffInDays(Carbon::parse($request->dateDepart));
        $newTotal = (float) ($reservation->prixTotal ?? 0);

        if ($chambre && $nights > 0) {
            $baseRoomTotal = (float) ($chambre->prix_base ?? 0) * $nights;
            $servicesTotal = collect($reservation->servicesChoisis ?? [])->sum(function ($item) {
                return (float) ($item['prix'] ?? 0);
            });

            $discountPct = (float) ($reservation->remiseAppliquee ?? 0);
            $subtotal = $baseRoomTotal + $servicesTotal;
            $newTotal = $discountPct > 0 ? $subtotal * (1 - $discountPct / 100) : $subtotal;
        }

        $reservation->update([
            'dateArrivee' => $request->dateArrivee,
            'dateDepart' => $request->dateDepart,
            'prixTotal' => round($newTotal, 2),
        ]);

        Paiement::where('reservationId', (string) $reservation->_id)
            ->where('statut', '!=', 'PAYE')
            ->update(['montant' => round($newTotal, 2)]);

        Notification::create([
            'userId' => (string) $user->_id,
            'type' => 'RESERVATION_MODIFIEE',
            'message' => "Votre réservation {$reservation->reference} a été modifiée.",
            'estLue' => false,
        ]);

        return response()->json($this->presentReservation($reservation));
    }

    public function pending()
    {
        $reservations = Reservation::where('statut', 'EN_ATTENTE')
            ->orderBy('dateArrivee', 'asc')
            ->get();
        return response()->json($reservations->map(fn (Reservation $reservation) => $this->presentReservation($reservation)));
    }

    public function specialRequests()
    {
        $reservations = Reservation::where(function ($query) {
            $query->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE', 'EN_COURS'])
                ->where('demandesSpeciales', '!=', '')
                ->orWhere('specialRequestStatus', 'TRAITE');
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reservations->map(function (Reservation $reservation) {
            $presented = $this->presentReservation($reservation);
            $specialText = (string) $reservation->demandesSpeciales;
            $status = (string) ($reservation->specialRequestStatus ?? 'EN_ATTENTE');
            $priority = 'NORMAL';
            $normalized = strtolower($specialText);

            if (preg_match('/\b(urgent|emergency|medical|sos)\b/u', $normalized)) {
                $priority = 'URGENT';
            } elseif (preg_match('/\b(light|minor|basic|simple)\b/u', $normalized)) {
                $priority = 'LOW';
            }

            return [
                'id' => (string) $reservation->_id,
                'client' => trim(($presented['client']['prenom'] ?? '') . ' ' . ($presented['client']['nom'] ?? '')) ?: (string) $reservation->clientId,
                'chambre' => $presented['chambre']['numero'] ?? $presented['chambre']['nom'] ?? (string) $reservation->chambreId,
                'hotel' => $presented['hotel']['nom'] ?? (string) $reservation->hotelId,
                'demande' => $specialText,
                'heure' => optional($reservation->created_at)->format('H:i') ?? now()->format('H:i'),
                'arrivee' => $reservation->dateArrivee,
                'depart' => $reservation->dateDepart,
                'status' => $status,
                'priority' => $priority,
                'urgent' => $priority === 'URGENT',
                'roomNumber' => $presented['chambre']['numero'] ?? $presented['chambre']['nom'] ?? null,
                'details' => $specialText,
            ];
        }));
    }

    public function markSpecialRequestDone($id)
    {
        $reservation = Reservation::findOrFail($id);

        $reservation->update([
            'specialRequestStatus' => 'TRAITE',
            'specialRequestHandledAt' => now(),
        ]);

        return response()->json([
            'message' => 'Demande speciale marquee comme traitee',
            'reservationId' => (string) $reservation->_id,
        ]);
    }

    public function checkinToday()
    {
        $today = Carbon::today();

        return response()->json(
            Reservation::whereIn('statut', ['CONFIRMEE', 'EN_COURS'])
                ->whereDate('dateArrivee', $today)
                ->orderBy('dateArrivee', 'asc')
                ->get()
                ->map(fn (Reservation $reservation) => $this->presentReservation($reservation))
        );
    }

    public function checkinSearch(Request $request)
    {
        $search = trim(mb_strtolower((string) $request->input('q', '')));

        if ($search === '') {
            return response()->json([]);
        }

        $results = Reservation::orderBy('created_at', 'desc')
            ->get()
            ->filter(function (Reservation $reservation) use ($search) {
                $presented = $this->presentReservation($reservation);
                $clientName = trim((string) (($presented['client']['prenom'] ?? '') . ' ' . ($presented['client']['nom'] ?? '')));
                $clientEmail = mb_strtolower((string) ($presented['client']['email'] ?? ''));
                $reference = mb_strtolower((string) ($reservation->reference ?? ''));

                return str_contains(mb_strtolower($clientName), $search)
                    || str_contains($clientEmail, $search)
                    || str_contains($reference, $search);
            })
            ->take(10)
            ->map(fn (Reservation $reservation) => $this->presentReservation($reservation))
            ->values();

        return response()->json($results);
    }

    public function confirmer($id)
    {
        $reservation = Reservation::findOrFail($id);
        if (!in_array($reservation->statut, ['EN_ATTENTE', 'REJETE'])) {
            return response()->json(['error' => 'Transition de statut invalide'], 422);
        }

        $reservation->update(['statut' => 'CONFIRMEE']);

        Paiement::where('reservationId', (string) $reservation->_id)
            ->update(['statut' => 'PAYE', 'transactionId' => 'TXN-' . uniqid()]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'RESERVATION_CONFIRMEE',
            'message' => "Votre réservation {$reservation->reference} est confirmée ! Bon séjour.",
            'estLue' => false,
        ]);

        return response()->json($this->presentReservation($reservation));
    }

    public function rejeter(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        if (!in_array($reservation->statut, ['EN_ATTENTE', 'CONFIRMEE'])) {
            return response()->json(['error' => 'Transition de statut invalide'], 422);
        }

        $request->validate(['motif' => 'nullable|string|max:500']);

        $reservation->update([
            'statut' => 'REJETE',
            'motifRejet' => $request->motif ?? '',
        ]);

        Paiement::where('reservationId', (string) $reservation->_id)
            ->where('statut', '!=', 'PAYE')
            ->update(['statut' => 'ANNULE']);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'RESERVATION_REJETEE',
            'message' => "Votre réservation {$reservation->reference} a été rejetée.",
            'estLue' => false,
        ]);

        return response()->json($this->presentReservation($reservation));
    }

    public function checkin($id)
    {
        $reservation = Reservation::findOrFail($id);
        if ($reservation->statut !== 'CONFIRMEE') {
            return response()->json(['error' => 'Check-in non autorise pour ce statut'], 422);
        }

        $reservation->update(['statut' => 'EN_COURS', 'checkinAt' => now()]);

        Chambre::where('_id', $reservation->chambreId)->update(['estDisponible' => false]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'CHECKIN',
            'message' => "Bienvenue ! Votre check-in pour la réservation {$reservation->reference} est enregistré.",
            'estLue' => false,
        ]);

        return response()->json($this->presentReservation($reservation));
    }

    public function checkout($id)
    {
        $reservation = Reservation::findOrFail($id);
        if ($reservation->statut !== 'EN_COURS') {
            return response()->json(['error' => 'Check-out non autorise pour ce statut'], 422);
        }

        $reservation->update(['statut' => 'TERMINEE', 'checkoutAt' => now()]);

        Chambre::where('_id', $reservation->chambreId)->update(['estDisponible' => true]);

        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'CHECKOUT',
            'message' => "Merci pour votre séjour ! Votre check-out est enregistré.",
            'estLue' => false,
        ]);

        return response()->json($this->presentReservation($reservation));
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
        return response()->json($this->presentReservation(Reservation::findOrFail($id)));
    }
}
