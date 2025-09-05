<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VendorRequestController extends Controller
{
    public function create()
    {
        return view('vendor-request.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->company_name = $request->company_name;
        $user->company_address = $request->company_address;
        $user->save();

        // Create a new VendorRequest entry
        \App\Models\VendorRequest::create([
            'user_id' => $user->id,
            'business_name' => $request->company_name,
            'business_type' => 'Individual',
            'gst_number' => 'N/A',
            'pan_number' => 'N/A',
            'address' => $request->company_address,
            'city' => 'N/A',
            'state' => 'N/A',
            'pincode' => 'N/A',
            'bank_name' => 'N/A',
            'account_number' => 'N/A',
            'ifsc_code' => 'N/A',
            'status' => 0,
        ]);

        return redirect()->route('dashboard')->with('success', 'Vendor request submitted successfully.');
    }

    // API to request vendor status
    public function requestVendor(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->save();
            \App\Models\VendorRequest::create([
                'user_id' => $user->id,
                'business_name' => $user->company_name ?? 'N/A',
                'business_type' => 'Individual',
                'gst_number' => 'N/A',
                'pan_number' => 'N/A',
                'address' => $user->company_address ?? 'N/A',
                'city' => 'N/A',
                'state' => 'N/A',
                'pincode' => 'N/A',
                'bank_name' => 'N/A',
                'account_number' => 'N/A',
                'ifsc_code' => 'N/A',
                'status' => 0,
            ]);
            $message = 'Vendor request updated for existing user.';
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            \App\Models\VendorRequest::create([
                'user_id' => $user->id,
                'business_name' => $request->company_name ?? 'N/A',
                'business_type' => 'Individual',
                'gst_number' => 'N/A',
                'pan_number' => 'N/A',
                'address' => $request->company_address ?? 'N/A',
                'city' => 'N/A',
                'state' => 'N/A',
                'pincode' => 'N/A',
                'bank_name' => 'N/A',
                'account_number' => 'N/A',
                'ifsc_code' => 'N/A',
                'status' => 0,
            ]);
            $message = 'Vendor request submitted successfully.';
        }

        // Optionally notify super admin here

        return response()->json(['message' => $message, 'user' => $user], 201);
    }
}
