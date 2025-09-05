<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VendorRequest;
use Illuminate\Http\Request;

class VendorRequestController extends Controller
{
    public function index()
    {
        $requests = VendorRequest::with('user')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'requests' => $requests
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string',
            'business_type' => 'required|string|max:255',
            'gst_number' => 'required|string|max:15',
        ]);

        $vendorRequest = VendorRequest::create([
            'user_id' => auth()->id(),
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'business_type' => $request->business_type,
            'gst_number' => $request->gst_number,
            'status' => 0
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vendor request submitted successfully',
            'data' => [
                'request' => $vendorRequest
            ]
        ], 201);
    }

    public function show(VendorRequest $vendorRequest)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'request' => $vendorRequest->load('user')
            ]
        ]);
    }

    public function update(Request $request, VendorRequest $vendorRequest)
    {
        if (!auth()->user()->hasRole(['admin', 'superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to update vendor requests'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string'
        ]);

        $vendorRequest->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'processed_by' => auth()->id(),
            'processed_at' => now()
        ]);

        if ($request->status === 'approved') {
            $vendorRequest->user->assignRole('vendor');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Vendor request ' . $request->status . ' successfully',
            'data' => [
                'request' => $vendorRequest->fresh()->load('user')
            ]
        ]);
    }

    public function destroy(VendorRequest $vendorRequest)
    {
        if (!auth()->user()->hasRole(['admin', 'superadmin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete vendor requests'
            ], 403);
        }

        $vendorRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vendor request deleted successfully'
        ]);
    }
}
