<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ClientSignal extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'clientSignals';

    protected $fillable = [
        'userId',
        'clientId',
        'clientName',
        'room',
        'reservationId',
        'message',
        'status',
        'incidentId',
        'createdIncidentId',
        'createdAt',
        'processedAt',
        'processedBy',
    ];

    protected $attributes = [
        'status' => 'en_attente',
    ];

    protected $casts = [
        'createdAt' => 'datetime',
        'processedAt' => 'datetime',
    ];
}
