<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
    
class ProductController extends Controller
{
    public function index()
    {
        $query = Product::with('user');
        
        // If user is vendor, only show their products
        if (auth()->user()->hasRole('vendor')) {
            $query->where('user_id', auth()->id());
        }
        
        $products = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => [
                        'raw' => $product->price,
                        'formatted' => '₹' . number_format($product->price, 2)
                    ],
                    'user_id' => $product->user_id,
                    'vendor' => $product->user ? [
                        'id' => $product->user->id,
                        'name' => $product->user->name,
                        'email' => $product->user->email
                    ] : null,
                    'created_at' => [
                        'raw' => $product->created_at,
                        'formatted' => $product->created_at->format('d M Y, h:i A')
                    ]
                ];
            }),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->price = $request->price;
        $product->user_id = auth()->id();
        $product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => [
                    'raw' => $product->price,
                    'formatted' => '₹' . number_format($product->price, 2)
                ],
                'vendor' => [
                    'id' => $product->user->id,
                    'name' => $product->user->name,
                    'email' => $product->user->email
                ],
                'created_at' => [
                    'raw' => $product->created_at,
                    'formatted' => $product->created_at->format('d M Y, h:i A')
                ]
            ]
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        // Check if user can edit this product
        if (auth()->user()->hasRole('vendor') && $product->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to edit this product'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0'
        ]);

        $product->update($request->only(['name', 'price']));

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => [
                    'raw' => $product->price,
                    'formatted' => '₹' . number_format($product->price, 2)
                ],
                'vendor' => [
                    'id' => $product->user->id,
                    'name' => $product->user->name,
                    'email' => $product->user->email
                ],
                'created_at' => [
                    'raw' => $product->created_at,
                    'formatted' => $product->created_at->format('d M Y, h:i A')
                ]
            ]
        ]);
    }

    public function destroy(Product $product)
    {
        // Check if user can delete this product
        if (auth()->user()->hasRole('vendor') && $product->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this product'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}