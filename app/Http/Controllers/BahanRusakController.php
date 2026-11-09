<?php

namespace App\Http\Controllers;

use App\Exceptions\TransaksiGagal;
use App\Models\BahanBaku;
use App\Models\PergerakanStok;
use App\Services\AktivitasService;
use App\Services\NotifikasiService;
use App\Services\StokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BahanRusakController extends Controller
{
    public function index(Request $request)
    {
        $riwayat = PergerakanStok::with(['bahanBaku', 'user'])
            ->whereIn('tipe_pergerakan', ['rusak', 'kadaluarsa'])
            ->when($request->jenis, fn ($q, $v) => $q->where('tipe_pergerakan', $v))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('created_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $bahan = BahanBaku::orderBy('nama')->get(['id', 'nama', 'satuan', 'stok'])
            ->map(fn ($b) => ['id' => $b->id, 'nama' => $b->nama, 'satuan' => $b->satuan, 'stok' => (float) $b->stok]);

        return view('bahan-rusak.index', compact('riwayat', 'bahan'));
    }

    public function store(Request $request, StokService $stok)
    {
        $data = $request->validate([
            'bahan_baku_id' => ['required', 'exists:bahan_baku,id'],
            'jenis'         => ['required', Rule::in(['rusak', 'kadaluarsa'])],
            'jumlah'        => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'keterangan'    => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'bahan_baku_id.required' => 'Pilih bahan baku.',
            'jumlah.gt'              => 'Jumlah harus lebih dari 0.',
            'keterangan.required'    => 'Keterangan wajib diisi.',
            'keterangan.min'         => 'Keterangan minimal 3 karakter.',
        ]);

        $bahan = BahanBaku::findOrFail($data['bahan_baku_id']);

        try {
            DB::transaction(function () use ($stok, $data) {
                $stok->ubahManual(
                    (int) $data['bahan_baku_id'],
                    -((float) $data['jumlah']),
                    $data['jenis'],
                    null,
                    $data['keterangan']
                );
            });
        } catch (TransaksiGagal $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AktivitasService::catat(
            'tambah',
            'bahan ' . $data['jenis'],
            "Mencatat {$bahan->nama} {$data['jenis']} sebanyak " . BahanBaku::angka($data['jumlah']) . " {$bahan->satuan}"
        );

        // Admin diberi tahu ada kerugian bahan
        NotifikasiService::kirimKeRole(
            'admin',
            'rusak',
            "{$bahan->nama} dicatat {$data['jenis']}",
            BahanBaku::angka($data['jumlah']) . " {$bahan->satuan} oleh " . auth()->user()->nama . ": {$data['keterangan']}",
            route('pergerakan-stok.index', ['tipe' => $data['jenis']])
        );

        return back()->with('sukses', 'Pencatatan berhasil. Stok bahan sudah dikurangi.');
    }
}