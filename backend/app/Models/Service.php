<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Service extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'services';

    protected $fillable = [
        'hotelId', 'categorie', 'nom', 'description',
        'prix', 'capacite', 'creneaux', 'estActif'
    ];

    protected $attributes = [
        'creneaux' => [],
        'estActif' => true,
    ];
}
