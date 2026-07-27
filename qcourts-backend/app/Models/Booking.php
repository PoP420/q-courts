<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'customer_name',
        'customer_phone',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function session()
    {
        return $this->hasOne(CourtSession::class);
    }
}
