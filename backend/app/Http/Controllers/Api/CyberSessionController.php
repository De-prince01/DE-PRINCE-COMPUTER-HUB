<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CyberSession;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class CyberSessionController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices)
    {
    }

    public function index(Request $request)
    {
        $q = CyberSession::query()->with(['pc', 'user', 'invoices']);
        if ($pid = $request->input('pc_id')) {
            $q->where('pc_id', $pid);
        }
        if ($uid = $request->input('user_id')) {
            $q->where('user_id', $uid);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function show(CyberSession $session)
    {
        return response()->json($session->load(['pc.vendor.user', 'user', 'invoices.payments']));
    }

    public function start(Request $request)
    {
        $valid = $request->validate([
            'pc_id' => ['required', 'exists:pcs,id'],
            'user_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'hourly_rate' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $pc = \App\Models\Pc::findOrFail($valid['pc_id']);
        if ($pc->status === 'in_use') {
            abort(409, 'PC is already in use');
        }

        if (!isset($valid['hourly_rate'])) {
            $valid['hourly_rate'] = $pc->hourly_rate;
        }

        $session = CyberSession::create([...$valid, 'status' => 'active']);
        $pc->update(['status' => 'in_use']);

        return response()->json($session->fresh()->load('pc'), 201);
    }

    public function stop(Request $request, CyberSession $session)
    {
        if ($session->status !== 'active') {
            abort(409, 'Session is not active');
        }

        $session->ended_at = now();
        $session->calculateAmount();
        $session->status = 'completed';
        $session->save();

        if ($session->pc) {
            $session->pc->update(['status' => 'idle']);
        }

        $invoice = $this->invoices->createFor($session);

        return response()->json([
            'session' => $session->fresh()->load('pc'),
            'invoice' => $invoice->load('payments'),
        ]);
    }

    public function pause(CyberSession $session)
    {
        if ($session->status !== 'active') {
            abort(409, 'Session not active');
        }
        $session->update(['status' => 'paused']);
        return response()->json($session->fresh());
    }

    public function resume(CyberSession $session)
    {
        if ($session->status !== 'paused') {
            abort(409, 'Session not paused');
        }
        $session->update(['status' => 'active']);
        return response()->json($session->fresh());
    }
}
