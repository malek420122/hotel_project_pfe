<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paiement;
use App\Models\Reservation;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;
use App\Models\Notification;

class PaymentController extends Controller
{
    public function createIntent(Request $request)
    {
        JWTAuth::parseToken()->authenticate();

        $request->validate([
            'reservationId' => 'required|string',
        ]);

        $reservation = Reservation::findOrFail($request->reservationId);
        $paiement = Paiement::where('reservationId', (string) $reservation->_id)->first();

        if (!$paiement) {
            return response()->json(['error' => 'Paiement introuvable'], 404);
        }

        $stripeSecret = config('services.stripe.secret');
        if (!$stripeSecret) {
            return response()->json([
                'provider' => 'stub',
                'clientSecret' => null,
                'message' => 'Mode test local: STRIPE_SECRET non configure.'
            ]);
        }

        $amount = (int) round(((float) $paiement->montant) * 100);

        $response = Http::asForm()
            ->withToken($stripeSecret)
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => max(50, $amount),
                'currency' => 'eur',
                'payment_method_types[]' => 'card',
                'metadata[reservation_id]' => (string) $reservation->_id,
            ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Echec creation PaymentIntent',
                'details' => $response->json(),
            ], 422);
        }

        $intent = $response->json();

        return response()->json([
            'provider' => 'stripe',
            'paymentIntentId' => $intent['id'] ?? null,
            'clientSecret' => $intent['client_secret'] ?? null,
            'status' => $intent['status'] ?? null,
        ]);
    }

    public function process(Request $request)
    {
        JWTAuth::parseToken()->authenticate();

        $request->validate([
            'reservationId' => 'required|string',
            'methode' => 'required|string',
            'paymentIntentId' => 'nullable|string',
        ]);

        $paiement = Paiement::where('reservationId', $request->reservationId)->first();
        if (!$paiement) {
            return response()->json(['error' => 'Paiement introuvable'], 404);
        }

        $stripeSecret = config('services.stripe.secret');
        $transactionId = 'TXN-' . uniqid();
        $provider = 'stub';

        if ($stripeSecret && $request->filled('paymentIntentId')) {
            $intentResponse = Http::withToken($stripeSecret)
                ->get('https://api.stripe.com/v1/payment_intents/' . $request->paymentIntentId);

            if (!$intentResponse->successful()) {
                return response()->json([
                    'error' => 'Echec verification PaymentIntent',
                    'details' => $intentResponse->json(),
                ], 422);
            }

            $intent = $intentResponse->json();
            $intentStatus = $intent['status'] ?? null;

            if (!in_array($intentStatus, ['succeeded', 'requires_capture'])) {
                return response()->json([
                    'error' => 'Paiement non confirme',
                    'stripe_status' => $intentStatus,
                ], 422);
            }

            $transactionId = $intent['id'] ?? $transactionId;
            $provider = 'stripe';
        }

        $paiement->update([
            'statut' => 'PAYE',
            'transactionId' => $transactionId,
            'methode' => $request->methode,
        ]);

        $reservation = Reservation::find($paiement->reservationId);
        if ($reservation && $reservation->statut !== 'CONFIRMEE') {
            $reservation->update(['statut' => 'CONFIRMEE']);

            Notification::create([
                'userId' => $reservation->clientId,
                'type' => 'PAIEMENT_CONFIRME',
                'message' => "Paiement confirme pour la reservation {$reservation->reference}.",
                'estLue' => false,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'provider' => $provider,
            'transactionId' => $paiement->transactionId,
        ]);
    }

    // Admin: list payments
    public function index()
    {
        $payments = Paiement::orderBy('created_at', 'desc')->get();
        return response()->json($payments);
    }

    // Admin: show payment
    public function show($id)
    {
        $payment = Paiement::findOrFail($id);
        return response()->json($payment);
    }
}
