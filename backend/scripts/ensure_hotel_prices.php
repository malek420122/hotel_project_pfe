<?php

declare(strict_types=1);

use App\Models\Chambre;
use App\Models\Hotel;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$priceByName = [
    'the ritz london' => 420,
    'burj al arab' => 680,
    'hotel negresco' => 290,
    'hotel plaza athenee' => 510,
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

$created = 0;

foreach (Hotel::where('estActif', true)->get() as $hotel) {
    $hotelId = (string) $hotel->_id;
    $roomCount = Chambre::where('hotelId', $hotelId)->count();
    if ($roomCount > 0) {
        continue;
    }

    $key = $normalize((string) ($hotel->nom ?? ''));
    $price = $priceByName[$key] ?? 220;

    Chambre::create([
        'hotelId' => $hotelId,
        'type' => 'DOUBLE',
        'nom' => 'Chambre Standard',
        'description' => 'Chambre confortable avec services essentiels.',
        'prix_base' => $price,
        'maxVoyageurs' => 2,
        'equipements' => ['wifi', 'climatisation'],
        'photos' => is_array($hotel->photos) ? $hotel->photos : [],
        'estDisponible' => true,
        'etage' => 1,
    ]);

    $created++;
}

echo json_encode(['rooms_created' => $created], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
