<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\PergerakanStok;
use App\Models\Resep;
use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BahanBakuController extends Controller
{
    public function index(Request $request)
    {
        $bahan = BahanBaku::query()
            ->when($request->q, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->when($request->boolean('menipis'), fn ($q) => $q->whereColumn('stok', '<=', 'stok_minimum'))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('bahan-baku.index', compact('bahan'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'         => ['required', 'string', 'max:100', 'unique:bahan_baku,nama'],
            'satuan'       => ['required', 'string', 'max:20'],
            'stok'         => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stok_minimum' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $bahan = DB::transaction(function () use ($data) {
            $bahan = BahanBaku::create($data);

            // Stok awal dicatat sebagai pergerakan, supaya riwayat stok lengkap
            if ((float) $bahan->stok > 0) {
                PergerakanStok::create([
                    'bahan_baku_id'   => $bahan->id,
                    'user_id'         => auth()->id(),
                    'tipe_pergerakan' => 'stok_awal',
                    'jumlah'          => $bahan->stok,
                    'stok_sebelum'    => 0,
                    'stok_sesudah'    => $bahan->stok,
                    'catatan'         => 'Stok awal saat bahan baku didaftarkan',
                ]);
            }

            return $bahan;
        });

        AktivitasService::catat('tambah', 'bahan baku', "Menambah bahan baku {$bahan->nama}");

        return redirect()->route('bahan-baku.index')->with('sukses', 'Bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, BahanBaku $bahanBaku)
    {
        // Stok sengaja tidak bisa diubah di sini
        $data = $request->validate([
            'nama'         => ['required', 'string', 'max:100', 'unique:bahan_baku,nama,' . $bahanBaku->id],
            'satuan'       => ['required', 'string', 'max:20'],
            'stok_minimum' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $bahanBaku->update($data);
        AktivitasService::catat('ubah', 'bahan baku', "Mengubah bahan baku {$bahanBaku->nama}");

        return redirect()->route('bahan-baku.index')->with('sukses', 'Bahan baku berhasil diubah.');
    }

    public function destroy(BahanBaku $bahanBaku)
    {
        $dipakai = Resep::where('bahan_baku_id', $bahanBaku->id)->exists()
            || DB::table('detail_barang_masuk')->where('bahan_baku_id', $bahanBaku->id)->exists()
            || DB::table('penyesuaian_stok')->where('bahan_baku_id', $bahanBaku->id)->exists()
            || PergerakanStok::where('bahan_baku_id', $bahanBaku->id)
                ->where('tipe_pergerakan', '!=', 'stok_awal')->exists();

        if ($dipakai) {
            return back()->with('error', 'Bahan baku tidak bisa dihapus karena sudah dipakai di resep atau riwayat stok.');
        }

        $nama = $bahanBaku->nama;

        DB::transaction(function () use ($bahanBaku) {
            PergerakanStok::where('bahan_baku_id', $bahanBaku->id)->delete();   // hanya catatan stok awal
            $bahanBaku->delete();
        });

        AktivitasService::catat('hapus', 'bahan baku', "Menghapus bahan baku {$nama}");

        return back()->with('sukses', 'Bahan baku berhasil dihapus.');
    }
}