<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $query = Product::with('user');
        
        if (auth()->user()->hasRole('super-admin')) {
            // Super admin can see all products including soft deleted ones
            $query = Product::withTrashed()->with('user');
        } elseif (auth()->user()->hasRole('vendor')) {
            // Vendors can only see their own active products
            $query->where('user_id', auth()->id());
        }
        
        $products = $query->paginate(10);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
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

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully');
    }

    public function edit(Product $product)
    {
        // Check if user can edit this product
        if (auth()->user()->hasRole('vendor') && $product->user_id !== auth()->id()) {
            return redirect()->route('products.index')
                ->with('error', 'You are not authorized to edit this product');
        }

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        // Check if user can edit this product
        if (auth()->user()->hasRole('vendor') && $product->user_id !== auth()->id()) {
            return redirect()->route('products.index')
                ->with('error', 'You are not authorized to edit this product');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        $product->update($request->only(['name', 'price']));

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully');
    }

    public function destroy(Product $product)
    {
        // Only super admin can delete products
        if (!auth()->user()->hasRole('super-admin')) {
            return redirect()->route('products.index')
                ->with('error', 'Only super admin can delete products');
        }

        $product->delete(); // This will soft delete the product

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    }

    // Method to permanently delete a product (only for super admin)
    public function forceDelete($id)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return redirect()->route('products.index')
                ->with('error', 'Only super admin can permanently delete products');
        }

        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();

        return redirect()->route('products.index')
            ->with('success', 'Product permanently deleted');
    }

    // Method to restore a soft deleted product (only for super admin)
    public function restore($id)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return redirect()->route('products.index')
                ->with('error', 'Only super admin can restore products');
        }

        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('products.index')
            ->with('success', 'Product restored successfully');
    }
}
 