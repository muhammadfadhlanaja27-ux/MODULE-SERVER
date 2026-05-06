<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsUser
{
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah token milik User biasa
        if (!$request->user() instanceof \App\Models\User) {
            return response()->json([
                'status' => 'insufficient_permissions',
                'message' => 'Access forbidden',
            ], 403);
        }

        return $next($request);
    }
}