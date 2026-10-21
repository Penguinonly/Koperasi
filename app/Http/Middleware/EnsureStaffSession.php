<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login')->with('status', 'Silakan masuk terlebih dahulu.');
        }

        if (!$user->is_active || !$user->hasRole('admin', 'master_admin', 'collector')) {
            abort(403, 'Akses karyawan atau admin diperlukan.');
        }

        return $next($request);
    }
}