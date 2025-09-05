<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DeviceDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    protected $deviceDetectionService;

    public function __construct(DeviceDetectionService $deviceDetectionService)
    {
        $this->deviceDetectionService = $deviceDetectionService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        // Create token and persist device details
        $deviceInfo = $this->deviceDetectionService->getDeviceInfo($request);
        $newToken   = $user->createToken('auth_token', []);
        $tokenModel = $newToken->accessToken; // PersonalAccessToken model
        $tokenModel->forceFill([
            'device_name'        => $deviceInfo['device_name'],
            'device_type'        => $deviceInfo['device_type'],
            'browser'            => $deviceInfo['browser'],
            'ip_address'         => $deviceInfo['ip_address'],
            'device_fingerprint' => $request->header('X-Device-Fingerprint'),
        ])->save();
        $token = $newToken->plainTextToken;
        
        // Assign customer role
        $user->assignRole('customer');

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::make($request, [
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Get device fingerprint from headers
        $deviceFingerprint = $request->header('X-Device-Fingerprint');
        $deviceType = $request->header('X-Device-Type');
        $browser = $request->header('X-Browser');
        $platform = $request->header('X-Platform');

        if (!$deviceFingerprint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device fingerprint is required'
            ], 400);
        }

        // Capture device info
        $deviceInfo = $this->deviceDetectionService->getDeviceInfo($request);

        // Access token with device details
        $newToken   = $user->createToken('auth_token', []);
        $tokenModel = $newToken->accessToken;
        $tokenModel->forceFill([
            'device_name'        => $deviceInfo['device_name'],
            'device_type'        => $deviceInfo['device_type'],
            'browser'            => $deviceInfo['browser'],
            'ip_address'         => $deviceInfo['ip_address'],
            'device_fingerprint' => $deviceFingerprint,
        ])->save();
        $token = $newToken->plainTextToken;

        // Refresh token with same device details
        $newRefresh   = $user->createToken('refresh_token', ['refresh-token']);
        $refreshModel = $newRefresh->accessToken;
        $refreshModel->forceFill([
            'device_name'        => $deviceInfo['device_name'],
            'device_type'        => $deviceInfo['device_type'],
            'browser'            => $deviceInfo['browser'],
            'ip_address'         => $deviceInfo['ip_address'],
            'device_fingerprint' => $deviceFingerprint,
        ])->save();
        $refreshToken = $newRefresh->plainTextToken;

        // Set refresh token as HttpOnly cookie
        $cookie = cookie('refresh_token', $refreshToken, 60 * 24 * 30); // 30 days

        return response()->json([
            'status' => 'success',
            'message' => 'User logged in successfully',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ])->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        // Delete all tokens
        $request->user()->tokens()->delete();

        $response = response()->json([
            'status' => 'success',
            'message' => 'User logged out successfully'
        ]);

        // Remove refresh token cookie
        return $response->cookie(
            'refresh_token',
            '',
            -1,
            '/'
        );
    }

    public function refreshToken(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            
            if (!$refreshToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Refresh token not found'
                ], 401);
            }

            // Get token ID and user ID from refresh token
            $tokenId = explode('|', $refreshToken)[0];
            $token = PersonalAccessToken::findOrFail($tokenId);
            $user = $token->tokenable;

            if (!$token || !$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid refresh token'
                ], 401);
            }

            // Check if token has refresh-token ability
            if (!$token->can('refresh-token')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token type'
                ], 401);
            }

            // Get device fingerprint from headers
            $deviceFingerprint = $request->header('X-Device-Fingerprint');
            
            // Verify device fingerprint matches the one stored with the refresh token
            if ($token->device_fingerprint !== $deviceFingerprint) {
                $token->delete();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid device. Please login again.'
                ], 401);
            }

            // Create new tokens
            $newToken = $user->createToken('auth_token', [])->plainTextToken;
            $newRefreshToken = $user->createToken('refresh_token', ['refresh-token'])->plainTextToken;

            // Delete old refresh token
            $token->delete();

            // Set new refresh token as HttpOnly cookie
            $cookie = cookie('refresh_token', $newRefreshToken, 60 * 24 * 30); // 30 days

            return response()->json([
                'status' => 'success',
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $newToken
                ]
            ])->withCookie($cookie);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error refreshing token'
            ], 401);
        }
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }
}