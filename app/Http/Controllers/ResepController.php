<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Menu;
use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResepController extends Controller
{
    public function index(Request $request)
    {
        $menu = Menu::with(['kategori', 'resep'])
            ->when($request->q, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->when($request->status === 'belum', fn ($q) => $q->doesntHave('resep'))
            ->when($request->status === 'sudah', fn ($q) => $q->has('resep'))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $bahan = BahanBaku::orderBy('nama')->get(['id', 'nama', 'satuan']);

        return view('resep.index', compact('menu', 'bahan'));
    }

    // Edit sekarang ada di panel halaman index
    public function edit(Menu $menu)
    {
        return redirect()->route('resep.index');
    }

    public function update(Request $request, Menu $menu)
    {
        $daftar = $this->daftarUkuran($menu);

        $aturan = [
            'resep'       => ['required', 'array'],
            'resep.small' => ['required', 'array', 'min:1'],
        ];

        $pesan = [
            'resep.required'       => 'Resep ukuran Small minimal punya satu bahan.',
            'resep.small.required' => 'Resep ukuran Small minimal punya satu bahan.',
            'resep.small.min'      => 'Resep ukuran Small minimal punya satu bahan.',
        ];

        foreach ($daftar as $u) {
            $label = $menu->pakai_varian ? config("cafe.ukuran.{$u}") : 'Resep';

            $aturan["resep.{$u}.*.bahan_baku_id"] = ['required', 'exists:bahan_baku,id'];
            $aturan["resep.{$u}.*.temperatur"]    = ['nullable', 'in:ice,hot'];
            $aturan["resep.{$u}.*.takaran"]       = ['required', 'numeric', 'gt:0', 'max:9999999'];

            $pesan["resep.{$u}.*.bahan_baku_id.required"] = "{$label}: pilih bahan untuk setiap baris.";
            $pesan["resep.{$u}.*.takaran.required"]       = "{$label}: isi takaran untuk setiap bahan.";
            $pesan["resep.{$u}.*.takaran.gt"]             = "{$label}: takaran harus lebih dari 0.";
        }

        $data = $request->validate($aturan, $pesan);

        // Bahan yang sama hanya boleh dobel kalau temperaturnya tidak tumpang tindih
        foreach ($daftar as $u) {
            $label = $menu->pakai_varian ? config("cafe.ukuran.{$u}") : 'Resep';

            foreach (collect($data['resep'][$u] ?? [])->groupBy('bahan_baku_id') as $bid => $grup) {
                $suhu = $grup->map(fn ($r) => ($r['temperatur'] ?? null) ?: null);

                if ($suhu->count() > 1 && ($suhu->contains(null) || $suhu->count() !== $suhu->unique()->count())) {
                    $nama = BahanBaku::find($bid)?->nama;

                    throw ValidationException::withMessages([
                        "resep.{$u}" => "{$label}: bahan {$nama} dipakai dua kali dengan temperatur yang tumpang tindih.",
                    ]);
                }
            }
        }

        $jumlah = 0;

        DB::transaction(function () use ($menu, $data, $daftar, &$jumlah) {
            $menu->resep()->delete();

            foreach ($daftar as $u) {
                foreach ($data['resep'][$u] ?? [] as $row) {
                    $menu->resep()->create([
                        'ukuran'        => $u,
                        'bahan_baku_id' => $row['bahan_baku_id'],
                        'temperatur'    => $menu->pakai_varian ? (($row['temperatur'] ?? null) ?: null) : null,
                        'takaran'       => $row['takaran'],
                    ]);
                    $jumlah++;
                }
            }
        });

        AktivitasService::catat('ubah', 'resep', "Menyimpan resep menu {$menu->nama} ({$jumlah} baris bahan)");

        return redirect()->route('resep.index')->with('sukses', "Resep {$menu->nama} berhasil disimpan.");
    }

    public function destroy(Menu $menu)
    {
        $menu->resep()->delete();
        AktivitasService::catat('hapus', 'resep', "Menghapus semua resep menu {$menu->nama}");

        return back()->with('sukses', "Resep {$menu->nama} berhasil dihapus.");
    }

    // Menu dengan varian punya resep untuk tiap ukuran, menu tanpa varian hanya satu
    private function daftarUkuran(Menu $menu): array
    {
        return $menu->pakai_varian ? array_keys(config('cafe.ukuran')) : ['small'];
    }
}