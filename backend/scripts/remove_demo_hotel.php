<?php

declare(strict_types=1);

use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$hotel = Hotel::where('nom', 'HotelEase Demo Hotel')->first();
if (! $hotel) {
    echo "not_found\n";
    exit(0);
}

$hotelId = (string) $hotel->_id;
$reservationIds = Reservation::where('hotelId', $hotelId)
    ->pluck('_id')
    ->map(fn ($id) => (string) $id)
    ->all();

if (! empty($reservationIds)) {
    Paiement::whereIn('reservationId', $reservationIds)->delete();
}

Reservation::where('hotelId', $hotelId)->delete();
Chambre::where('hotelId', $hotelId)->delete();
$hotel->delete();

echo "removed\n";
