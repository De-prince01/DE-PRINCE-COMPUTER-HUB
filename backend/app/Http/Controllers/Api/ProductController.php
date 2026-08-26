<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()->with('vendor.user')->where('is_active', true);
        if ($vid = $request->input('vendor_id')) {
            $q->where('vendor_id', $vid);
        }
        if ($s = $request->string('search')->toString()) {
            $q->where('name', 'ILIKE', "%{$s}%")
                ->orWhere('description', 'ILIKE', "%{$s}%");
        }
        return response()->json($q->paginate(20));
    }

    public function show(Product $product)
    {
        return response()->json($product->load('vendor.user'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'nullable', 'array'],
        ]);

        if (!$request->user()->isAdmin() && (!$request->user()->vendor || (int) $request->user()->vendor->id !== (int) $valid['vendor_id']) {
            abort(403);
        }

        return response()->json(Product::create($valid), 201);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwn($request, $product);
        $product->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'nullable', 'array'],
        ]));
        return response()->json($product->fresh());
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwn($request, $product);
        $product->delete();
        return response()->json(status: 204);
    }

    private function authorizeOwn(Request $request, Product $product): void
    {
        if ($request->user()->isAdmin()) {
            return;
        }
        if ($request->user()->vendor && (int) $request->user()->vendor->id === (int) $product->vendor_id) {
            return;
        }
        abort(403);
    }
}
