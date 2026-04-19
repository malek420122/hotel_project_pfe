<?php

declare(strict_types=1);

use App\Models\Hotel;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$servicesByName = [
    'the ritz london' => ['wifi', 'spa', 'restaurant', 'climatisation', 'parking'],
    'burj al arab' => ['wifi', 'piscine', 'spa', 'restaurant', 'parking', 'climatisation'],
    'hotel negresco' => ['wifi', 'restaurant', 'parking'],
    'hotel plaza athenee' => ['wifi', 'spa', 'restaurant', 'climatisation'],
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

$updated = 0;

foreach (Hotel::query()->get() as $hotel) {
    $key = $normalize((string) ($hotel->nom ?? ''));
        $target = $servicesByName[$key] ?? ['wifi'];
        $current = is_array($hotel->services) ? array_values(array_unique($hotel->services)) : [];

    sort($target);
    sort($current);

    if ($current !== $target) {
            $hotel->services = $servicesByName[$key] ?? ['wifi'];
            $hotel->equipements = $servicesByName[$key] ?? ['wifi'];
      $hotel->save();
      $updated++;
    }
}

echo json_encode(['services_updated' => $updated], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
