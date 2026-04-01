<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyStripeWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        // If no secret configured, skip verification for local/dev.
        if ($secret === '') {
            return $next($request);
        }

        $signatureHeader = $request->header('Stripe-Signature');
        if (! $signatureHeader) {
            return response()->json(['error' => 'Missing Stripe-Signature header'], 400);
        }

        $parts = $this->parseSignatureHeader($signatureHeader);
        $timestamp = $parts['t'] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            return response()->json(['error' => 'Invalid Stripe signature header format'], 400);
        }

        // Replay attack protection (5 min tolerance)
        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['error' => 'Stripe signature timestamp is outside tolerance'], 400);
        }

        $payload = $request->getContent();
        $signedPayload = $timestamp . '.' . $payload;
        $computed = hash_hmac('sha256', $signedPayload, $secret);

        $isValid = false;
        foreach ($signatures as $signature) {
            if (hash_equals($computed, $signature)) {
                $isValid = true;
                break;
            }
        }

        if (! $isValid) {
            return response()->json(['error' => 'Invalid Stripe webhook signature'], 400);
        }

        return $next($request);
    }

    private function parseSignatureHeader(string $header): array
    {
        $result = [];
        foreach (explode(',', $header) as $item) {
            $kv = explode('=', trim($item), 2);
            if (count($kv) !== 2) {
                continue;
            }

            [$key, $value] = $kv;
            if ($key === 'v1') {
                $result['v1'][] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
