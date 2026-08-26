<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CyberSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'pc_id', 'user_id', 'customer_name', 'customer_phone',
        'started_at', 'ended_at', 'hourly_rate', 'total_minutes',
        'amount', 'status', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'total_minutes' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function pc(): BelongsTo
    {
        return $this->belongsTo(Pc::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable');
    }

    public function getElapsedMinutesAttribute(): float
    {
        $end = $this->ended_at ?? Carbon::now();
        return (float) $end->diffInMinutes($this->started_at ?? Carbon::now());
    }

    public function calculateAmount(): float
    {
        $minutes = $this->elapsed_minutes;
        $this->total_minutes = $minutes;
        $this->amount = (float) bcmul((string) $this->hourly_rate, (string) ($minutes / 60), 2);
        return $this->amount;
    }
}
