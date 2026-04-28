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

        // Handle both: role as string column OR role as relationship
        $userRole = is_object($user->role) ? $user->role->name : $user->role;

        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Forbidden: insufficient permissions'], 403);
        }

        return $next($request);
    }
}