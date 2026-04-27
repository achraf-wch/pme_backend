<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user's role name is in the allowed roles list
        if (!in_array($user->role->name, $roles)) {
            return response()->json(['message' => 'Forbidden: insufficient permissions'], 403);
        }

        return $next($request);
    }
}