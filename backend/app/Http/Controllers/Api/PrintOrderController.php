<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintOrder;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class PrintOrderController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function index(Request $request)
    {
        $q = PrintOrder::query()->with(['vendor.user', 'user']);
        if ($vid = $request->input('vendor_id')) {
            $q->where('vendor_id', $vid);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function show(PrintOrder $order)
    {
        return response()->json($order->load(['vendor.user', 'user', 'invoices.payments']));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'type' => ['required', 'in:black_white,color,photo,large_format'],
            'pages' => ['required', 'integer', 'min:1'],
            'copies' => ['required', 'integer', 'min:1'],
            'double_sided' => ['sometimes', 'boolean'],
            'file_path' => ['sometimes', 'nullable', 'string'],
            'file_name' => ['sometimes', 'nullable', 'string'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $valid['amount'] = 0;
        $order = PrintOrder::create($valid);
        $order->calculateAmount();
        $order->save();

        $invoice = $this->invoices->createFor($order, ['status' => 'unpaid']);

        return response()->json([
            'order' => $order->fresh()->load('vendor'),
            'invoice' => $invoice,
        ], 201);
    }

    public function markPaid(Request $request, PrintOrder $order)
    {
        if (!in_array($order->status, ['pending', 'printing'], true)) {
            abort(409, 'Order status does not allow marking paid');
        }
        $order->update(['status' => 'paid']);
        return response()->json($order->fresh());
    }

    public function updateStatus(Request $request, PrintOrder $order)
    {
        $order->update($request->validate([
            'status' => ['required', 'in:pending,paid,printing,ready,delivered,cancelled,refunded'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]));
        return response()->json($order->fresh());
    }
}
