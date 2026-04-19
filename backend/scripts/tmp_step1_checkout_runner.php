<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'mimi@gmail.com')->first();
if (!$user) {
    echo "USER_NOT_FOUND\n";
    exit(0);
}

$reservation = App\Models\Reservation::where('user_id', $user->_id)->first();
if (!$reservation) {
    echo "RESERVATION_NOT_FOUND\n";
    exit(0);
}

$reservation->statut = 'checkout';
$reservation->save();

echo 'Done: ' . $reservation->_id
    . ' -> status: ' . $reservation->statut
    . ' | hotel_id=' . ($reservation->hotel_id ?? '')
    . ' | reference=' . ($reservation->reference ?? '')
    . "\n";
