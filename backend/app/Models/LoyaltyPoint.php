<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class LoyaltyPoint extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'loyalty_points';

    protected $fillable = [
        'user_id',
        'points_total',
    ];

    protected $casts = [
        'points_total' => 'integer',
    ];
}
