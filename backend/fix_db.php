<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Initialize Laravel
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Fix 1: Add rooms to hotels with fewer than 3 rooms
echo "=== ADDING ROOMS ===\n";
$roomsAdded = 0;
\App\Models\Hotel::all()->each(function($hotel) use (&$roomsAdded) {
    $existing = \App\Models\Chambre::where('hotel_id', $hotel->id)->count();
    if ($existing < 3) {
        $base = $hotel->prix_par_nuit ?? 200;
        $rooms = [
            [
                'type' => 'simple',
                'capacite' => 1,
                'prix_par_nuit' => $base * 0.7,
                'description' => 'Chambre simple confortable',
                'disponible' => true,
                'numero' => 'S-' . rand(100, 199),
                'services' => ['wifi', 'climatisation']
            ],
            [
                'type' => 'double',
                'capacite' => 2,
                'prix_par_nuit' => $base,
                'description' => 'Chambre double élégante',
                'disponible' => true,
                'numero' => 'D-' . rand(200, 299),
                'services' => ['wifi', 'climatisation', 'tv']
            ],
            [
                'type' => 'suite',
                'capacite' => 4,
                'prix_par_nuit' => $base * 1.5,
                'description' => 'Suite luxueuse avec vue',
                'disponible' => true,
                'numero' => 'SU-' . rand(300, 399),
                'services' => ['wifi', 'climatisation', 'tv', 'jacuzzi', 'minibar']
            ],
        ];
        foreach ($rooms as $r) {
            $r['hotel_id'] = $hotel->id;
            \App\Models\Chambre::create($r);
            $roomsAdded++;
        }
        echo "Hotel {$hotel->nom}: added 3 rooms (had $existing)\n";
    }
});
echo "Total rooms added: $roomsAdded\n";
echo "Total rooms count: " . \App\Models\Chambre::count() . "\n\n";

// Fix 2: Add reviews from completed reservations
echo "=== ADDING REVIEWS ===\n";
$hotels = \App\Models\Hotel::all();
$users = \App\Models\User::where('role', 'client')->get();

if ($users->count() === 0) {
    $users = \App\Models\User::all();
}

$reviews = [
    ['note' => 5, 'commentaire' => 'Séjour exceptionnel, service impeccable!'],
    ['note' => 4, 'commentaire' => 'Très bel hôtel, chambre spacieuse et propre.'],
    ['note' => 5, 'commentaire' => 'Personnel accueillant, je recommande vivement.'],
    ['note' => 3, 'commentaire' => 'Bon rapport qualité-prix, quelques points à améliorer.'],
    ['note' => 5, 'commentaire' => 'Vue magnifique, petit-déjeuner délicieux.'],
    ['note' => 4, 'commentaire' => 'Excellent emplacement, très bien situé en centre-ville.'],
    ['note' => 5, 'commentaire' => 'Suite somptueuse, nous reviendrons sans hésiter!'],
    ['note' => 4, 'commentaire' => 'Bonne expérience globale, spa de qualité.'],
    ['note' => 3, 'commentaire' => 'Correct mais le prix est un peu élevé.'],
    ['note' => 5, 'commentaire' => 'Le meilleur hôtel où j\'ai séjourné, parfait!'],
    ['note' => 4, 'commentaire' => 'Très agréable, piscine superbe et restaurant excellent.'],
    ['note' => 5, 'commentaire' => 'Service 5 étoiles, rien à redire, absolument parfait.'],
];

$reviewsAdded = 0;
foreach ($reviews as $i => $rev) {
    $hotel = $hotels[$i % $hotels->count()];
    $user = $users[$i % $users->count()];
    \App\Models\Avis::updateOrCreate(
        ['hotel_id' => (string)$hotel->_id, 'user_id' => (string)$user->_id],
        array_merge($rev, [
            'hotel_id' => $hotel->_id,
            'user_id' => $user->_id,
            'reponse_marketing' => $i % 3 === 0
                ? 'Merci pour votre retour, nous sommes ravis!'
                : null,
            'created_at' => \Carbon\Carbon::now()->subDays(rand(1, 90)),
        ])
    );
    $reviewsAdded++;
}
echo "Added $reviewsAdded reviews\n";
echo "Total reviews count: " . \App\Models\Avis::count() . "\n\n";

// Fix 3: Update hotel average ratings
echo "=== UPDATING HOTEL RATINGS ===\n";
$updated = 0;
\App\Models\Hotel::all()->each(function($hotel) use (&$updated) {
    $avg = \App\Models\Avis::where('hotel_id', $hotel->_id)->avg('note');
    if ($avg) {
        $hotel->note_moyenne = round($avg, 1);
        $hotel->save();
        $updated++;
    }
});
echo "Updated $updated hotels with average ratings\n\n";

// Verification
echo "=== FINAL VERIFICATION ===\n";
echo "Hotels: " . \App\Models\Hotel::count() . " (target: 19+)\n";
echo "Rooms: " . \App\Models\Chambre::count() . " (target: 30+)\n";
echo "Users: " . \App\Models\User::count() . " (target: 43+)\n";
echo "Reservations: " . \App\Models\Reservation::count() . " (target: 28+)\n";
echo "Reviews: " . \App\Models\Avis::count() . " (target: 10+)\n";
echo "Payments: " . \App\Models\Paiement::count() . " (target: 5+)\n";
echo "Promotions: " . \App\Models\Promotion::count() . " (target: 3+)\n";
echo "Notifications: " . \App\Models\Notification::count() . " (target: 10+)\n";
