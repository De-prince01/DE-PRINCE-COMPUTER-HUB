<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PrintOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'user_id', 'customer_name', 'customer_phone',
        'type', 'pages', 'copies', 'double_sided',
        'file_path', 'file_name',
        'unit_price', 'amount', 'status', 'notes',
    ];

    protected $casts = [
        'double_sided' => 'boolean',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'billable');
    }

    public function calculateAmount(): float
    {
        $sides = $this->double_sided ? 1 : 2;
        $this->amount = (float) bcmul(
            (string) bcmul((string) $this->pages, (string) $this->copies, 4),
            (string) $this->unit_price,
            2
        );
        return $this->amount;
    }
}
