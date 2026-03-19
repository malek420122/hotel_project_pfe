<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Hotel extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'hotels';

    protected $fillable = [
        'nom', 'description', 'adresse', 'ville',
        'latitude', 'longitude', 'etoiles', 'photos',
        'noteMoyenne', 'equipements', 'estActif'
    ];

    protected $attributes = [
        'etoiles' => 3,
        'photos' => [],
        'equipements' => [],
        'noteMoyenne' => 0,
        'estActif' => true,
    ];
}
