<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ReservationPaymentConsistencySeeder extends Seeder
{
    public function run(): void
    {
        Reservation::query()->orderBy('created_at')->chunk(200, function ($reservations): void {
            foreach ($reservations as $reservation) {
                $payment = Paiement::query()->firstOrCreate(
                    ['reservationId' => (string) $reservation->_id],
                    [
                        'montant' => (float) ($reservation->prixTotal ?? 0),
                        'methode' => 'carte',
                        'statut' => 'EN_COURS',
                    ]
                );

                $expectedStatus = match ($reservation->statut) {
                    'CONFIRMEE', 'EN_COURS', 'TERMINEE' => 'PAYE',
                    'ANNULEE', 'REJETE' => 'ANNULE',
                    default => 'EN_COURS',
                };

                $payment->update([
                    'montant' => (float) ($reservation->prixTotal ?? 0),
                    'methode' => $payment->methode ?: 'carte',
                    'statut' => $expectedStatus,
                    'transactionId' => $expectedStatus === 'PAYE'
                        ? ($payment->transactionId ?: ('TXN-' . strtoupper(substr((string) $reservation->reference, -6))))
                        : null,
                ]);
            }
        });
    }
}
