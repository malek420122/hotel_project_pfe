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

echo "USER_ID=" . $user->_id . "\n";

$reservations = App\Models\Reservation::where('user_id', $user->_id)->get();
echo "RESERVATION_COUNT=" . $reservations->count() . "\n";

foreach ($reservations as $r) {
    echo "RES=" . $r->_id . " hotel_id=" . ($r->hotel_id ?? '') . " statut=" . ($r->statut ?? '') . " reference=" . ($r->reference ?? '') . "\n";
}
