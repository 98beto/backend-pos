<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceLoginRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DeviceAuthController extends Controller
{
    public function login(DeviceLoginRequest $request)
    {
        $device = Device::with('branch')
            ->where('identifier', $request->validated('identifier'))
            ->first();

        if (! $device || ! Hash::check($request->validated('secret'), $device->secret_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device credentials.',
            ], 422);
        }

        if (! $device->is_active || ! $device->branch?->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This device is inactive.',
            ], 422);
        }

        $device->forceFill(['last_login_at' => now()])->save();

        $token = $device->createToken($device->identifier);

        return response()->json([
            'success' => true,
            'message' => 'Device authenticated successfully.',
            'data' => [
                'token' => $token->plainTextToken,
                'device' => new DeviceResource($device),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        $device = $request->user()?->load('branch');

        return response()->json([
            'success' => true,
            'data' => new DeviceResource($device),
        ]);
    }
}
