<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_PAYSTACK = 'paystack';
    public const GATEWAY_FLUTTERWAVE = 'flutterwave';
    public const GATEWAY_WALLET = 'wallet';
    public const GATEWAY_CASH = 'cash';

    protected $fillable = [
        'invoice_id', 'user_id', 'vendor_id',
        'gateway', 'gateway_reference', 'customer_email', 'customer_phone',
        'currency', 'amount', 'fee', 'settled',
        'status', 'gateway_response', 'metadata', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'settled' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
