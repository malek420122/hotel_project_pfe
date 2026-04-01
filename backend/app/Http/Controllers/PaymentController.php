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
    public function createCheckoutSession(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'reservationId' => 'required|string',
            'successUrl' => 'required|url',
            'cancelUrl' => 'required|url',
        ]);

        $reservation = Reservation::findOrFail($request->reservationId);
        if ((string) $reservation->clientId !== (string) $user->_id) {
            return response()->json(['error' => 'Non autorise'], 403);
        }

        $paiement = Paiement::firstOrCreate(
            ['reservationId' => (string) $reservation->_id],
            [
                'montant' => (float) $reservation->prixTotal,
                'statut' => 'EN_COURS',
                'methode' => 'carte',
            ]
        );

        $stripeSecret = config('services.stripe.secret');
        if (! $stripeSecret) {
            return response()->json(['error' => 'Stripe non configure (STRIPE_SECRET manquant).'], 500);
        }

        $response = Http::asForm()
            ->withToken($stripeSecret)
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $request->successUrl,
                'cancel_url' => $request->cancelUrl,
                'customer_email' => $user->email,
                'line_items[0][price_data][currency]' => 'eur',
                'line_items[0][price_data][unit_amount]' => (int) round(((float) $reservation->prixTotal) * 100),
                'line_items[0][price_data][product_data][name]' => 'Reservation ' . $reservation->reference,
                'line_items[0][quantity]' => 1,
                'metadata[reservation_id]' => (string) $reservation->_id,
                'metadata[paiement_id]' => (string) $paiement->_id,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Echec de creation de session Stripe',
                'details' => $response->json(),
            ], 422);
        }

        $session = $response->json();
        $paiement->update([
            'transactionId' => $session['id'] ?? null,
            'statut' => 'EN_COURS',
        ]);

        return response()->json([
            'checkoutUrl' => $session['url'] ?? null,
            'sessionId' => $session['id'] ?? null,
        ]);
    }

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

    public function stripeWebhook(Request $request)
    {
        $event = $request->json()->all();
        $type = $event['type'] ?? null;

        if ($type === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $metadata = $session['metadata'] ?? [];
            $reservationId = $metadata['reservation_id'] ?? null;
            $paiementId = $metadata['paiement_id'] ?? null;

            if ($paiementId) {
                Paiement::where('_id', $paiementId)->update([
                    'statut' => 'PAYE',
                    'transactionId' => $session['id'] ?? null,
                ]);
            }

            if ($reservationId) {
                Reservation::where('_id', $reservationId)->update(['statut' => 'CONFIRMEE']);
            }
        }

        return response()->json(['received' => true]);
    }

    public function history()
    {
        $user = JWTAuth::parseToken()->authenticate();

        $reservations = Reservation::where('clientId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [];
        foreach ($reservations as $reservation) {
            $payment = Paiement::where('reservationId', (string) $reservation->_id)->first();
            $rows[] = [
                'reference' => $reservation->reference,
                'reservationId' => (string) $reservation->_id,
                'montant' => $payment?->montant ?? $reservation->prixTotal,
                'methode' => $payment?->methode ?? 'carte',
                'date' => optional($reservation->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                'statut' => $payment?->statut ?? 'EN_COURS',
                'transactionId' => $payment?->transactionId,
            ];
        }

        return response()->json($rows);
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
