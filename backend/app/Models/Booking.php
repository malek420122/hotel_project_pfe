<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'check_in',
        'check_out',
        'guests',
        'status',
        'total_amount',
        'services',
        'special_requests',
        'payment_status',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'services' => 'array',
        'guests' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return '_id';
    }
}
