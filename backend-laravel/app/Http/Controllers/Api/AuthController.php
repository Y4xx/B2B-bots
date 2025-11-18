<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:255',
        ]);

        // Create tenant
        $tenant = Tenant::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_name' => $validated['company_name'] ?? null,
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(14),
            'is_active' => true,
        ]);

        // Create user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('tenant'),
            'token' => $token,
            'tenant' => $tenant,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->tenant->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact support.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('tenant'),
            'token' => $token,
            'tenant' => $user->tenant,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('tenant'),
            'tenant' => $request->user()->tenant,
        ]);
    }
}
