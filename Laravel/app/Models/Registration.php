<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    public const STATUSES = ['pending', 'confirmed', 'cancelled'];

    protected $fillable = [
        'event_id',
        'participant_id',
        'status',
        'registered_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Registration $registration) {
            $registration->registered_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
