<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Promotion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'promotions';

    protected $fillable = [
        'titre', 'description', 'remise_pourcent', 'type', 'valeur',
        'codePromo', 'dateDebut', 'dateFin',
        'chambresIds', 'estActive', 'nbUtilisations', 'limiteUtilisations'
    ];

    protected $attributes = [
        'chambresIds' => [],
        'estActive' => true,
        'nbUtilisations' => 0,
    ];

    protected $casts = [
        'estActive' => 'boolean',
        'remise_pourcent' => 'integer',
        'nbUtilisations' => 'integer',
        'limiteUtilisations' => 'integer',
    ];
}
