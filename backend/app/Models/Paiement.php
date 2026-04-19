<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Paiement extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'paiements';

    protected $fillable = [
        'reservationId', 'montant', 'statut', 'methode', 'transactionId'
    ];

    protected $attributes = [
        'statut' => 'EN_COURS',
    ];

    protected $casts = [
        'montant' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
