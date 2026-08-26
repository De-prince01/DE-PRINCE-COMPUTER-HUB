<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'paystack' => [
        'publicKey' => env('PAYSTACK_PUBLIC_KEY'),
        'secret' => env('PAYSTACK_SECRET_KEY'),
        'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        'merchantEmail' => env('MERCHANT_EMAIL'),
        'url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
    ],
    'flutterwave' => [
        'public' => env('FLW_PUBLIC_KEY'),
        'secret' => env('FLW_SECRET_KEY'),
        'encryption' => env('FLW_ENCRYPTION_KEY'),
        'env' => env('FLW_ENV', 'staging'),
        'hash_secret' => env('FLW_HASH_SECRET'),
    ],
];
