<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WebAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        if (!$request->user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang tidak aktif.',
            ]);
        }

        if ($request->user()->hasRole('admin', 'master_admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($request->user()->hasRole('collector')) {
            return redirect()->intended(route('dashboard'));
        }

        if ($request->user()->hasRole('customer')) {
            if (!$request->user()->customer) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'email' => 'Akun anggota belum terhubung ke data anggota. Hubungi pengurus koperasi.',
                ]);
            }

            return redirect()->intended(route('member.dashboard'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages([
            'email' => 'Role akun belum memiliki halaman web.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}