<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\ClientSignal;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClientSignalController extends Controller
{
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ((string) ($user->role ?? '') !== 'client') {
            return response()->json(['message' => 'Seul un client peut envoyer un signalement simple.'], 403);
        }

        $data = $request->validate([
            'message' => 'required|string|min:5|max:500',
        ]);

        $activeReservation = Reservation::query()
            ->where('clientId', $this->modelId($user))
            ->where('statut', 'EN_COURS')
            ->whereNotNull('checkinAt')
            ->whereNull('checkoutAt')
            ->orderBy('checkinAt', 'desc')
            ->first();

        if (! $activeReservation || empty($activeReservation->chambreId)) {
            return response()->json([
                'message' => 'Aucune réservation active avec chambre attribuée n’a été trouvée.',
            ], 422);
        }

        $clientName = trim(((string) ($user->prenom ?? '')) . ' ' . ((string) ($user->nom ?? '')));

        $signal = ClientSignal::create([
            'userId' => $this->modelId($user),
            'clientId' => $this->modelId($user),
            'clientName' => $clientName,
            'room' => (string) $activeReservation->chambreId,
            'reservationId' => $this->modelId($activeReservation),
            'message' => trim((string) $data['message']),
            'status' => 'en_attente',
            'incidentId' => null,
            'createdIncidentId' => null,
            'createdAt' => now(),
            'processedAt' => null,
            'processedBy' => null,
        ]);

        $room = Chambre::find((string) $activeReservation->chambreId);
        $roomLabel = $this->roomNumber($room);

        $receptionUsers = User::query()
            ->where('role', 'receptionniste')
            ->get();

        foreach ($receptionUsers as $receptionUser) {
            $this->notify(
                $this->modelId($receptionUser),
                "Nouveau signalement client en chambre {$roomLabel} par {$clientName}.",
                'CLIENT_SIGNAL'
            );
        }

        return response()->json($this->presentSignal($signal), 201);
    }

    public function pending()
    {
        $signals = ClientSignal::query()
            ->whereIn('status', ['en_attente', 'pending'])
            ->orderBy('createdAt', 'asc')
            ->get();

        return response()->json($signals->map(fn (ClientSignal $signal) => $this->presentSignal($signal))->values());
    }

    public function mySignals()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $this->modelId($user);

        $signals = ClientSignal::query()
            ->where(function ($query) use ($userId) {
                $query->where('userId', $userId)->orWhere('clientId', $userId);
            })
            ->orderBy('createdAt', 'desc')
            ->get();

        return response()->json($signals->map(fn (ClientSignal $signal) => $this->presentSignal($signal))->values());
    }

    private function presentSignal(ClientSignal $signal): array
    {
        $room = Chambre::find((string) ($signal->room ?? ''));
        $client = User::find((string) ($signal->userId ?? $signal->clientId ?? ''));
        $status = (string) ($signal->status ?? 'en_attente');
        if ($status === 'pending') {
            $status = 'en_attente';
        } elseif ($status === 'processed') {
            $status = 'traité';
        }

        return [
            '_id' => (string) ($signal->_id ?? ''),
            'message' => (string) ($signal->message ?? ''),
            'status' => $status,
            'incidentId' => (string) ($signal->incidentId ?? $signal->createdIncidentId ?? ''),
            'createdAt' => $signal->createdAt ?? $signal->created_at,
            'processedAt' => $signal->processedAt,
            'room' => [
                '_id' => (string) ($room->_id ?? $signal->room ?? ''),
                'number' => $this->roomNumber($room),
                'name' => (string) ($room->nom ?? ''),
            ],
            'client' => [
                '_id' => (string) ($client->_id ?? $signal->userId ?? $signal->clientId ?? ''),
                'name' => trim(((string) ($client->prenom ?? '')) . ' ' . ((string) ($client->nom ?? ''))) ?: (string) ($signal->clientName ?? ''),
                'email' => (string) ($client->email ?? ''),
            ],
        ];
    }

    private function notify(string $userId, string $message, string $type): void
    {
        Notification::create([
            'userId' => $userId,
            'type' => $type,
            'message' => $message,
            'vu' => false,
            'estLue' => false,
            'createdAt' => now(),
        ]);
    }

    private function modelId($model): string
    {
        return (string) ($model->_id ?? $model->id ?? $model->getKey());
    }

    private function roomNumber(?Chambre $room): string
    {
        if (! $room) {
            return '-';
        }

        foreach ([$room->numero ?? null, $room->room_number ?? null, $room->nom ?? null] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return strtoupper(substr((string) ($room->_id ?? ''), -4));
    }
}
