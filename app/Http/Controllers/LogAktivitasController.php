<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\Request;

class LogAktivitasController extends Controller
{
    public function index(Request $request)
    {
        $log = LogAktivitas::with('user')
            ->when($request->pengguna, fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->modul, fn ($q, $v) => $q->where('modul', $v))
            ->when($request->aksi, fn ($q, $v) => $q->where('aksi', $v))
            ->when($request->q, fn ($q, $v) => $q->where('deskripsi', 'like', "%{$v}%"))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('log-aktivitas.index', [
            'log'      => $log,
            'pengguna' => User::orderBy('nama')->get(['id', 'nama']),
            'daftarModul' => LogAktivitas::select('modul')->distinct()->orderBy('modul')->pluck('modul'),
            'daftarAksi'  => LogAktivitas::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
        ]);
    }
}