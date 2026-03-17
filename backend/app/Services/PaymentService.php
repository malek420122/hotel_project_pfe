<?php

namespace App\Services;

use App\Models\Payment;

class PaymentService
{
    public function process(array $payload): Payment
    {
        return Payment::create([
            'booking_id' => $payload['booking_id'],
            'amount' => $payload['amount'],
            'currency' => $payload['currency'] ?? 'EUR',
            'status' => 'paid',
            'provider' => $payload['provider'] ?? 'sandbox',
            'transaction_reference' => $payload['transaction_reference'] ?? uniqid('pay_', true),
        ]);
    }
}
