<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request)
    {
        // Create user
        $user = User::create([
            'mobile_number' => $request->mobile_number,
        ]);

        // Generate Sanctum token
        $token = $user->createToken('Flutter App')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    /**
     * Login an existing user.
     */
    public function login(LoginRequest $request)
    {
        // Find user by mobile number
        $user = User::where('mobile_number', $request->mobile_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number is not registered.',
            ], 404);
        }

        // Delete old tokens (optional: one active session per user)
        $user->tokens()->delete();

        // Generate new Sanctum token
        $token = $user->createToken('Flutter App')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }
}