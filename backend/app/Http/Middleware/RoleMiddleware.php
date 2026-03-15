<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (!in_array($user->role, $roles)) {
            return response()->json(['error' => 'Accès interdit'], 403);
        }
        return $next($request);
    }
}
