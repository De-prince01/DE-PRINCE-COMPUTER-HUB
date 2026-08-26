<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Webhook as StripeWebhook;

class PaymentService
{
    public function initiate(Invoice $invoice, string $gateway, array $metadata = []): array
    {
        if (!in_array($gateway, [Payment::GATEWAY_STRIPE, Payment::GATEWAY_PAYSTACK, Payment::GATEWAY_FLUTTERWAVE, Payment::GATEWAY_WALLET, Payment::GATEWAY_CASH], true)) {
            throw new \InvalidArgumentException('Unsupported gateway: ' . $gateway);
        }

        $callbackUrl = config('app.frontend_url') . '/payments/redirect?gateway=' . $gateway . '&invoice=' . $invoice->number;
        $cancelUrl = config('app.frontend_url') . '/payments/cancel?invoice=' . $invoice->number;

        $pay = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'vendor_id' => $invoice->vendor_id,
            'gateway' => $gateway,
            'gateway_reference' => 'tmp-' . uniqid('', true),
            'customer_email' => $invoice->user?->email ?: ($metadata['email'] ?? null),
            'customer_phone' => $metadata['phone'] ?? null,
            'currency' => $metadata['currency'] ?? ($gateway === Payment::GATEWAY_STRIPE ? 'USD' : 'NGN'),
            'amount' => $invoice->balance,
            'status' => Payment::STATUS_PENDING,
            'metadata' => $metadata,
        ]);

        $result = match ($gateway) {
            Payment::GATEWAY_STRIPE => $this->initStripe($invoice, $pay, $callbackUrl, $cancelUrl),
            Payment::GATEWAY_PAYSTACK => $this->initPaystack($invoice, $pay, $callbackUrl),
            Payment::GATEWAY_FLUTTERWAVE => $this->initFlutterwave($invoice, $pay, $callbackUrl, $cancelUrl),
            Payment::GATEWAY_CASH => $this->initCash($pay),
            Payment::GATEWAY_WALLET => $this->initWallet($invoice, $pay),
            default => throw new \RuntimeException('Gateway not implemented'),
        };

        return ['payment' => $pay->fresh(), ...$result];
    }

    private function initStripe(Invoice $invoice, Payment $pay, string $callbackUrl, string $cancelUrl): array
    {
        $stripe = new StripeClient(config('services.stripe.secret'));
        $amountCents = (int) bcmul((string) $pay->amount, '100', 0);
        $checkout = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $pay->currency,
                    'unit_amount' => $amountCents,
                    'product_data' => ['name' => "Invoice {$invoice->number}"],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $callbackUrl . '&session_id={CHECKOUT_SESSION_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $pay->id,
            'metadata' => ['invoice_number' => $invoice->number, 'payment_id' => (string) $pay->id],
        ]);

        $pay->update(['gateway_reference' => $checkout->id]);

        return [
            'checkout_url' => $checkout->url,
            'gateway_payload' => ['session_id' => $checkout->id],
        ];
    }

    private function initPaystack(Invoice $invoice, Payment $pay, string $callbackUrl): array
    {
        $email = $pay->customer_email ?? 'pay@deprincehub.ng';
        $amountKobo = (int) bcmul((string) $pay->amount, '100', 0);

        $resp = Http::withToken(config('services.paystack.secret'))
            ->asJson()
            ->post(rtrim(config('services.paystack.url', 'https://api.paystack.co'), '/') . '/transaction/initialize', [
                'email' => $email,
                'amount' => $amountKobo,
                'currency' => $pay->currency,
                'reference' => 'PSK-' . $pay->id . '-' . uniqid(),
                'callback_url' => $callbackUrl,
                'metadata' => [
                    'invoice_number' => $invoice->number,
                    'payment_id' => $pay->id,
                    'customer_name' => $invoice->user?->name,
                ],
            ]);

        if (!$resp->successful() || !($resp['status'] ?? false)) {
            Log::error('Paystack init failed', ['resp' => $resp->json(), 'payment' => $pay->id]);
            throw new \RuntimeException($resp['message'] ?? 'Paystack init failed');
        }

        $pay->update(['gateway_reference' => $resp['data']['reference']]);

        return [
            'checkout_url' => $resp['data']['authorization_url'],
            'gateway_payload' => [
                'reference' => $resp['data']['reference'],
                'access_code' => $resp['data']['access_code'] ?? null,
            ],
        ];
    }

    private function initFlutterwave(Invoice $invoice, Payment $pay, string $callbackUrl, string $cancelUrl): array
    {
        $txRef = 'FLW-' . $pay->id . '-' . uniqid();
        $pay->update(['gateway_reference' => $txRef]);

        $base = config('app.frontend_url');
        return [
            'checkout_url' => $base . '/payments/flutterwave-checkout?tx_ref=' . $txRef,
            'gateway_payload' => [
                'tx_ref' => $txRef,
                'public_key' => config('services.flutterwave.public'),
                'amount' => $pay->amount,
                'currency' => $pay->currency,
                'customer' => [
                    'email' => $pay->customer_email ?? 'pay@deprincehub.ng',
                    'phone_number' => $pay->customer_phone,
                    'name' => $invoice->user?->name ?? 'Customer',
                ],
                'customizations' => [
                    'title' => 'Invoice ' . $invoice->number,
                    'description' => 'DE-PRINCE HUB payment',
                    'logo' => asset('images/logo.png'),
                ],
                'redirect_url' => $callbackUrl,
                'cancel_url' => $cancelUrl,
                'meta' => ['invoice_number' => $invoice->number, 'payment_id' => $pay->id],
            ],
        ];
    }

    private function initCash(Payment $pay): array
    {
        $pay->update([
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
            'fee' => 0,
            'settled' => $pay->amount,
        ]);
        $this->applyToInvoice($pay);
        return ['checkout_url' => null, 'gateway_payload' => []];
    }

    private function initWallet(Invoice $invoice, Payment $pay): array
    {
        $vendor = $invoice->vendor;
        if (!$vendor || (float) $vendor->balance < (float) $pay->amount) {
            throw new \RuntimeException('Insufficient wallet balance');
        }

        \DB::transaction(function () use ($vendor, $pay) {
            $vendor->decrement('balance', $pay->amount);
            $pay->update([
                'status' => Payment::STATUS_SUCCEEDED,
                'paid_at' => now(),
                'fee' => 0,
                'settled' => $pay->amount,
            ]);
            $this->applyToInvoice($pay);
        });

        return ['checkout_url' => null, 'gateway_payload' => []];
    }

    public function applyToInvoice(Payment $pay): void
    {
        $invoice = $pay->invoice()->lockForUpdate()->first();
        if (!$invoice) {
            return;
        }
        $invoice->amount_paid = (float) bcadd((string) $invoice->amount_paid, (string) $pay->settled, 2);
        $invoice->recalcTotals()->save();
    }

    public function confirmPaystack(string $reference): Payment
    {
        $pay = Payment::where('gateway', Payment::GATEWAY_PAYSTACK)
            ->where('gateway_reference', $reference)
            ->firstOrFail();

        $resp = Http::withToken(config('services.paystack.secret'))
            ->get(rtrim(config('services.paystack.url', 'https://api.paystack.co'), '/') . '/transaction/verify/' . urlencode($reference));

        if (!$resp->successful() || !($resp['status'] ?? false)) {
            throw new \RuntimeException($resp['message'] ?? 'Paystack verification failed');
        }

        $data = $resp['data'];
        $success = ($data['status'] ?? '') === 'success';
        $gatewayResponse = $resp->json();

        \DB::transaction(function () use ($pay, $data, $success, $gatewayResponse) {
            $pay->gateway_response = $gatewayResponse;
            $pay->fee = $data['fees'] ?? 0;
            $pay->settled = (float) bcsub((string) $pay->amount, (string) ($data['fees_charge'] ?? ($data['fees'] ?? 0)), 2);
            if ($success) {
                $pay->status = Payment::STATUS_SUCCEEDED;
                $pay->paid_at = now();
                $pay->save();
                $this->applyToInvoice($pay);
            } else {
                $pay->status = Payment::STATUS_FAILED;
                $pay->save();
            }
        });

        return $pay->fresh();
    }

    public function confirmStripeWebhook(Request $request, string $signature): Payment
    {
        $secret = config('services.stripe.webhook_secret');
        $event = StripeWebhook::constructEvent($request->getContent(), $signature, $secret);

        if ($event->type !== 'checkout.session.completed' && $event->type !== 'payment_intent.succeeded') {
            throw new \RuntimeException('Unhandled Stripe event: ' . $event->type);
        }

        $object = $event->data->object;
        $ref = $object->payment_intent ?? $object->id;
        $pay = Payment::where('gateway', Payment::GATEWAY_STRIPE)
            ->where(function ($q) use ($ref, $object) {
                $q->where('gateway_reference', $ref)
                    ->orWhere('gateway_reference', $object->id);
            })
            ->first();

        if (!$pay && isset($object->metadata?->payment_id)) {
            $pay = Payment::find($object->metadata->payment_id);
        }

        if (!$pay) {
            throw new \RuntimeException('Stripe payment not found for ref: ' . $ref);
        }

        \DB::transaction(function () use ($pay, $event, $object) {
            $pay->gateway_response = (array) $event;
            $pay->fee = isset($object->total_details?->amount_fee) ? ($object->total_details->amount_fee / 100) : 0;
            $pay->settled = (float) bcsub((string) $pay->amount, (string) $pay->fee, 2);
            $pay->status = Payment::STATUS_SUCCEEDED;
            $pay->paid_at = now();
            $pay->save();
            $this->applyToInvoice($pay);
        });

        return $pay->fresh();
    }

    public function confirmFlutterwave(array $payload): Payment
    {
        $secret = config('services.flutterwave.secret');
        $providedHash = $payload['verif-hash'] ?? $_SERVER['HTTP_VERIF_HASH'] ?? null;

        if ($providedHash && $providedHash !== config('services.flutterwave.hash_secret')) {
            throw new \RuntimeException('Invalid Flutterwave hash');
        }

        $data = $payload['data'] ?? $payload;
        $txRef = $data['tx_ref'] ?? null;
        $status = $data['status'] ?? ($data['payment_status'] ?? null);

        $pay = Payment::where('gateway', Payment::GATEWAY_FLUTTERWAVE)
            ->where('gateway_reference', $txRef)
            ->first();

        if (!$pay && isset($data['meta']['payment_id'])) {
            $pay = Payment::find($data['meta']['payment_id']);
        }

        if (!$pay) {
            throw new \RuntimeException('Flutterwave payment not found: ' . $txRef);
        }

        $charged = (float) ($data['charged_amount'] ?? $data['amount'] ?? $pay->amount);
        $fee = (float) ($data['app_fee'] ?? 0);

        \DB::transaction(function () use ($pay, $payload, $status, $charged, $fee) {
            $pay->gateway_response = $payload;
            $pay->fee = $fee;
            $pay->settled = (float) bcsub((string) $charged, (string) $fee, 2);
            if (in_array(strtolower($status), ['successful', 'success'], true)) {
                $pay->status = Payment::STATUS_SUCCEEDED;
                $pay->paid_at = now();
                $pay->save();
                $this->applyToInvoice($pay);
            } else {
                $pay->status = Payment::STATUS_FAILED;
                $pay->save();
            }
        });

        return $pay->fresh();
    }

    public function verifyFlutterwaveTx(string $txId): array
    {
        $resp = Http::withToken(config('services.flutterwave.secret'))
            ->get('https://api.flutterwave.com/v3/transactions/' . urlencode($txId) . '/verify');

        if (!$resp->successful() || ($resp['status'] ?? 'error') !== 'success') {
            throw new \RuntimeException($resp['message'] ?? 'Flutterwave verify failed');
        }
        return $resp->json();
    }
}
