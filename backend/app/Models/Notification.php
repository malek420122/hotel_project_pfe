<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = ['userId', 'type', 'message', 'vu', 'estLue', 'createdAt'];

    protected $attributes = [
        'vu' => false,
        'estLue' => false,
    ];

    protected $casts = [
        'vu' => 'boolean',
        'estLue' => 'boolean',
        'createdAt' => 'datetime',
    ];
}
