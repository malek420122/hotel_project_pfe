<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$statuses = ['LIBRE', 'OCCUPE', 'ENTRETIEN', 'NETTOYAGE'];
\App\Models\Chambre::all()->each(function($room) use ($statuses) {
    $room->update(['statut' => $statuses[array_rand($statuses)]]);
});

echo "Updated room statuses\n";