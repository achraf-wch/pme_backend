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

        $user->loadMissing('role');
        $userRole = optional($user->role)->name;

        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Forbidden: insufficient permissions'], 403);
        }

        return $next($request);
    }
}
