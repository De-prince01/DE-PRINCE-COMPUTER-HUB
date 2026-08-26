<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CyberSession;
use App\Models\Invoice;
use App\Models\PrintOrder;
use Illuminate\Database\Eloquent\Model;

class InvoiceService
{
    public function createFor(Model $billable, array $overrides = []): Invoice
    {
        $class = $billable::class;

        [$subtotal, $vendorId, $userId] = match ($class) {
            CyberSession::class => [
                $billable->calculateAmount() ?? $billable->amount ?? 0,
                $billable->pc?->vendor_id,
                $billable->user_id,
            ],
            PrintOrder::class => [
                $billable->calculateAmount() ?? $billable->amount ?? 0,
                $billable->vendor_id,
                $billable->user_id,
            ],
            Booking::class => [
                (float) ($billable->amount ?? 0),
                $billable->vendor_id,
                $billable->user_id,
            ],
            default => [0, null, null],
        };

        $invoice = Invoice::create([
            'number' => Invoice::generateNumber(),
            'user_id' => $userId ?? $overrides['user_id'] ?? null,
            'vendor_id' => $vendorId ?? $overrides['vendor_id'] ?? null,
            'billable_type' => $class,
            'billable_id' => $billable->getKey(),
            'subtotal' => $overrides['subtotal'] ?? $subtotal,
            'tax' => $overrides['tax'] ?? 0,
            'discount' => $overrides['discount'] ?? 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'line_items' => $overrides['line_items'] ?? $this->buildLineItems($billable),
            'status' => $overrides['status'] ?? 'unpaid',
            'due_at' => $overrides['due_at'] ?? now()->addDays(7),
            'notes' => $overrides['notes'] ?? null,
        ]);

        $invoice->recalcTotals()->save();
        return $invoice->fresh();
    }

    private function buildLineItems(Model $billable): array
    {
        $items = [];
        if ($billable instanceof CyberSession) {
            $items[] = [
                'label' => "Cyber session on {$billable->pc?->name} (#{$billable->pc?->identifier})",
                'qty' => round(($billable->total_minutes ?? 0) / 60, 2) ?: 0,
                'unit_price' => (float) $billable->hourly_rate,
                'subtotal' => (float) $billable->amount,
            ];
        } elseif ($billable instanceof PrintOrder) {
            $items[] = [
                'label' => "Print job ({$billable->type}, pages: {$billable->pages}, copies: {$billable->copies})",
                'qty' => $billable->pages * $billable->copies,
                'unit_price' => (float) $billable->unit_price,
                'subtotal' => (float) $billable->amount,
            ];
        } elseif ($billable instanceof Booking) {
            $items[] = [
                'label' => "Booking #{$billable->id}",
                'qty' => 1,
                'unit_price' => (float) $billable->amount,
                'subtotal' => (float) $billable->amount,
            ];
        }
        return $items;
    }
}
