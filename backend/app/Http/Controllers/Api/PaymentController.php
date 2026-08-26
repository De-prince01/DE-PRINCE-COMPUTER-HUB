<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(Request $request)
    {
        $q = Payment::query()->with(['invoice', 'user', 'vendor.user']);
        if ($iid = $request->input('invoice_id')) {
            $q->where('invoice_id', $iid);
        }
        if ($gateway = $request->input('gateway')) {
            $q->where('gateway', $gateway);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load(['invoice.billable', 'user', 'vendor.user']));
    }

    public function initiate(Request $request)
    {
        $valid = $request->validate([
            'invoice_number' => ['required', 'exists:invoices,number'],
            'gateway' => ['required', 'string'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'email' => ['sometimes', 'nullable', 'email'],
            'phone' => ['sometimes', 'nullable', 'string'],
        ]);

        $invoice = Invoice::where('number', $valid['invoice_number'])->firstOrFail();

        if ((float) $invoice->balance <= 0) {
            abort(409, 'Invoice has no outstanding balance');
        }

        try {
            $result = $this->payments->initiate($invoice, $valid['gateway'], [
                'currency' => $valid['currency'] ?? ($valid['gateway'] === Payment::GATEWAY_STRIPE ? 'USD' : 'NGN'),
                'email' => $valid['email'] ?? null,
                'phone' => $valid['phone'] ?? null,
            ]);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed', ['err' => $e->getMessage()]);
            abort(400, $e->getMessage());
        }
    }

    public function verify(Request $request, string $gateway)
    {
        try {
            if ($gateway === 'paystack') {
                $ref = $request->input('reference') ?? $request->input('trxref');
                abort_unless($ref, 422, 'reference required');
                $pay = $this->payments->confirmPaystack($ref);
                return response()->json(['payment' => $pay, 'invoice' => $pay->invoice]);
            }

            if ($gateway === 'flutterwave') {
                $txId = $request->input('transaction_id');
                if ($txId) {
                    $payload = $this->payments->verifyFlutterwaveTx($txId);
                    $pay = $this->payments->confirmFlutterwave($payload);
                    return response()->json(['payment' => $pay, 'invoice' => $pay->invoice, 'gateway_payload' => $payload]);
                }
                $txRef = $request->input('tx_ref');
                if (!$txRef) {
                    abort(422, 'transaction_id or tx_ref required');
                }
                $pay = Payment::where('gateway', Payment::GATEWAY_FLUTTERWAVE)
                    ->where('gateway_reference', $txRef)
                    ->firstOrFail();
                return response()->json(['payment' => $pay, 'invoice' => $pay->invoice]);
            }

            if ($gateway === 'stripe') {
                $sessionId = $request->input('session_id');
                $pay = Payment::where('gateway', Payment::GATEWAY_STRIPE)
                    ->where('gateway_reference', $sessionId)
                    ->firstOrFail();
                return response()->json(['payment' => $pay, 'invoice' => $pay->invoice]);
            }

            abort(422, 'Unsupported gateway');
        } catch (\Throwable $e) {
            Log::error('Payment verify failed', ['gateway' => $gateway, 'err' => $e->getMessage()]);
            abort(400, $e->getMessage());
        }
    }

    public function webhook(Request $request, string $gateway)
    {
        try {
            if ($gateway === 'stripe') {
                $signature = $request->header('Stripe-Signature', '');
                $pay = $this->payments->confirmStripeWebhook($request, $signature);
                return response()->json(['status' => 'ok', 'payment_id' => $pay->id]);
            }

            if ($gateway === 'paystack') {
                $signature = $request->header('X-Paystack-Signature', '');
                $expected = hash_hmac('sha512', $request->getContent(), config('services.paystack.secret'));
                if (!hash_equals($expected, $signature)) {
                    abort(403, 'Invalid signature');
                }
                $event = $request->input('event');
                if ($event === 'charge.success') {
                    $ref = $request->input('data.reference');
                    if ($ref) {
                        $this->payments->confirmPaystack($ref);
                    }
                }
                return response()->json(['status' => 'ok']);
            }

            if ($gateway === 'flutterwave') {
                $pay = $this->payments->confirmFlutterwave($request->all());
                return response()->json(['status' => 'ok', 'payment_id' => $pay->id]);
            }

            abort(422, 'Unsupported gateway');
        } catch (\Throwable $e) {
            Log::error("Webhook [{$gateway}] failed", ['err' => $e->getMessage(), 'payload' => $request->all()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
