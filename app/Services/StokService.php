<?php

namespace App\Services;

use App\Exceptions\TransaksiGagal;
use App\Models\BahanBaku;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\Resep;
use App\Models\Transaksi;
use Illuminate\Support\Collection;

class StokService
{
    /**
     * Hitung kebutuhan bahan dari pesanan. Setiap ukuran memakai resepnya sendiri.
     *
     * @param  array<int,array{menu_id:int,jumlah:int,ukuran:?string}>  $baris
     * @return array<int,float>   [bahan_baku_id => total pemakaian]
     */
    public function kebutuhan(array $baris): array
    {
        $ids  = collect($baris)->pluck('menu_id')->unique()->all();
        $menu = Menu::whereIn('id', $ids)->get()->keyBy('id');

        $resep = Resep::whereIn('menu_id', $ids)->get()->groupBy(['menu_id', 'ukuran']);

        $kebutuhan = [];

        foreach ($baris as $b) {
            $m       = $menu[$b['menu_id']];                       // 1. $m didefinisikan dulu
            $ukuran  = $m->pakai_varian ? ($b['ukuran'] ?? 'small') : 'small';
            $suhu    = $m->pakai_varian ? ($b['temperatur'] ?? 'ice') : null;
            $gula    = $m->pakai_varian ? ($b['gula'] ?? 'normal') : 'normal';
            $pengali = (float) config("cafe.pengali_gula.{$gula}", 1);

            $daftar = ($resep->get($m->id)?->get($ukuran) ?? collect())
                ->filter(fn ($r) => $r->temperatur === null || $r->temperatur === $suhu);

            if ($daftar->isEmpty()) {
                $label = config("cafe.ukuran.{$ukuran}", $ukuran);

                throw new TransaksiGagal(
                    $m->pakai_varian
                        ? "{$m->nama} ukuran {$label} sedang tidak tersedia."
                        : "{$m->nama} sedang tidak tersedia."
                );
            }

            foreach ($daftar as $r) {
                $takaran = (float) $r->takaran * ($r->ikut_gula ? $pengali : 1);
                if ($takaran <= 0) continue;

                $kebutuhan[$r->bahan_baku_id] = ($kebutuhan[$r->bahan_baku_id] ?? 0)
                    + (int) $b['jumlah'] * $takaran;
            }
        }

        return array_map(fn ($v) => round($v, 2), $kebutuhan);
    }

    /**
     * Kunci baris bahan lalu pastikan stoknya cukup (fitur 46).
     * Dipanggil di dalam DB::transaction, jadi dua kasir yang membayar bersamaan
     * diproses bergantian dan stok tidak pernah menjadi negatif (fitur 48).
     */
    public function kunciDanValidasi(array $kebutuhan): Collection
    {
        $bahan = BahanBaku::whereIn('id', array_keys($kebutuhan))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $kurang = [];

        foreach ($kebutuhan as $id => $butuh) {
            $b = $bahan[$id];

            if ((float) $b->stok + 0.000001 < $butuh) {
                $kurang[] = "{$b->nama} (butuh " . BahanBaku::angka($butuh) . " {$b->satuan}, tersedia "
                    . BahanBaku::angka($b->stok) . " {$b->satuan})";
            }
        }

        if ($kurang) {
            throw new TransaksiGagal('Stok bahan tidak cukup: ' . implode('; ', $kurang) . '.');
        }

        return $bahan;
    }

    /**
     * Kurangi stok dan catat pergerakannya (fitur 47 dan 49).
     */
    public function kurangi(Transaksi $transaksi, array $kebutuhan, Collection $bahan): void
    {
        // Cegah pengurangan ganda untuk transaksi yang sama (fitur 48)
        $sudah = PergerakanStok::where('transaksi_id', $transaksi->id)
            ->where('tipe_pergerakan', 'keluar')
            ->exists();

        if ($sudah) {
            return;
        }

        foreach ($kebutuhan as $id => $pakai) {
            $b       = $bahan[$id];
            $sebelum = (float) $b->stok;
            $sesudah = round($sebelum - $pakai, 2);

            if ($sesudah < 0) {
                throw new TransaksiGagal("Stok {$b->nama} tidak boleh negatif.");
            }

            PergerakanStok::create([
                'bahan_baku_id'   => $b->id,
                'user_id'         => auth()->id(),
                'transaksi_id'    => $transaksi->id,
                'tipe_pergerakan' => 'keluar',
                'jumlah'          => $pakai,
                'stok_sebelum'    => $sebelum,
                'stok_sesudah'    => $sesudah,
                'catatan'         => 'Penjualan ' . $transaksi->no_invoice,
            ]);

            $b->update(['stok' => $sesudah]);
        }
    }

    /**
     * Kembalikan stok saat transaksi dibatalkan.
     * Jumlah yang dikembalikan diambil dari catatan pemakaian saat penjualan,
     * jadi tetap akurat walau resep diubah setelahnya.
     */
    public function kembalikan(Transaksi $transaksi): void
    {
        // Cegah pengembalian ganda (fitur 48)
        $sudah = PergerakanStok::where('transaksi_id', $transaksi->id)
            ->where('tipe_pergerakan', 'pembatalan')
            ->exists();

        if ($sudah) {
            throw new TransaksiGagal('Stok transaksi ini sudah pernah dikembalikan.');
        }

        $keluar = PergerakanStok::where('transaksi_id', $transaksi->id)
            ->where('tipe_pergerakan', 'keluar')
            ->get();

        if ($keluar->isEmpty()) {
            return;
        }

        $bahan = BahanBaku::whereIn('id', $keluar->pluck('bahan_baku_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($keluar as $k) {
            $b       = $bahan[$k->bahan_baku_id];
            $sebelum = (float) $b->stok;
            $sesudah = round($sebelum + (float) $k->jumlah, 2);

            PergerakanStok::create([
                'bahan_baku_id'   => $b->id,
                'user_id'         => auth()->id(),
                'transaksi_id'    => $transaksi->id,
                'tipe_pergerakan' => 'pembatalan',
                'jumlah'          => $k->jumlah,
                'stok_sebelum'    => $sebelum,
                'stok_sesudah'    => $sesudah,
                'catatan'         => 'Pembatalan ' . $transaksi->no_invoice,
            ]);

            $b->update(['stok' => $sesudah]);
        }
    }

    /**
     * Ubah stok secara manual (bahan rusak, kadaluarsa, penyesuaian) dan catat pergerakannya.
     * Harus dipanggil di dalam DB::transaction.
     * $delta negatif = stok berkurang, positif = stok bertambah.
     */
    public function ubahManual(int $bahanId, float $delta, string $tipe, ?int $penyesuaianId, ?string $catatan): PergerakanStok
    {
        $bahan   = BahanBaku::lockForUpdate()->findOrFail($bahanId);
        $sebelum = (float) $bahan->stok;
        $sesudah = round($sebelum + $delta, 2);

        // Stok tidak boleh negatif (fitur 48)
        if ($sesudah < 0) {
            throw new TransaksiGagal(
                "Stok {$bahan->nama} tidak mencukupi. Tersedia " . BahanBaku::angka($sebelum) . " {$bahan->satuan}."
            );
        }

        $pergerakan = PergerakanStok::create([
            'bahan_baku_id'       => $bahan->id,
            'user_id'             => auth()->id(),
            'penyesuaian_stok_id' => $penyesuaianId,
            'tipe_pergerakan'     => $tipe,
            'jumlah'              => abs($delta),
            'stok_sebelum'        => $sebelum,
            'stok_sesudah'        => $sesudah,
            'catatan'             => $catatan,
        ]);

        $bahan->update(['stok' => $sesudah]);

        return $pergerakan;
    }
}