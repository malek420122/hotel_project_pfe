<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = ['userId', 'type', 'message', 'estLue'];

    protected $attributes = [
        'estLue' => false,
    ];
}
