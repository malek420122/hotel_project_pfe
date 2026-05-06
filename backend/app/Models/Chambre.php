<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Chambre extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'chambres';

    protected $fillable = [
        'hotel_id', 'hotelId', 'type', 'nom', 'description', 'prix_base',
        'maxVoyageurs', 'equipements', 'photos', 'estDisponible', 'etage', 'statut'
    ];

    protected $attributes = [
        'type' => 'SIMPLE',
        'equipements' => [],
        'photos' => [],
        'estDisponible' => true,
        'etage' => 1,
        'statut' => 'LIBRE',
    ];
}
