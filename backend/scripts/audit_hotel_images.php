<?php

declare(strict_types=1);

use App\Models\Hotel;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$fallback = 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800';

function isHttp200(string $url): bool
{
    if ($url === '') {
        return false;
    }

    $escaped = escapeshellarg($url);
    $command = "curl.exe -k -L -s -o NUL -w \"%{http_code}\" $escaped";
    $output = trim((string) shell_exec($command));
    if ($output === '') {
        return false;
    }

    return $output === '200';
}

$total = 0;
$ok = 0;
$fixed = 0;
$broken = [];

$hotels = Hotel::query()->get();

foreach ($hotels as $hotel) {
    $total++;
    $photos = is_array($hotel->photos) ? array_values(array_filter($hotel->photos)) : [];
    $current = isset($photos[0]) ? (string) $photos[0] : '';

    if (isHttp200($current)) {
        $ok++;
        continue;
    }

    $broken[] = [
        'nom' => (string) ($hotel->nom ?? ''),
        'bad_url' => $current,
    ];

    $hotel->photos = [$fallback];
    $hotel->save();
    $fixed++;
}

echo json_encode([
    'total_hotels' => $total,
    'ok' => $ok,
    'fixed' => $fixed,
    'broken_hotels' => $broken,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
