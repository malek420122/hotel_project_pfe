<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\ClientSignal;
use App\Models\Incident;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class IncidentController extends Controller
{
    public function store(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'room' => 'required|string',
            'type' => 'required|in:propreté,maintenance,sécurité,bruit,autre',
            'severity' => 'required|in:basse,moyenne,haute',
            'description' => 'required|string|min:5',
            'source' => 'nullable|in:client,reception,menage,appel',
            'signalId' => 'nullable|string',
            'clientSignalId' => 'nullable|string',
            'assignedTo' => 'nullable|string',
        ]);

        $signalId = (string) ($data['signalId'] ?? $data['clientSignalId'] ?? '');
        $source = $signalId !== '' ? 'client' : (string) ($data['source'] ?? 'reception');

        $incident = Incident::create([
            'room' => trim((string) $data['room']),
            'type' => (string) $data['type'],
            'severity' => (string) $data['severity'],
            'description' => trim((string) $data['description']),
            'status' => 'ouvert',
            'source' => $source,
            'signalId' => $signalId ?: null,
            'reportedBy' => $this->modelId($user),
            'assignedTo' => $data['assignedTo'] ?? null,
            'reportedAt' => now(),
            'resolvedAt' => null,
            'resolvedBy' => null,
        ]);

        if ($signalId !== '') {
            $signal = ClientSignal::find($signalId);
            if ($signal) {
                $signal->update([
                    'status' => 'traité',
                    'incidentId' => $this->modelId($incident),
                    'createdIncidentId' => $this->modelId($incident),
                    'processedAt' => now(),
                    'processedBy' => $this->modelId($user),
                ]);

                $clientId = (string) ($signal->userId ?? $signal->clientId ?? '');
                if ($clientId !== '') {
                    $this->notify($clientId, 'Votre problème est pris en charge', 'INCIDENT_PRISE_EN_CHARGE');
                }
            }
        }

        return response()->json($this->presentIncident($incident), 201);
    }

    public function index(Request $request)
    {
        $query = Incident::query();

        if ($request->filled('status')) {
            $query->where('status', $this->normalizeStatus((string) $request->input('status')));
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', (string) $request->input('severity'));
        }

        if ($request->filled('room')) {
            $query->where('room', (string) $request->input('room'));
        }

        if ($request->filled('source')) {
            $query->where('source', (string) $request->input('source'));
        }

        $rows = $query->orderBy('reportedAt', 'desc')->get();

        return response()->json($rows->map(fn (Incident $incident) => $this->presentIncident($incident))->values());
    }

    public function myIncidents()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = $this->modelId($user);

        $signalIds = ClientSignal::query()
            ->where(function ($query) use ($userId) {
                $query->where('userId', $userId)->orWhere('clientId', $userId);
            })
            ->get()
            ->map(fn (ClientSignal $signal) => $this->modelId($signal))
            ->filter()
            ->values()
            ->all();

        if (empty($signalIds)) {
            return response()->json([]);
        }

        $incidents = Incident::query()
            ->whereIn('signalId', $signalIds)
            ->orderBy('reportedAt', 'desc')
            ->get();

        return response()->json($incidents->map(fn (Incident $incident) => $this->presentIncident($incident, true))->values());
    }

    public function updateStatus(Request $request, $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'status' => 'required|in:ouvert,en_cours,en cours,résolu',
        ]);

        $incident = Incident::findOrFail($id);
        $oldStatus = $this->normalizeStatus((string) ($incident->status ?? 'ouvert'));
        $status = $this->normalizeStatus((string) $data['status']);

        $payload = ['status' => $status];

        if ($status === 'résolu') {
            $payload['resolvedAt'] = now();
            $payload['resolvedBy'] = $this->modelId($user);
        } else {
            $payload['resolvedAt'] = null;
            $payload['resolvedBy'] = null;
        }

        $incident->update($payload);
        $fresh = $incident->fresh();

        if ($status === 'résolu' && $oldStatus !== 'résolu') {
            $clientId = $this->clientIdForIncident($fresh);
            if ($clientId !== '') {
                $this->notify($clientId, 'Votre problème a été résolu', 'INCIDENT_RESOLU');
            }
        }

        return response()->json($this->presentIncident($fresh));
    }

    public function stats()
    {
        $all = Incident::query()->get();
        $statuses = [
            'ouvert' => 0,
            'en_cours' => 0,
            'résolu' => 0,
        ];
        $byType = [
            'propreté' => 0,
            'maintenance' => 0,
            'sécurité' => 0,
            'bruit' => 0,
            'autre' => 0,
        ];
        $resolutionBuckets = [
            'basse' => [],
            'moyenne' => [],
            'haute' => [],
        ];

        foreach ($all as $incident) {
            $status = $this->normalizeStatus((string) ($incident->status ?? 'ouvert'));
            if (array_key_exists($status, $statuses)) {
                $statuses[$status]++;
            }

            $type = (string) ($incident->type ?? 'autre');
            if (! array_key_exists($type, $byType)) {
                $type = 'autre';
            }
            $byType[$type]++;

            $severity = (string) ($incident->severity ?? 'moyenne');
            if (! array_key_exists($severity, $resolutionBuckets)) {
                $severity = 'moyenne';
            }

            if ($status === 'résolu' && $incident->reportedAt && $incident->resolvedAt) {
                $reported = Carbon::parse($incident->reportedAt);
                $resolved = Carbon::parse($incident->resolvedAt);
                $resolutionBuckets[$severity][] = max(0, $reported->diffInMinutes($resolved));
            }
        }

        $avgResolutionBySeverity = [];
        foreach ($resolutionBuckets as $severity => $minutes) {
            $avgResolutionBySeverity[$severity] = count($minutes)
                ? round(array_sum($minutes) / count($minutes), 2)
                : null;
        }

        $byDay = [];
        $start = now()->startOfDay()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $byDay[$day] = 0;
        }

        foreach ($all as $incident) {
            if (! $incident->reportedAt) {
                continue;
            }

            $day = Carbon::parse($incident->reportedAt)->toDateString();
            if (array_key_exists($day, $byDay)) {
                $byDay[$day]++;
            }
        }

        return response()->json([
            'total' => $all->count(),
            'statuses' => $statuses,
            'byStatus' => $statuses,
            'ouverts' => $statuses['ouvert'],
            'enCours' => $statuses['en_cours'],
            'resolus' => $statuses['résolu'],
            'byType' => $byType,
            'parType' => $byType,
            'avgResolutionBySeverity' => $avgResolutionBySeverity,
            'resolutionMoyenneParGravite' => $avgResolutionBySeverity,
            'byDay' => array_map(fn ($date, $count) => ['date' => $date, 'count' => $count], array_keys($byDay), array_values($byDay)),
            'parJour30Jours' => $byDay,
        ]);
    }

    private function presentIncident(Incident $incident, bool $forClient = false): array
    {
        $room = Chambre::find((string) ($incident->room ?? ''));
        $reportedBy = User::find((string) ($incident->reportedBy ?? ''));
        $assignedTo = User::find((string) ($incident->assignedTo ?? ''));
        $resolvedBy = User::find((string) ($incident->resolvedBy ?? ''));
        $status = $this->normalizeStatus((string) ($incident->status ?? 'ouvert'));

        return [
            '_id' => (string) ($incident->_id ?? ''),
            'room' => [
                '_id' => (string) ($room->_id ?? $incident->room ?? ''),
                'number' => $this->roomNumber($room),
                'name' => (string) ($room->nom ?? ''),
            ],
            'type' => (string) ($incident->type ?? 'autre'),
            'severity' => (string) ($incident->severity ?? 'moyenne'),
            'description' => (string) ($incident->description ?? ''),
            'status' => $status,
            'clientStatus' => $forClient ? $this->clientStatus($status) : null,
            'source' => (string) ($incident->source ?? 'reception'),
            'signalId' => (string) ($incident->signalId ?? ''),
            'reportedBy' => [
                '_id' => (string) ($reportedBy->_id ?? $incident->reportedBy ?? ''),
                'name' => trim(((string) ($reportedBy->prenom ?? '')) . ' ' . ((string) ($reportedBy->nom ?? ''))),
            ],
            'assignedTo' => [
                '_id' => (string) ($assignedTo->_id ?? $incident->assignedTo ?? ''),
                'name' => trim(((string) ($assignedTo->prenom ?? '')) . ' ' . ((string) ($assignedTo->nom ?? ''))),
            ],
            'reportedAt' => $incident->reportedAt,
            'resolvedAt' => $incident->resolvedAt,
            'resolvedBy' => [
                '_id' => (string) ($resolvedBy->_id ?? $incident->resolvedBy ?? ''),
                'name' => trim(((string) ($resolvedBy->prenom ?? '')) . ' ' . ((string) ($resolvedBy->nom ?? ''))),
            ],
        ];
    }

    private function clientIdForIncident(Incident $incident): string
    {
        $signalId = (string) ($incident->signalId ?? '');
        if ($signalId === '') {
            return '';
        }

        $signal = ClientSignal::find($signalId);
        return $signal ? (string) ($signal->userId ?? $signal->clientId ?? '') : '';
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

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'en cours' => 'en_cours',
            default => $status,
        };
    }

    private function clientStatus(string $status): string
    {
        return match ($status) {
            'ouvert' => 'en attente',
            'en_cours' => 'en cours',
            'résolu' => 'résolu',
            default => 'en attente',
        };
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
