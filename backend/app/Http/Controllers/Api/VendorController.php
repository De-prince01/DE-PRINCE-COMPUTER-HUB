<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $q = Vendor::query()->with('user');
        if ($s = $request->string('search')->toString()) {
            $q->where('business_name', 'ILIKE', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'ILIKE', "%{$s}%"));
        }
        return response()->json($q->paginate(15));
    }

    public function show(Vendor $vendor)
    {
        return response()->json($vendor->load(['user', 'pcs', 'products']));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cac_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
        ]);

        $vendor = Vendor::create([
            'user_id' => $request->user()->id,
            ...$valid,
        ]);

        if ($request->user()->role !== 'admin') {
            $request->user()->forceFill(['role' => 'vendor'])->save();
        }

        return response()->json($vendor, 201);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->authorizeOwner($request, $vendor);
        $vendor->update($request->validate([
            'business_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'nullable', 'string'],
            'banner' => ['sometimes', 'nullable', 'string'],
            'cac_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bank_account' => ['sometimes', 'nullable', 'string', 'max:30'],
            'bank_account_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]));
        return response()->json($vendor->fresh());
    }

    public function destroy(Request $request, Vendor $vendor)
    {
        $this->authorizeOwner($request, $vendor);
        $vendor->delete();
        return response()->json(status: 204);
    }

    private function authorizeOwner(Request $request, Vendor $vendor): void
    {
        if ($request->user()->isAdmin() || (int) $vendor->user_id === (int) $request->user()->id) {
            return;
        }
        abort(403, 'Not authorized');
    }
}
