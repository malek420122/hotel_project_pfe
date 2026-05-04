<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Incident extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'incidents';

    protected $fillable = [
        'room',
        'type',
        'severity',
        'description',
        'status',
        'source',
        'signalId',
        'reportedBy',
        'assignedTo',
        'reportedAt',
        'resolvedAt',
        'resolvedBy',
    ];

    protected $attributes = [
        'status' => 'ouvert',
        'source' => 'reception',
    ];

    protected $casts = [
        'reportedAt' => 'datetime',
        'resolvedAt' => 'datetime',
    ];
}
