<?php

namespace App\Http\Controllers;

use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // is_active = true: akun nonaktif tidak bisa login (fitur 6)
        if (Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();

            AktivitasService::catat('login', 'autentikasi', 'Masuk ke sistem');

            // Halaman awal sesuai role
            return redirect()->route(
                $request->user()->role === 'kasir' ? 'kasir.pos' : 'dashboard'
            );
        }

        // Pengguna belum dikenal, jadi user_id kosong
        AktivitasService::catat(
            'login gagal',
            'autentikasi',
            'Percobaan login gagal untuk email ' . Str::limit($credentials['email'], 100)
        );

        return back()
            ->withErrors(['email' => 'Email atau password salah, atau akun tidak aktif.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AktivitasService::catat('logout', 'autentikasi', 'Keluar dari sistem');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}