<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::query()->with(['billable', 'payments']);
        if ($uid = $request->input('user_id')) {
            $q->where('user_id', $uid);
        }
        if ($vid = $request->input('vendor_id')) {
            $q->where('vendor_id', $vid);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['billable', 'payments', 'user', 'vendor.user']));
    }

    public function markVoid(Invoice $invoice)
    {
        if (in_array($invoice->status, ['paid', 'refunded'], true)) {
            abort(409, 'Cannot void a paid or refunded invoice');
        }
        $invoice->update(['status' => 'void']);
        return response()->json($invoice->fresh());
    }
}
