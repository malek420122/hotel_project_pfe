<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

$user = App\Models\User::where('email', 'mimi@gmail.com')->first();
if (!$user) {
    $user = App\Models\User::create([
        'nom' => 'Mimi',
        'prenom' => 'Mimi',
        'email' => 'mimi@gmail.com',
        'password' => Hash::make('Mimi1234'),
        'telephone' => '0600000011',
        'langue' => 'fr',
        'role' => 'client',
        'est_actif' => true,
    ]);
} else {
    $user->password = Hash::make('Mimi1234');
    $user->role = $user->role ?: 'client';
    $user->est_actif = true;
    $user->save();
}

$hotels = App\Models\Hotel::all();
$hotelRows = [];
foreach ($hotels as $hotel) {
    $room = App\Models\Chambre::where('hotelId', (string) $hotel->_id)->first();
    if (!$room) {
        continue;
    }

    $hotelRows[] = [
        'hotel' => $hotel,
        'room' => $room,
        'isBurj' => stripos((string) $hotel->nom, 'burj al arab') !== false,
    ];
}

usort($hotelRows, function ($a, $b) {
    if ($a['isBurj'] === $b['isBurj']) {
        return strcmp((string) $a['hotel']->nom, (string) $b['hotel']->nom);
    }
    return $a['isBurj'] ? -1 : 1;
});

$selected = array_slice($hotelRows, 0, 3);

$startDate = new DateTimeImmutable('2026-01-10');
$result = [];

foreach ($selected as $index => $row) {
    $hotel = $row['hotel'];
    $room = $row['room'];

    $arrival = $startDate->modify('+' . ($index * 6) . ' days');
    $departure = $arrival->modify('+2 days');

    $reservation = App\Models\Reservation::where('clientId', (string) $user->_id)
        ->where('hotelId', (string) $hotel->_id)
        ->first();

    if (!$reservation) {
        $reservation = App\Models\Reservation::create([
            'reference' => 'REV-' . strtoupper(substr(md5((string) microtime(true) . $index), 0, 8)),
            'clientId' => (string) $user->_id,
            'chambreId' => (string) $room->_id,
            'hotelId' => (string) $hotel->_id,
            'dateArrivee' => $arrival->format('Y-m-d'),
            'dateDepart' => $departure->format('Y-m-d'),
            'nbVoyageurs' => 2,
            'prixTotal' => (float) (($room->prix_base ?? 100) * 2),
            'statut' => 'CHECKOUT',
            'demandesSpeciales' => '',
            'servicesChoisis' => [],
            'codePromoApplique' => '',
            'remiseAppliquee' => 0,
            'checkoutAt' => now(),
        ]);
    } else {
        $reservation->chambreId = (string) $room->_id;
        $reservation->dateArrivee = $arrival->format('Y-m-d');
        $reservation->dateDepart = $departure->format('Y-m-d');
        $reservation->statut = 'CHECKOUT';
        $reservation->checkoutAt = now();
        $reservation->save();
    }

    $result[] = [
        'reservation_id' => (string) $reservation->_id,
        'hotel_id' => (string) $hotel->_id,
        'hotel_name' => (string) $hotel->nom,
        'room_id' => (string) $room->_id,
        'status' => (string) $reservation->statut,
    ];
}

echo json_encode([
    'user_id' => (string) $user->_id,
    'email' => (string) $user->email,
    'reservations' => $result,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
