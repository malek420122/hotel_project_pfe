<?php

require_once 'vendor/autoload.php';

use App\Models\Chambre;

$chambres = Chambre::all()->groupBy('hotelId')->map(function($g) {
    return $g->count() . ' chambres, ' . $g->where('estDisponible', true)->count() . ' disponibles';
})->toArray();

print_r($chambres);