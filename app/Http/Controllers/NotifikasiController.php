<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $uid = $request->user()->id;

        $daftar = Notifikasi::where('user_id', $uid)
            ->where('created_at', '>=', now()->subDays(14))
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id'     => $n->id,
                'jenis'  => $n->jenis,
                'judul'  => $n->judul,
                'isi'    => $n->isi,
                'url'    => $n->url,
                'dibaca' => $n->dibaca_at !== null,
                'waktu'  => $n->created_at->format('d/m H:i'),
            ])->values();

        return response()->json([
            'belum'  => Notifikasi::where('user_id', $uid)->whereNull('dibaca_at')->count(),
            'daftar' => $daftar,
        ]);
    }

    public function baca(Request $request, Notifikasi $notifikasi)
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);

        $notifikasi->update(['dibaca_at' => $notifikasi->dibaca_at ?? now()]);

        return response()->json(['ok' => true]);
    }

    public function bacaSemua(Request $request)
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);

        return response()->json(['ok' => true]);
    }
}