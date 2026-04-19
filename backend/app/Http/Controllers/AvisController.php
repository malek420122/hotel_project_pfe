<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvisController extends Controller
{
    public function reviewable()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $reservations = Reservation::where('clientId', (string) $user->_id)
            ->whereIn('statut', ['CHECKOUT', 'TERMINEE'])
            ->orderBy('created_at', 'desc')
            ->get();

        $reviewedReservationIds = Avis::where('clientId', (string) $user->_id)
            ->pluck('reservationId')
            ->map(fn ($value) => (string) $value)
            ->all();

        $reviewedLookup = array_flip($reviewedReservationIds);

        $payload = $reservations
            ->reject(fn (Reservation $reservation) => isset($reviewedLookup[(string) $reservation->_id]))
            ->map(function (Reservation $reservation) {
                $hotel = Hotel::find($reservation->hotelId);
                $chambre = Chambre::find($reservation->chambreId);

                return [
                    '_id' => (string) $reservation->_id,
                    'reference' => (string) ($reservation->reference ?? ''),
                    'hotelId' => (string) ($reservation->hotelId ?? ''),
                    'chambreId' => (string) ($reservation->chambreId ?? ''),
                    'dateArrivee' => $reservation->dateArrivee,
                    'dateDepart' => $reservation->dateDepart,
                    'statut' => (string) ($reservation->statut ?? ''),
                    'prixTotal' => (float) ($reservation->prixTotal ?? 0),
                    'hotel' => $hotel ? [
                        '_id' => (string) $hotel->_id,
                        'nom' => (string) ($hotel->nom ?? ''),
                        'ville' => $hotel->ville,
                        'etoiles' => (int) ($hotel->etoiles ?? 0),
                        'photos' => $hotel->photos,
                        'previewPhoto' => $hotel->previewPhoto,
                    ] : null,
                    'chambre' => $chambre ? [
                        '_id' => (string) $chambre->_id,
                        'nom' => (string) ($chambre->nom ?? ''),
                        'type' => (string) ($chambre->type ?? ''),
                        'prix_base' => (float) ($chambre->prix_base ?? 0),
                        'maxVoyageurs' => (int) ($chambre->maxVoyageurs ?? 1),
                    ] : null,
                ];
            })
            ->values();

        return response()->json($payload);
    }

    public function myReviews()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $reviews = Avis::where('clientId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Avis $avis) {
                $hotel = Hotel::find($avis->hotelId);
                $reservation = Reservation::find($avis->reservationId);
                $chambre = $reservation ? Chambre::find($reservation->chambreId) : null;
                $marketingReply = (string) ($avis->reponse_marketing ?? $avis->reponseHotel ?? '');

                return [
                    '_id' => (string) $avis->_id,
                    'hotel_id' => (string) ($avis->hotelId ?? ''),
                    'reservation_id' => (string) ($avis->reservationId ?? ''),
                    'note' => (int) ($avis->note ?? 0),
                    'commentaire' => (string) ($avis->commentaire ?? ''),
                    'statut' => (string) ($avis->statut ?? 'PUBLIE'),
                    'reponseHotel' => $marketingReply,
                    'reponse_marketing' => $marketingReply,
                    'created_at' => $avis->created_at,
                    'hotel' => $hotel ? [
                        '_id' => (string) $hotel->_id,
                        'nom' => $hotel->nom,
                        'ville' => $hotel->ville,
                        'etoiles' => (int) ($hotel->etoiles ?? 0),
                        'photos' => $hotel->photos,
                        'previewPhoto' => $hotel->previewPhoto,
                    ] : null,
                    'reservation' => $reservation ? [
                        '_id' => (string) $reservation->_id,
                        'reference' => (string) ($reservation->reference ?? ''),
                        'dateArrivee' => $reservation->dateArrivee,
                        'dateDepart' => $reservation->dateDepart,
                        'statut' => $reservation->statut,
                        'chambre' => $chambre ? [
                            '_id' => (string) $chambre->_id,
                            'type' => (string) ($chambre->type ?? ''),
                            'nom' => (string) ($chambre->nom ?? ''),
                        ] : null,
                    ] : null,
                ];
            })
            ->values();

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $hotelId = (string) ($request->input('hotel_id') ?? $request->input('hotelId') ?? '');
        $reservationId = (string) ($request->input('reservation_id') ?? $request->input('reservationId') ?? '');

        $request->validate([
            'note' => 'required|integer|between:1,5',
            'commentaire' => 'required|string|min:10',
        ]);

        if ($hotelId === '' || $reservationId === '') {
            return response()->json([
                'message' => 'hotel_id et reservation_id sont requis.',
            ], 422);
        }

        $reservation = Reservation::find($reservationId);
        if (! $reservation || (string) $reservation->clientId !== (string) $user->_id) {
            return response()->json(['message' => 'Séjour introuvable pour cet utilisateur.'], 404);
        }

        $hotelIdFromReservation = (string) ($reservation->hotelId ?? '');
        if ($hotelIdFromReservation === '') {
            return response()->json(['message' => 'Séjour invalide: hôtel introuvable.'], 422);
        }
        $hotelId = $hotelIdFromReservation;

        $status = strtoupper((string) ($reservation->statut ?? ''));
        $isCompletedStatus = in_array($status, ['CHECKOUT', 'TERMINEE'], true);

        if (! $isCompletedStatus) {
            return response()->json(['message' => 'Vous pouvez noter uniquement un séjour terminé.'], 422);
        }

        $alreadyReviewed = Avis::where('clientId', (string) $user->_id)
            ->where('reservationId', (string) $reservation->_id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json(['message' => 'Un avis existe déjà pour ce séjour.'], 409);
        }

        $avis = Avis::create([
            'clientId' => (string) $user->_id,
            'hotelId' => $hotelId,
            'reservationId' => $reservationId,
            'note' => $request->note,
            'commentaire' => $request->commentaire,
            'statut' => 'PUBLIE',
        ]);

        $hotel = Hotel::find($hotelId);
        if ($hotel) {
            $this->recalculateHotelRating($hotelId);
        }

        User::where('_id', (string) $user->_id)->increment('points_fidelite', 10);

        Notification::create([
            'userId' => (string) $user->_id,
            'type' => 'LOYALTY',
            'message' => 'Merci pour votre avis ! +10 points de fidélité ont été ajoutés à votre compte.',
            'estLue' => false,
        ]);

        return response()->json([
            '_id' => (string) $avis->_id,
            'hotel_id' => (string) $avis->hotelId,
            'reservation_id' => (string) $avis->reservationId,
            'note' => (int) $avis->note,
            'commentaire' => (string) $avis->commentaire,
            'statut' => (string) $avis->statut,
            'reponseHotel' => (string) ($avis->reponseHotel ?? ''),
            'created_at' => $avis->created_at,
        ], 201);
    }

    public function moderation(Request $request)
    {
        $statusFilter = $this->normalizeReviewStatus((string) ($request->query('status') ?? $request->query('statut') ?? $request->query('filter') ?? 'pending'));

        $query = Avis::query();
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->whereIn('statut', $this->storageStatusesFromNormalized($statusFilter));
        }

        $rows = $query
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Avis $avis) => $this->presentModerationReview($avis))
            ->values();

        return response()->json([
            'data' => $rows,
            'counts' => [
                'pending' => (int) Avis::query()->whereIn('statut', $this->storageStatusesFromNormalized('pending'))->count(),
                'published' => (int) Avis::query()->whereIn('statut', $this->storageStatusesFromNormalized('published'))->count(),
                'rejected' => (int) Avis::query()->whereIn('statut', $this->storageStatusesFromNormalized('rejected'))->count(),
            ],
        ]);
    }

    public function approve(string $id)
    {
        $avis = Avis::findOrFail($id);
        $avis->update(['statut' => 'PUBLIE']);
        $this->recalculateHotelRating((string) $avis->hotelId);

        return response()->json([
            'message' => 'Review approved',
            'review' => $this->presentModerationReview($avis->fresh()),
        ]);
    }

    public function reject(string $id)
    {
        $avis = Avis::findOrFail($id);
        $avis->update(['statut' => 'REJETE']);

        return response()->json([
            'message' => 'Review rejected',
            'review' => $this->presentModerationReview($avis->fresh()),
        ]);
    }

    public function reply(Request $request, string $id)
    {
        $request->validate([
            'reponse' => 'required|string|min:2',
        ]);

        $avis = Avis::findOrFail($id);
        $reply = trim((string) $request->input('reponse'));
        $avis->update([
            'reponse_marketing' => $reply,
            'reponseHotel' => $reply,
        ]);

        return response()->json([
            'message' => 'Reply saved',
            'review' => $this->presentModerationReview($avis->fresh()),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $statusFilter = $this->normalizeReviewStatus((string) ($request->query('status') ?? $request->query('statut') ?? $request->query('filter') ?? 'all'));

        $query = Avis::query();
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $query->whereIn('statut', $this->storageStatusesFromNormalized($statusFilter));
        }

        $rows = $query->orderBy('created_at', 'desc')->get();

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['client', 'hotel', 'note', 'commentaire', 'date']);

            foreach ($rows as $avis) {
                $payload = $this->presentModerationReview($avis);
                fputcsv($handle, [
                    (string) ($payload['client_nom'] ?? ''),
                    (string) ($payload['hotel_nom'] ?? ''),
                    (string) ($payload['note'] ?? ''),
                    (string) ($payload['commentaire'] ?? ''),
                    $this->formatCsvDate($payload['created_at'] ?? null),
                ]);
            }

            fclose($handle);
        };

        $fileName = 'marketing-reviews-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function moderer(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:PUBLIE,REJETE,published,rejected',
            'reponse' => 'nullable|string',
        ]);

        $action = strtoupper((string) $request->input('action'));
        if ($action === 'PUBLISHED') {
            return $this->approve((string) $id);
        }
        if ($action === 'REJECTED') {
            return $this->reject((string) $id);
        }

        $avis = Avis::findOrFail($id);
        $reply = trim((string) $request->input('reponse', ''));
        $avis->update([
            'statut' => $action,
            'reponse_marketing' => $reply,
            'reponseHotel' => $reply,
        ]);

        if ($action === 'PUBLIE') {
            $this->recalculateHotelRating((string) $avis->hotelId);
        }

        return response()->json([
            'message' => 'Review updated',
            'review' => $this->presentModerationReview($avis->fresh()),
        ]);
    }

    private function normalizeReviewStatus(string $status): string
    {
        $value = strtolower(trim($status));
        return match ($value) {
            'en_attente', 'pending' => 'pending',
            'publie', 'publié', 'published', 'approved' => 'published',
            'rejete', 'rejeté', 'rejected' => 'rejected',
            'all', '' => 'all',
            default => 'pending',
        };
    }

    private function storageStatusesFromNormalized(string $status): array
    {
        return match ($status) {
            'pending' => ['EN_ATTENTE', 'PENDING'],
            'published' => ['PUBLIE', 'PUBLISHED', 'APPROVED'],
            'rejected' => ['REJETE', 'REJECTED'],
            default => ['EN_ATTENTE', 'PENDING'],
        };
    }

    private function normalizedStatusFromStorage(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'EN_ATTENTE', 'PENDING' => 'pending',
            'PUBLIE', 'PUBLISHED', 'APPROVED' => 'published',
            'REJETE', 'REJECTED' => 'rejected',
            default => 'pending',
        };
    }

    private function presentModerationReview(Avis $avis): array
    {
        $client = User::find((string) $avis->clientId);
        $hotel = Hotel::find((string) $avis->hotelId);
        $status = $this->normalizedStatusFromStorage((string) ($avis->statut ?? 'EN_ATTENTE'));
        $first = strtoupper(substr((string) ($client?->prenom ?? ''), 0, 1));
        $last = strtoupper(substr((string) ($client?->nom ?? ''), 0, 1));

        return [
            '_id' => (string) $avis->_id,
            'note' => (int) ($avis->note ?? 0),
            'commentaire' => (string) ($avis->commentaire ?? ''),
            'statut' => $status,
            'created_at' => $avis->created_at,
            'reponse_marketing' => (string) ($avis->reponse_marketing ?? $avis->reponseHotel ?? ''),
            'client_nom' => trim((string) (($client?->prenom ?? '') . ' ' . ($client?->nom ?? ''))) ?: 'Client',
            'client_email' => (string) ($client?->email ?? ''),
            'client_initiales' => trim($first . $last) ?: 'CL',
            'hotel_nom' => (string) ($hotel?->nom ?? 'Hotel'),
            'hotel_ville' => $this->stringifyHotelCity($hotel?->ville ?? ''),
            'hotel_id' => (string) ($hotel?->_id ?? $avis->hotelId ?? ''),
        ];
    }

    private function stringifyHotelCity(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            foreach (['fr', 'en', 'ar'] as $lang) {
                if (! empty($value[$lang]) && is_scalar($value[$lang])) {
                    return (string) $value[$lang];
                }
            }

            foreach ($value as $item) {
                if (is_scalar($item) && $item !== '') {
                    return (string) $item;
                }
            }
        }

        if (is_object($value)) {
            $asArray = (array) $value;
            return $this->stringifyHotelCity($asArray);
        }

        return '';
    }

    private function formatCsvDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d H:i:s');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return $value;
            }
        }

        return '';
    }

    private function recalculateHotelRating(string $hotelId): void
    {
        $hotel = Hotel::find($hotelId);
        if (! $hotel) {
            return;
        }

        $avgNote = Avis::where('hotelId', $hotelId)
            ->where('statut', 'PUBLIE')
            ->whereBetween('note', [1, 5])
            ->avg('note');

        $rounded = round((float) $avgNote, 1);
        $bounded = max(1, min(5, $rounded));

        $hotel->update(['noteMoyenne' => $bounded]);
    }
}
