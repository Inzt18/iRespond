<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Register a new citizen (Flutter).
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'mobile_number' => $request->mobile_number,
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $token = $user->createToken('Flutter App')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    /**
     * Login citizen or LGU responder by mobile number.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('mobile_number', $request->mobile_number)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number is not registered.',
            ], 404);
        }

        if ($user->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been disabled.',
            ], 403);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts must use the web portal.',
            ], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('Flutter App')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 200);
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'mobile_number' => $user->mobile_number,
            'role' => $user->role ?? 'citizen',
            'department_type' => $user->department_type,
            'department_name' => $user->department_name,
            'is_active' => $user->is_active,
        ];
    }
}
