<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Promotion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'promotions';

    protected $fillable = [
        'titre', 'description', 'remise_pourcent',
        'codePromo', 'dateDebut', 'dateFin',
        'chambresIds', 'estActive', 'nbUtilisations', 'limiteUtilisations'
    ];

    protected $attributes = [
        'chambresIds' => [],
        'estActive' => true,
        'nbUtilisations' => 0,
    ];
}
