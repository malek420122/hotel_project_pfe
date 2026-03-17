<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'currency',
        'status',
        'provider',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
