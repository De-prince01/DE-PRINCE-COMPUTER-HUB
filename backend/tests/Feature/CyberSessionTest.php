<?php

namespace Tests\Feature;

use App\Models\Pc;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CyberSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_and_stop_session_creates_invoice(): void
    {
        $vendorUser = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'password' => Hash::make('password123'),
            'role' => 'vendor',
        ]);

        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Vendor',
            'status' => 'approved',
        ]);

        $pc = Pc::create([
            'vendor_id' => $vendor->id,
            'name' => 'PC 1',
            'identifier' => 'PC-001',
            'hourly_rate' => 1000,
            'status' => 'idle',
        ]);

        $customer = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);

        Sanctum::actingAs($customer);

        $start = $this->postJson('/api/sessions', [
            'pc_id' => $pc->id,
        ]);

        $start->assertStatus(201)->assertJsonPath('status', 'active');
        $sessionId = $start->json('id');

        $stop = $this->postJson("/api/sessions/{$sessionId}/stop");
        $stop->assertStatus(200)->assertJsonStructure(['session', 'invoice']);

        $this->assertNotEmpty($stop->json('invoice.number'));
        $this->assertSame('completed', $stop->json('session.status'));
    }
}
