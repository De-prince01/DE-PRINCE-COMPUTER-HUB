<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/ping', function () {
    return response()->json(['message' => 'DE-PRINCE HUB API v1', 'time' => now()->toIso8601String()]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum')->name('api.auth.me');
});

Route::prefix('webhooks')->group(function () {
    Route::post('/stripe', [\App\Http\Controllers\Api\PaymentController::class, 'webhook'])->defaults('gateway', 'stripe');
    Route::post('/paystack', [\App\Http\Controllers\Api\PaymentController::class, 'webhook'])->defaults('gateway', 'paystack');
    Route::post('/flutterwave', [\App\Http\Controllers\Api\PaymentController::class, 'webhook'])->defaults('gateway', 'flutterwave');
});

Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class)->only(['index', 'show']);
Route::apiResource('pcs', \App\Http\Controllers\Api\PcController::class)->only(['index', 'show']);
Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)->only(['index', 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class)->except(['index', 'show']);
    Route::apiResource('pcs', \App\Http\Controllers\Api\PcController::class)->except(['index', 'show']);
    Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)->except(['index', 'show']);

    Route::get('sessions', [\App\Http\Controllers\Api\CyberSessionController::class, 'index']);
    Route::get('sessions/{session}', [\App\Http\Controllers\Api\CyberSessionController::class, 'show']);
    Route::post('sessions', [\App\Http\Controllers\Api\CyberSessionController::class, 'start']);
    Route::post('sessions/{session}/stop', [\App\Http\Controllers\Api\CyberSessionController::class, 'stop']);
    Route::post('sessions/{session}/pause', [\App\Http\Controllers\Api\CyberSessionController::class, 'pause']);
    Route::post('sessions/{session}/resume', [\App\Http\Controllers\Api\CyberSessionController::class, 'resume']);

    Route::apiResource('print-orders', \App\Http\Controllers\Api\PrintOrderController::class);
    Route::post('print-orders/{order}/mark-paid', [\App\Http\Controllers\Api\PrintOrderController::class, 'markPaid']);
    Route::post('print-orders/{order}/status', [\App\Http\Controllers\Api\PrintOrderController::class, 'updateStatus']);

    Route::apiResource('bookings', \App\Http\Controllers\Api\BookingController::class);
    Route::post('bookings/{booking}/confirm', [\App\Http\Controllers\Api\BookingController::class, 'confirm']);
    Route::post('bookings/{booking}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'cancel']);

    Route::get('invoices', [\App\Http\Controllers\Api\InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/void', [\App\Http\Controllers\Api\InvoiceController::class, 'markVoid']);

    Route::get('payments', [\App\Http\Controllers\Api\PaymentController::class, 'index']);
    Route::get('payments/{payment}', [\App\Http\Controllers\Api\PaymentController::class, 'show']);
    Route::post('payments/initiate', [\App\Http\Controllers\Api\PaymentController::class, 'initiate']);
    Route::get('payments/verify/{gateway}', [\App\Http\Controllers\Api\PaymentController::class, 'verify']);
});
