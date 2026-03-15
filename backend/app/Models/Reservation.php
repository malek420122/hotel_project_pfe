<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Reservation extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reservations';

    protected $fillable = [
        'reference', 'clientId', 'chambreId', 'hotelId',
        'dateArrivee', 'dateDepart', 'nbVoyageurs',
        'statut', 'prixTotal', 'demandesSpeciales',
        'servicesChoisis', 'codePromoApplique', 'remiseAppliquee'
    ];

    protected $attributes = [
        'statut' => 'EN_ATTENTE',
        'servicesChoisis' => [],
        'nbVoyageurs' => 1,
        'remiseAppliquee' => 0,
    ];
}
