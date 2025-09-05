<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\VendorDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    public function submitVendorRequest(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_type' => 'required|string',
                'gst_number' => 'required|string',
                'pan_number' => 'required|string',
                'business_address' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string',
                'pincode' => 'required|string',
                'contact_person_name' => 'required|string',
                'contact_person_phone' => 'required|string',
                'alternate_phone' => 'nullable|string',
                'bank_name' => 'required|string',
                'account_number' => 'required|string',
                'ifsc_code' => 'required|string',
                'branch_name' => 'required|string',
                'gst_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'pan_card' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'business_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
                'bank_statement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Upload documents
            $gstPath = $request->file('gst_certificate')->store('vendor_documents/gst', 'public');
            $panPath = $request->file('pan_card')->store('vendor_documents/pan', 'public');
            $licensePath = $request->file('business_license')->store('vendor_documents/license', 'public');
            $bankStatementPath = $request->file('bank_statement')->store('vendor_documents/bank', 'public');

            // Create vendor details
            $vendorDetail = VendorDetail::create([
                'user_id' => mt_rand(10000, 99999), // Generate random user_id between 10000-99999
                'business_type' => $request->business_type,
                'gst_number' => $request->gst_number,
                'pan_number' => $request->pan_number,
                'business_address' => $request->business_address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'contact_person_name' => $request->contact_person_name,
                'contact_person_phone' => $request->contact_person_phone,
                'alternate_phone' => $request->alternate_phone,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'ifsc_code' => $request->ifsc_code,
                'branch_name' => $request->branch_name,
                'gst_certificate_path' => $gstPath,
                'pan_card_path' => $panPath,
                'business_license_path' => $licensePath,
                'bank_statement_path' => $bankStatementPath,
                'status' => 0
            ]);



            return response()->json([
                'status' => 'success',
                'message' => 'Vendor request submitted successfully',
                'data' => $vendorDetail
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Vendor request submission failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit vendor request'
            ], 500);
        }
    }

    public function getVendorDetails()
    {
        try {
            $vendorDetail = VendorDetail::where('user_id', auth()->id())->first();
            
            if (!$vendorDetail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor details not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $vendorDetail
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get vendor details: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get vendor details'
            ], 500);
        }
    }
}