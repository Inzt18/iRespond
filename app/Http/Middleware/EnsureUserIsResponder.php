<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsResponder
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'responder' || $user->is_active === false) {
            return response()->json([
                'success' => false,
                'message' => 'Responder access only.',
            ], 403);
        }

        if (! in_array($user->department_type, ['fire', 'medical', 'natural_disaster'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Responder department is not configured.',
            ], 403);
        }

        return $next($request);
    }
}
