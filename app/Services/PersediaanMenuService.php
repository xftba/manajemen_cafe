<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\Menu;
use Illuminate\Support\Collection;

class PersediaanMenuService
{
    public const BATAS_MENIPIS = 5;   // porsi: sama atau di bawah ini dianggap menipis

    // Status semua menu, diurutkan menurut nama
    public function semua(): Collection
    {
        return Menu::with(['kategori', 'resep.bahanBaku'])
            ->orderBy('nama')
            ->get()
            ->map(fn (Menu $m) => $this->ringkas($m))
            ->values();
    }

    // Untuk lonceng notifikasi: menu yang tidak bisa dijual dan menu yang hampir habis
    public function notifikasi(): array
    {
        $semua = $this->semua();
        $urut  = ['stok' => 0, 'resep' => 1];

        return [
            'habis'   => $semua->where('status', 'habis')
                ->sortBy(fn ($m) => $urut[$m['sebab']] ?? 9)
                ->values(),
            'menipis' => $semua->where('status', 'menipis')
                ->sortBy('porsi')
                ->values(),
        ];
    }

    /**
     * Ringkasan satu menu. Relasi kategori dan resep.bahanBaku harus sudah dimuat.
     *
     * Status sepenuhnya otomatis dari resep dan stok bahan.
     * sebab: stok (bahan kosong atau kurang), resep (belum ada resep), menipis, aman.
     */
    public function ringkas(Menu $m): array
    {
        $ukuran  = $m->pakai_varian ? array_keys(config('cafe.ukuran')) : ['small'];
        $terbaik = null;   // ukuran dengan porsi terbanyak

        foreach ($ukuran as $u) {
            $baris = $m->resep->where('ukuran', $u);

            if ($baris->isEmpty()) {
                continue;
            }

            // Bahan penentu: yang paling cepat habis untuk ukuran ini
            $penentu = $baris
                ->sortBy(fn ($r) => floor((float) $r->bahanBaku->stok / (float) $r->takaran))
                ->first();

            $porsi = (int) floor((float) $penentu->bahanBaku->stok / (float) $penentu->takaran);

            if ($terbaik === null || $porsi > $terbaik['porsi']) {
                $terbaik = ['porsi' => $porsi, 'bahan' => $penentu->bahanBaku];
            }
        }

        $porsi = $terbaik['porsi'] ?? 0;
        $bahan = $terbaik['bahan'] ?? null;
        $sisa  = $bahan ? BahanBaku::angka($bahan->stok) . ' ' . $bahan->satuan : '';

        if (! $bahan) {
            [$status, $sebab, $catatan, $tipe] = ['habis', 'resep', 'Belum ada resep', 'peringatan'];
        } elseif ($porsi === 0) {
            $catatan = (float) $bahan->stok <= 0
                ? "{$bahan->nama} kosong (0 {$bahan->satuan})"
                : "{$bahan->nama} tidak cukup (sisa {$sisa})";
            [$status, $sebab, $tipe] = ['habis', 'stok', 'bahaya'];
        } elseif ($porsi <= self::BATAS_MENIPIS || $bahan->menipis()) {
            [$status, $sebab, $catatan, $tipe] = ['menipis', 'menipis', "{$bahan->nama} sisa {$sisa}", 'info'];
        } else {
            $status  = 'tersedia';
            $sebab   = 'aman';
            $tipe    = 'netral';
            $catatan = $m->resep->map(fn ($r) => $r->bahanBaku->nama)->unique()->values()->implode(', ');
        }

        $harga = [(float) $m->harga];
        if ($m->pakai_varian) {
            $harga[] = $m->hargaUntuk('medium');
            $harga[] = $m->hargaUntuk('large');
        }

        return [
            'id'           => $m->id,
            'nama'         => $m->nama,
            'kategori_id'  => $m->kategori_id,
            'kategori'     => $m->kategori->nama,
            'gambar_url'   => $m->gambar ? asset('storage/' . $m->gambar) : null,
            'tersedia'     => $status !== 'habis',   // dipakai indikator di halaman Persediaan
            'harga_min'    => min($harga),
            'harga_maks'   => max($harga),
            'porsi'        => $porsi,
            'status'       => $status,
            'sebab'        => $sebab,
            'catatan'      => $catatan,
            'tipe_catatan' => $tipe,
            'proses'       => false,
        ];
    }
}