<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Register user
    public function register(Request $request){
        $request->validate([
            'name'=>'required',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|min:6',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);

        // Assign default role
        $user->assignRole('customer');

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()->name ?? 'customer'
                ]
            ]
        ], 201);
    }

    // Login user
    public function login(Request $request){
        try {
            \Log::info('Login attempt', [
                'email' => $request->email
            ]);

            $request->validate([
                'email'=>'required|email',
                'password'=>'required'
            ]);

            $user = User::where('email',$request->email)->first();

            if(!$user || !Hash::check($request->password, $user->password)){
                \Log::warning('Login failed - Invalid credentials', [
                    'email' => $request->email
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            \Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->roles->first()->name ?? 'customer'
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->roles->first()->name ?? 'customer'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Login error', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Logout user (revoke current token)
    public function logout(Request $request)
    {
        try {
            \Log::info('Logout attempt', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);
            
            $request->user()->currentAccessToken()->delete();
            
            \Log::info('Logout successful', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);
            
            return response()->json(['message' => 'Successfully logged out'], 200);
        } catch (\Exception $e) {
            \Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? 'unknown',
                'email' => $request->user()->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['message' => 'Error during logout', 'error' => $e->getMessage()], 500);
        }
    }
}
