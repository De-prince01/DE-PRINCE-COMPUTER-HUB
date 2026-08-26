<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pc extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'name', 'identifier', 'specs', 'hourly_rate', 'status', 'metadata',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function cyberSessions(): HasMany
    {
        return $this->hasMany(CyberSession::class);
    }

    public function activeSession()
    {
        return $this->hasOne(CyberSession::class)->ofMany([
            'id' => 'max',
        ], function ($q) {
            $q->where('status', 'active');
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
