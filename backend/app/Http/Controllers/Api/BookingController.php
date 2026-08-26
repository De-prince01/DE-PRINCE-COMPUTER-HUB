<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $q = Booking::query()->with(['pc.vendor.user', 'user']);
        if ($vid = $request->input('vendor_id')) {
            $q->where('vendor_id', $vid);
        }
        if ($pcId = $request->input('pc_id')) {
            $q->where('pc_id', $pcId);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('starts_at')->paginate(20));
    }

    public function show(Booking $booking)
    {
        return response()->json($booking->load(['pc.vendor.user', 'user', 'invoices.payments']));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'pc_id' => ['nullable', 'exists:pcs,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($valid['pc_id'] ?? null) {
            $conflict = Booking::where('pc_id', $valid['pc_id'])
                ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
                ->where(function ($q) use ($valid) {
                    $q->whereBetween('starts_at', [$valid['starts_at'], $valid['ends_at']])
                        ->orWhereBetween('ends_at', [$valid['starts_at'], $valid['ends_at']])
                        ->orWhere(function ($q2) use ($valid) {
                            $q2->where('starts_at', '<=', $valid['starts_at'])
                                ->where('ends_at', '>=', $valid['ends_at']);
                        });
                })
                ->exists();
            if ($conflict) {
                abort(409, 'PC is already booked for this time range');
            }
        }

        $booking = Booking::create([...$valid, 'status' => 'pending']);
        return response()->json($booking->fresh()->load(['pc', 'vendor']), 201);
    }

    public function confirm(Request $request, Booking $booking)
    {
        if ($booking->status !== 'pending') {
            abort(409, 'Booking not in pending state');
        }
        $booking->update(['status' => 'confirmed']);
        return response()->json($booking->fresh());
    }

    public function cancel(Request $request, Booking $booking)
    {
        if (in_array($booking->status, ['completed'], true)) {
            abort(409, 'Booking already completed');
        }
        $booking->update(['status' => 'cancelled']);
        return response()->json($booking->fresh());
    }
}
