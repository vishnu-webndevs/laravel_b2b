<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $query = Order::with(['user', 'products']);
        
        // If user is vendor, only show orders containing their products
        if (auth()->user()->hasRole('vendor')) {
            $query->whereHas('products', function($q) {
                $q->where('user_id', auth()->id());
            });
        }
        
        $orders = $query->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user' => [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email
                    ],
                    'products' => $order->products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => [
                                'raw' => $product->price,
                                'formatted' => '₹' . number_format($product->price, 2)
                            ],
                            'quantity' => $product->pivot->quantity,
                            'vendor' => [
                                'id' => $product->user->id,
                                'name' => $product->user->name
                            ]
                        ];
                    }),
                    'total_amount' => [
                        'raw' => $order->total_amount,
                        'formatted' => '₹' . number_format($order->total_amount, 2)
                    ],
                    'status' => $order->status,
                    'created_at' => [
                        'raw' => $order->created_at,
                        'formatted' => $order->created_at->format('d M Y, h:i A')
                    ]
                ];
            }),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|in:cod,online'
        ]);

        $totalAmount = 0;
        $products = [];

        foreach ($request->products as $item) {
            $product = Product::findOrFail($item['id']);
            $totalAmount += $product->price * $item['quantity'];
            $products[$item['id']] = ['quantity' => $item['quantity']];
        }

        $order = Order::create([
            'user_id' => auth()->id(),
            'total_amount' => $totalAmount,
            'shipping_address' => $request->shipping_address,
            'payment_method' => $request->payment_method,
            'status' => 0
        ]);

        $order->products()->attach($products);

        return response()->json([
            'status' => 'success',
            'message' => 'Order placed successfully',
            'data' => $order
        ], 201);
    }

    public function show(Order $order)
    {
        // Check if user is authorized to view this order
        if (auth()->id() !== $order->user_id && 
            !auth()->user()->hasRole('admin') && 
            !$order->products()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $order->id,
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email
                ],
                'products' => $order->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => [
                            'raw' => $product->price,
                            'formatted' => '₹' . number_format($product->price, 2)
                        ],
                        'quantity' => $product->pivot->quantity,
                        'vendor' => [
                            'id' => $product->user->id,
                            'name' => $product->user->name
                        ]
                    ];
                }),
                'total_amount' => [
                    'raw' => $order->total_amount,
                    'formatted' => '₹' . number_format($order->total_amount, 2)
                ],
                'shipping_address' => $order->shipping_address,
                'payment_method' => $order->payment_method,
                'status' => $order->status,
                'created_at' => [
                    'raw' => $order->created_at,
                    'formatted' => $order->created_at->format('d M Y, h:i A')
                ]
            ]
        ]);
    }

    public function update(Request $request, Order $order)
    {
        // Only admin or vendor can update order status
        if (!auth()->user()->hasRole('admin') && 
            !$order->products()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled'
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    public function destroy(Order $order)
    {
        // Only admin can delete orders
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $order->products()->detach();
        $order->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Order deleted successfully'
        ]);
    }
}