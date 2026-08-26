<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'user_id', 'vendor_id', 'billable_type', 'billable_id',
        'subtotal', 'tax', 'discount', 'total', 'amount_paid', 'balance',
        'line_items', 'status', 'due_at', 'paid_at', 'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'line_items' => 'array',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recalcTotals(): static
    {
        $this->total = (float) bcsub(
            (string) bcadd((string) $this->subtotal, (string) $this->tax, 2),
            (string) $this->discount,
            2
        );
        $this->balance = (float) bcsub((string) $this->total, (string) $this->amount_paid, 2);

        if ($this->balance <= 0 && $this->total > 0 && $this->status !== 'refunded') {
            $this->status = 'paid';
            if (is_null($this->paid_at)) {
                $this->paid_at = now();
            }
        } elseif ($this->amount_paid > 0 && $this->balance > 0) {
            $this->status = 'partially_paid';
        }

        return $this;
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $seq = static::whereYear('created_at', $year)->max('id') ?? 0;
        $seq = str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT);
        return "INV-{$year}{$month}-{$seq}";
    }
}
