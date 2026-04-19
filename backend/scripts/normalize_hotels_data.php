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

$photosByName = [
    'the ritz london' => [
        'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
    ],
    'burj al arab' => [
        'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
    ],
    'hotel negresco' => [
        'https://images.unsplash.com/photo-1501117716987-c8e1ecb210f8?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1400&q=80',
    ],
    'hotel plaza athenee' => [
        'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1431274172761-fca41d930114?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=1400&q=80',
    ],
];

$normalize = static function (string $name): string {
    $value = trim(mb_strtolower($name));
    $value = str_replace(['é', 'è', 'ê', 'ë'], 'e', $value);
    $value = str_replace(['à', 'â', 'ä'], 'a', $value);
    $value = str_replace(['î', 'ï'], 'i', $value);
    $value = str_replace(['ô', 'ö'], 'o', $value);
    $value = str_replace(['ù', 'û', 'ü'], 'u', $value);
    $value = str_replace(['ç'], 'c', $value);

    return preg_replace('/\s+/', ' ', $value) ?: $value;
};

$removedDemo = 0;
$starsFixed = 0;
$photosUpdated = 0;

$demo = Hotel::where('nom', 'HotelEase Demo Hotel')->first();
if ($demo) {
    $hotelId = (string) $demo->_id;
    $reservationIds = Reservation::where('hotelId', $hotelId)
        ->pluck('_id')
        ->map(fn ($id) => (string) $id)
        ->all();

    if (!empty($reservationIds)) {
        Paiement::whereIn('reservationId', $reservationIds)->delete();
    }

    Reservation::where('hotelId', $hotelId)->delete();
    Chambre::where('hotelId', $hotelId)->delete();
    $demo->delete();
    $removedDemo = 1;
}

$hotels = Hotel::all();
foreach ($hotels as $hotel) {
    $dirty = false;

    $stars = (int) ($hotel->etoiles ?? 3);
    if ($stars > 5) {
        $hotel->etoiles = 5;
        $dirty = true;
        $starsFixed++;
    } elseif ($stars < 1) {
        $hotel->etoiles = 1;
        $dirty = true;
        $starsFixed++;
    }

    $key = $normalize((string) ($hotel->nom ?? ''));
    if (isset($photosByName[$key])) {
        $target = $photosByName[$key];
        $current = is_array($hotel->photos) ? array_values(array_filter($hotel->photos, fn ($x) => is_string($x) && trim($x) !== '')) : [];
        if ($current !== $target) {
            $hotel->photos = $target;
            $dirty = true;
            $photosUpdated++;
        }
    }

    if ($dirty) {
        $hotel->save();
    }
}

echo json_encode([
    'removed_demo_hotel' => $removedDemo,
    'stars_fixed' => $starsFixed,
    'photos_updated' => $photosUpdated,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
