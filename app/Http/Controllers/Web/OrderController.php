<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $query = Order::with(['product', 'buyer', 'vendor']);
        
        // If user is vendor, only show their orders
        if (auth()->user()->hasRole('vendor')) {
            $query->whereHas('product', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }
        // If user is customer, only show their orders
        elseif (auth()->user()->hasRole('customer')) {
            $query->where('buyer_id', auth()->id());
        }
        
        $orders = $query->paginate(10);
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $products = Product::all();
        return view('orders.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
        ]);

        $product = Product::findOrFail($request->product_id);

        try {
            DB::beginTransaction();

            $order = Order::create([
                'product_id' => $product->id,
                'buyer_id' => auth()->id(),
                'vendor_id' => $product->user_id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_amount' => $product->price * $request->quantity,
                'shipping_address' => $request->shipping_address,
                'status' => 0
            ]);

            DB::commit();

            return redirect()->route('orders.index')
                ->with('success', 'Order placed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to place order: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Order $order)
    {
        // Check if user can view this order
        if (!$this->canViewOrder($order)) {
            return redirect()->route('orders.index')
                ->with('error', 'You are not authorized to view this order');
        }

        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        // Only vendor can update order status
        if (!auth()->user()->hasRole('vendor') || $order->vendor_id !== auth()->id()) {
            return redirect()->route('orders.index')
                ->with('error', 'You are not authorized to update this order');
        }

        return view('orders.edit', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        // Only vendor can update order status
        if (!auth()->user()->hasRole('vendor') || $order->vendor_id !== auth()->id()) {
            return redirect()->route('orders.index')
                ->with('error', 'You are not authorized to update this order');
        }

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled'
        ]);

        $order->update([
            'status' => $request->status,
            'status_updated_at' => now()
        ]);

        return redirect()->route('orders.index')
            ->with('success', 'Order status updated successfully');
    }

    public function destroy(Order $order)
    {
        // Check if user can delete this order
        if (!$this->canDeleteOrder($order)) {
            return redirect()->route('orders.index')
                ->with('error', 'You are not authorized to delete this order');
        }

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Order deleted successfully');
    }

    private function canViewOrder(Order $order)
    {
        $user = auth()->user();
        
        // Admin can view all orders
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }
        
        // Vendor can view their own orders
        if ($user->hasRole('vendor')) {
            return $order->vendor_id === $user->id;
        }
        
        // Customer can view their own orders
        if ($user->hasRole('customer')) {
            return $order->buyer_id === $user->id;
        }
        
        return false;
    }

    private function canDeleteOrder(Order $order)
    {
        $user = auth()->user();
        
        // Admin can delete any order
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }
        
        // Vendor can delete their own orders that are not yet processed
        if ($user->hasRole('vendor')) {
            return $order->vendor_id === $user->id && $order->status === 0;
        }
        
        // Customer can delete their own pending orders
        if ($user->hasRole('customer')) {
            return $order->buyer_id === $user->id && $order->status === 0;
        }
        
        return false;
    }
}