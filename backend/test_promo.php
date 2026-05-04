<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Promotion;

// Create a new promo
$promo = Promotion::create([
    'titre' => 'Test',
    'codePromo' => 'TEST-NEW',
    'dateDebut' => now(),
    'dateFin' => now()->addMonths(3),
    'estActive' => true
]);

echo "Created promo: " . $promo->codePromo . "\n";
echo "DateDebut in DB: " . json_encode($promo->dateDebut) . "\n";

// Validate it
$valid = Promotion::where('codePromo', 'TEST-NEW')
    ->where('estActive', true)
    ->where('dateDebut', '<=', now())
    ->where('dateFin', '>=', now())
    ->first();

echo "Is valid? " . ($valid ? "YES" : "NO") . "\n";

