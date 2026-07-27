<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'booking_id',
        'game_type',
        'planned_minutes',
        'started_at',
        'ended_at',
        'score',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'score' => 'array',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** Minutes remaining right now, floored at 0. Useful for the mobile app's live timer. */
    public function getMinutesRemainingAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $elapsed = now()->diffInMinutes($this->started_at);

        return max(0, $this->planned_minutes - $elapsed);
    }
}
