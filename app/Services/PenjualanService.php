<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PenjualanService
{
    // Query dasar: transaksi dalam rentang tanggal. Default hanya yang berstatus selesai.
    private function dasar(Carbon $dari, Carbon $sampai, ?int $kasirId = null, bool $semuaStatus = false)
    {
        return DB::table('transaksi')
            ->whereBetween('transaksi.tanggal_transaksi', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->when(! $semuaStatus, fn ($q) => $q->where('transaksi.status', 'selesai'))
            ->when($kasirId, fn ($q) => $q->where('transaksi.user_id', $kasirId));
    }

    public function ringkasan(Carbon $dari, Carbon $sampai, ?int $kasirId = null): array
    {
        $selesai = $this->dasar($dari, $sampai, $kasirId)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(total), 0) as omset, COALESCE(SUM(subtotal), 0) as subtotal, COALESCE(SUM(pajak), 0) as pajak')
            ->first();

        $batal = $this->dasar($dari, $sampai, $kasirId, true)
            ->where('transaksi.status', 'batal')
            ->count();

        $jumlah = (int) $selesai->jumlah;

        return [
            'jumlah'   => $jumlah,
            'omset'    => (float) $selesai->omset,
            'subtotal' => (float) $selesai->subtotal,
            'pajak'    => (float) $selesai->pajak,
            'batal'    => $batal,
            'rata'     => $jumlah ? (float) $selesai->omset / $jumlah : 0.0,
        ];
    }

    // Penjualan per hari. Hari tanpa transaksi tetap muncul dengan nilai 0.
    public function perHari(Carbon $dari, Carbon $sampai, ?int $kasirId = null): Collection
    {
        $data = $this->dasar($dari, $sampai, $kasirId)
            ->selectRaw('DATE(tanggal_transaksi) as tanggal, COUNT(*) as jumlah, SUM(total) as omset, SUM(subtotal) as subtotal, SUM(pajak) as pajak')
            ->groupBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $hasil  = collect();
        $batas  = $sampai->copy()->startOfDay();

        for ($d = $dari->copy()->startOfDay(); $d->lte($batas); $d->addDay()) {
            $baris = $data->get($d->toDateString());

            $hasil->push([
                'tanggal'  => $d->copy(),
                'jumlah'   => (int) ($baris->jumlah ?? 0),
                'omset'    => (float) ($baris->omset ?? 0),
                'subtotal' => (float) ($baris->subtotal ?? 0),
                'pajak'    => (float) ($baris->pajak ?? 0),
            ]);
        }

        return $hasil;
    }

    public function perMetode(Carbon $dari, Carbon $sampai, ?int $kasirId = null): Collection
    {
        return $this->dasar($dari, $sampai, $kasirId)
            ->join('pembayaran', 'pembayaran.transaksi_id', '=', 'transaksi.id')
            ->selectRaw('pembayaran.metode, COUNT(*) as jumlah, SUM(transaksi.total) as omset')
            ->groupBy('pembayaran.metode')
            ->orderByDesc('omset')
            ->get();
    }

    public function perTipe(Carbon $dari, Carbon $sampai, ?int $kasirId = null): Collection
    {
        return $this->dasar($dari, $sampai, $kasirId)
            ->selectRaw('tipe_pesanan, COUNT(*) as jumlah, SUM(total) as omset')
            ->groupBy('tipe_pesanan')
            ->orderByDesc('omset')
            ->get();
    }

    // Menu terlaris. $batas = null untuk menampilkan semua.
    public function menuTerlaris(Carbon $dari, Carbon $sampai, ?int $kasirId = null, ?int $batas = 5): Collection
    {
        return $this->dasar($dari, $sampai, $kasirId)
            ->join('detail_transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('menu', 'menu.id', '=', 'detail_transaksi.menu_id')
            ->selectRaw('menu.nama, SUM(detail_transaksi.jumlah) as terjual, SUM(detail_transaksi.subtotal) as omset')
            ->groupBy('menu.id', 'menu.nama')
            ->orderByDesc('terjual')
            ->orderBy('menu.nama')
            ->when($batas, fn ($q) => $q->limit($batas))
            ->get();
    }

    // Penjualan per kategori, dihitung dari subtotal item (sebelum pajak)
    public function perKategori(Carbon $dari, Carbon $sampai, ?int $kasirId = null): Collection
    {
        return $this->dasar($dari, $sampai, $kasirId)
            ->join('detail_transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
            ->join('menu', 'menu.id', '=', 'detail_transaksi.menu_id')
            ->join('kategori', 'kategori.id', '=', 'menu.kategori_id')
            ->selectRaw('kategori.nama, SUM(detail_transaksi.jumlah) as terjual, SUM(detail_transaksi.subtotal) as omset')
            ->groupBy('kategori.id', 'kategori.nama')
            ->orderByDesc('omset')
            ->get();
    }

    // ----- bantuan membaca filter tanggal dari URL -----

    public function tanggal(?string $nilai, Carbon $default): Carbon
    {
        if (! $nilai) {
            return $default->copy()->startOfDay();
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay();
        } catch (\Throwable $e) {
            return $default->copy()->startOfDay();
        }
    }

    // Rentang tanggal yang aman: urutan dibetulkan dan dibatasi maksimal 1 tahun
    public function rentang(?string $dari, ?string $sampai, Carbon $defDari, Carbon $defSampai, int $maksHari = 366): array
    {
        $a = $this->tanggal($dari, $defDari);
        $b = $this->tanggal($sampai, $defSampai);

        if ($a->gt($b)) {
            [$a, $b] = [$b, $a];
        }

        if ($a->diffInDays($b) > $maksHari) {
            $a = $b->copy()->subDays($maksHari);
        }

        return [$a, $b];
    }
}