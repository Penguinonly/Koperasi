<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->with('status', 'Silakan masuk sebagai admin terlebih dahulu.');
        }

        if (!$user->is_active || !$user->hasRole('admin', 'master_admin')) {
            abort(403, 'Akses admin diperlukan.');
        }

        return $next($request);
    }
}