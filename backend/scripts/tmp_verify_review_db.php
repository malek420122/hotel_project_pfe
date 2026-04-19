<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hotelIds = [
    '69d181434f4b26aa2e0f5bc3',
    '69d19b569ac74c940909fdbc',
    '69d19b569ac74c940909fdba',
];

foreach ($hotelIds as $id) {
    $hotel = App\Models\Hotel::find($id);
    $reviews = App\Models\Avis::where('hotelId', $id)->where('statut', 'PUBLIE')->get();
    $avg = $reviews->count() ? round($reviews->avg('note'), 2) : 0;

    echo 'HOTEL_ID=' . $id
        . ' NAME=' . ($hotel->nom ?? 'N/A')
        . ' DB_noteMoyenne=' . ($hotel->noteMoyenne ?? 'null')
        . ' PUBLIE_COUNT=' . $reviews->count()
        . ' AVG_FROM_AVIS=' . $avg
        . PHP_EOL;

    foreach ($reviews as $review) {
        echo '  REVIEW_ID=' . $review->_id
            . ' NOTE=' . $review->note
            . ' CLIENT=' . ($review->clientId ?? '')
            . ' RES=' . ($review->reservationId ?? '')
            . PHP_EOL;
    }
}
