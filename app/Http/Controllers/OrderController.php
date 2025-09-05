<?php

namespace App\Http\Controllers;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->with(['product', 'company'])->get();
        return response()->json($orders);
    }

    public function store(Request $request){
        $request->validate([
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|integer|min:1'
        ]);

        $order = Order::create([
            'user_id'=>auth()->user()->id,
            'product_id'=>$request->product_id,
            'company_id'=>auth()->user()->company_id,
            'quantity'=>$request->quantity
        ]);

        return response()->json($order,201);
    }
}
