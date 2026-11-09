<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Akun dinonaktifkan saat sedang login: keluarkan (fitur 6)
        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Permintaan dari Pos Kasir (fetch) menerima JSON, bukan pengalihan halaman
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun Anda sudah dinonaktifkan.'], 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda sudah dinonaktifkan.']);
        }

        return $next($request);
    }
}