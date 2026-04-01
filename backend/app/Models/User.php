<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'nom', 'prenom', 'email', 'password', 'telephone',
        'nationalite', 'langue', 'role', 'points_fidelite',
        'niveau_fidelite', 'est_actif', 'tentatives_connexion',
        'bloque_jusqu_a'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'est_actif' => 'boolean',
        'points_fidelite' => 'integer',
    ];

    protected $attributes = [
        'role' => 'client',
        'points_fidelite' => 0,
        'niveau_fidelite' => 'Bronze',
        'est_actif' => true,
        'tentatives_connexion' => 0,
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->role];
    }
}
