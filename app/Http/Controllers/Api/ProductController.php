<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Product::orderBy('name')->get(['id', 'name', 'code', 'price', 'tax_percentage', 'stock']),
        ]);
    }

    public function lowStock(Request $request)
    {
        $threshold = (int) $request->query('threshold', 10);
        return response()->json([
            'threshold' => $threshold,
            'data' => Product::where('stock', '<', $threshold)->orderBy('stock')->get(['id', 'name', 'code', 'stock']),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());
        return response()->json(['data' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        return response()->json(['data' => $product]);
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();
            return response()->json(['message' => 'Product deleted.']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Cannot delete this product — it has existing order history.',
            ], 409);
        }
    }
}