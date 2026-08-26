<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Pc;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@deprincehub.test'],
            ['name' => 'Admin', 'password' => 'password123', 'role' => 'admin']
        );

        $vendorUser = User::firstOrCreate(
            ['email' => 'vendor@deprincehub.test'],
            ['name' => 'Vendor', 'password' => 'password123', 'role' => 'vendor']
        );

        $vendor = Vendor::firstOrCreate(
            ['user_id' => $vendorUser->id],
            ['business_name' => 'Sample Vendor', 'status' => 'approved']
        );

        Pc::firstOrCreate(
            ['identifier' => 'PC-001'],
            ['vendor_id' => $vendor->id, 'name' => 'PC 1', 'hourly_rate' => 1000, 'status' => 'idle']
        );

        Pc::firstOrCreate(
            ['identifier' => 'PC-002'],
            ['vendor_id' => $vendor->id, 'name' => 'PC 2', 'hourly_rate' => 1200, 'status' => 'idle']
        );

        Product::firstOrCreate(
            ['vendor_id' => $vendor->id, 'slug' => 'sample-product'],
            ['name' => 'Sample Product', 'description' => 'Demo item', 'price' => 2500, 'stock' => 10, 'is_active' => true]
        );

        $invoice = Invoice::firstOrCreate(
            ['number' => 'INV-DEMO-0001'],
            [
                'user_id' => $admin->id,
                'vendor_id' => $vendor->id,
                'billable_type' => Vendor::class,
                'billable_id' => $vendor->id,
                'subtotal' => 5000,
                'tax' => 0,
                'discount' => 0,
                'total' => 5000,
                'amount_paid' => 0,
                'balance' => 5000,
                'line_items' => json_encode([['label' => 'Demo invoice', 'qty' => 1, 'unit_price' => 5000, 'subtotal' => 5000]]),
                'status' => 'unpaid',
                'due_at' => now()->addDays(7),
            ]
        );

        Payment::firstOrCreate(
            ['gateway_reference' => 'demo-ref-0001'],
            [
                'invoice_id' => $invoice->id,
                'user_id' => $admin->id,
                'vendor_id' => $vendor->id,
                'gateway' => 'stripe',
                'currency' => 'NGN',
                'amount' => 5000,
                'fee' => 0,
                'settled' => 0,
                'status' => 'pending',
            ]
        );
    }
}
