<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'history' => 'sometimes|array|max:8',
            'history.*.role' => 'required_with:history|string|in:user,assistant,bot',
            'history.*.text' => 'required_with:history|string|max:1000',
        ]);

        $user = auth()->user();

        if (! $user) {
            return response()->json(['reply' => 'Unauthorized.'], 401);
        }

        $reservations = Reservation::where('clientId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        $context = 'Tu es Mimi, l assistant virtuel pour HotelEase, une plateforme de réservation hôtelière premium. ';
        $context .= 'Tu détectes automatiquement la langue du message de l utilisateur et tu réponds uniquement dans cette même langue. ';
        $context .= 'Ne mélange jamais plusieurs langues dans une même réponse. ';
        $context .= 'Réponds en français si le message est en français, en anglais si le message est en anglais, et en arabe si le message est en arabe. ';
        $context .= 'Sois chaleureux, naturel, clair et concis (max 3 phrases). ';
        $context .= 'N ouvre pas chaque réponse avec un nouveau bonjour si la conversation est déjà en cours. ';
        $context .= 'Ne répète pas l historique du client ni ses réservations sauf s il le demande explicitement. ';
        $context .= 'Si tu recommandes des hotels, donne des suggestions réalistes et pertinentes sans inventer de disponibilité. ';

        if ($reservations->isNotEmpty()) {
            $context .= 'Réservations récentes du client: ';

            foreach ($reservations as $reservation) {
                $hotel = $reservation->hotelId ? Hotel::find((string) $reservation->hotelId) : null;
                $hotelName = $this->formatHotelName($hotel?->nom ?? $reservation->hotel_nom ?? '');
                $dateArrivee = (string) ($reservation->dateArrivee ?? '');
                $dateDepart = (string) ($reservation->dateDepart ?? '');
                $status = (string) ($reservation->statut ?? '');

                $context .= trim($hotelName . ', du ' . $dateArrivee . ' au ' . $dateDepart . ', statut: ' . $status . '. ');
            }

            $context .= 'Utilise ce contexte uniquement si la question est liée aux réservations ou au séjour. ';
        }

        $historyMessages = collect($request->input('history', []))
            ->map(function (array $item): array {
                $role = $item['role'] === 'bot' ? 'assistant' : $item['role'];

                return [
                    'role' => $role,
                    'content' => (string) ($item['text'] ?? ''),
                ];
            })
            ->filter(fn (array $item): bool => $item['content'] !== '')
            ->values()
            ->all();

        $groqKey = trim((string) config('services.groq.key', ''));
        $groqEndpoint = trim((string) config('services.groq.endpoint', ''));
        $groqModel = trim((string) config('services.groq.model', 'llama-3.3-70b-versatile'));
        $groqSslVerify = (bool) config('services.groq.ssl_verify', false);

        if ($groqKey === '') {
            return response()->json([
                'reply' => 'Service IA indisponible: clé Groq manquante.',
            ], 503);
        }

        if ($groqEndpoint === '') {
            return response()->json([
                'reply' => 'Service IA indisponible: endpoint Groq manquant.',
            ], 503);
        }

        $httpRequest = Http::timeout(35)->withHeaders([
            'Authorization' => 'Bearer ' . $groqKey,
            'Content-Type' => 'application/json',
        ]);

        if (! $groqSslVerify) {
            $httpRequest = $httpRequest->withOptions(['verify' => false]);
        }

        try {
            $payloadMessages = array_merge(
                [[
                    'role' => 'system',
                    'content' => $context,
                ]],
                $historyMessages,
                [[
                    'role' => 'user',
                    'content' => (string) $request->message,
                ]]
            );

            $response = $httpRequest->post($groqEndpoint, [
                'model' => $groqModel,
                'messages' => $payloadMessages,
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);
        } catch (ConnectionException $exception) {
            return response()->json([
                'reply' => 'Service IA indisponible: impossible de joindre Groq.',
            ], 503);
        }

        if ($response->failed()) {
            $status = $response->status();
            $providerMessage = (string) $response->json('error.message', '');

            if (in_array($status, [401, 403], true)) {
                return response()->json([
                    'reply' => 'Service IA indisponible: clé API Groq invalide ou refusée.',
                ], 503);
            }

            if ($status === 429) {
                return response()->json([
                    'reply' => 'Service IA temporairement indisponible: quota Groq dépassé. Réessayez dans quelques instants.',
                ], 503);
            }

            return response()->json([
                'reply' => $providerMessage !== ''
                    ? 'Service IA temporairement indisponible: ' . $providerMessage
                    : 'Désolé, le service est temporairement indisponible.',
            ], 503);
        }

        return response()->json([
            'reply' => (string) $response->json('choices.0.message.content', ''),
        ]);
    }

    private function formatHotelName(mixed $hotelName): string
    {
        if (is_array($hotelName)) {
            foreach (['fr', 'en', 'ar'] as $language) {
                if (! empty($hotelName[$language])) {
                    return (string) $hotelName[$language];
                }
            }

            return (string) (reset($hotelName) ?: '');
        }

        return trim((string) $hotelName);
    }

}