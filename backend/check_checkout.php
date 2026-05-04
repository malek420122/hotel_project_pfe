<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checkout = App\Models\Reservation::where('reference', 'like', 'TEST-CHECKOUT-%')->first();
if ($checkout) {
    echo 'Found checkout: ' . $checkout->reference . PHP_EOL;
    echo 'Status: ' . $checkout->statut . PHP_EOL;
    echo 'Date Depart: ' . $checkout->dateDepart . PHP_EOL;
    echo 'Today: ' . now()->toDateString() . PHP_EOL;
} else {
    echo 'No checkout found' . PHP_EOL;
}