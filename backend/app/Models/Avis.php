<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Avis extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'avis';

    protected $fillable = [
        'clientId', 'hotelId', 'reservationId',
        'note', 'commentaire', 'statut', 'reponseHotel', 'reponse_marketing'
    ];

    protected $attributes = [
        'statut' => 'EN_ATTENTE',
    ];
}
