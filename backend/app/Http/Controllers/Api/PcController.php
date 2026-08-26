<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pc;
use Illuminate\Http\Request;

class PcController extends Controller
{
    public function index(Request $request)
    {
        $q = Pc::query()->with('vendor.user');
        if ($vid = $request->input('vendor_id')) {
            $q->where('vendor_id', $vid);
        }
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->paginate(30));
    }

    public function show(Pc $pc)
    {
        return response()->json($pc->load(['vendor.user', 'activeSession.user']));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:100'],
            'identifier' => ['required', 'string', 'max:50', 'unique:pcs,identifier'],
            'specs' => ['nullable', 'string'],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:idle,in_use,maintenance,offline'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]);
        $this->authorizeVendor($request, $valid['vendor_id']);

        return response()->json(Pc::create($valid), 201);
    }

    public function update(Request $request, Pc $pc)
    {
        $this->authorizeVendor($request, $pc->vendor_id);
        $pc->update($request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'specs' => ['sometimes', 'nullable', 'string'],
            'hourly_rate' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:idle,in_use,maintenance,offline'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ]));
        return response()->json($pc->fresh());
    }

    public function destroy(Request $request, Pc $pc)
    {
        $this->authorizeVendor($request, $pc->vendor_id);
        $pc->delete();
        return response()->json(status: 204);
    }

    private function authorizeVendor(Request $request, int $vendorId): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }
        if ($request->user()->vendor && (int) $request->user()->vendor->id === $vendorId) {
            return;
        }
        abort(403, 'Not authorized');
    }
}
