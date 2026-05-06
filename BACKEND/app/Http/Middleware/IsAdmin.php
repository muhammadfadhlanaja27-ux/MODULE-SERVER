<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah token milik Administrator
        if (!$request->user() instanceof \App\Models\Administrator) {
            return response()->json([
                'status' => 'insufficient_permissions',
                'message' => 'Access forbidden',
            ], 403);
        }

        return $next($request);
    }
}