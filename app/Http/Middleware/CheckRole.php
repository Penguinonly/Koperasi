<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = array_map('strtolower', $roles);
        $role = strtolower((string) $user?->role?->name);

        if (!$user || !$user->is_active || !in_array($role, $allowedRoles, true)) {
            return response()->json(['message' => 'This action is unauthorized for your role'], 403);
        }

        return $next($request);
    }
}
