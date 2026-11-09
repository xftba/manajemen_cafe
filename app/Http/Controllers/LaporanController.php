<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\AktivitasService;
use App\Services\PenjualanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    private const METODE = ['tunai' => 'Tunai', 'qris' => 'QRIS'];
    private const TIPE   = ['takeaway' => 'Takeaway', 'dine_in' => 'Dine In'];

    // ===================== PENJUALAN HARIAN (fitur 55) =====================

    public function penjualanHarian(Request $request, PenjualanService $p)
    {
        $tanggal   = $p->tanggal($request->query('tanggal'), today());
        $ringkasan = $p->ringkasan($tanggal, $tanggal);
        $metode    = $p->perMetode($tanggal, $tanggal);
        $tipe      = $p->perTipe($tanggal, $tanggal);
        $menu      = $p->menuTerlaris($tanggal, $tanggal, null, null);

        if ($this->ekspor($request)) {
            return $this->unduh(
                'penjualan-harian-' . $tanggal->toDateString(),
                [['Laporan Penjualan Harian'], ['Tanggal', $tanggal->translatedFormat('d F Y')]],
                ['Menu', 'Jumlah Terjual', 'Penjualan (sebelum pajak)'],
                $menu->map(fn ($m) => [$m->nama, (int) $m->terjual, $this->rp($m->omset)])->all(),
                [
                    [],
                    ['Jumlah transaksi', $ringkasan['jumlah']],
                    ['Subtotal', $this->rp($ringkasan['subtotal'])],
                    ['Pajak', $this->rp($ringkasan['pajak'])],
                    ['Total penjualan', $this->rp($ringkasan['omset'])],
                    ['Transaksi dibatalkan', $ringkasan['batal']],
                ]
            );
        }

        return view('laporan.penjualan-harian', [
            'tabs'      => $this->tabs($request->user()),
            'tanggal'   => $tanggal,
            'ringkasan' => $ringkasan,
            'metode'    => $metode,
            'tipe'      => $tipe,
            'menu'      => $menu,
        ]);
    }

    // ===================== PENJUALAN BULANAN (fitur 56) =====================

    public function penjualanBulanan(Request $request, PenjualanService $p)
    {
        $awal  = $this->bulan($request->query('bulan'));
        $akhir = $awal->copy()->endOfMonth()->startOfDay();

        // Bulan berjalan hanya dihitung sampai hari ini
        $batas = ($awal->lte(today()) && $akhir->gt(today())) ? today() : $akhir;

        $ringkasan = $p->ringkasan($awal, $batas);
        $harian    = $p->perHari($awal, $batas);
        $metode    = $p->perMetode($awal, $batas);
        $menu      = $p->menuTerlaris($awal, $batas, null, 10);

        if ($this->ekspor($request)) {
            return $this->unduh(
                'penjualan-bulanan-' . $awal->format('Y-m'),
                [['Laporan Penjualan Bulanan'], ['Bulan', $awal->translatedFormat('F Y')]],
                ['Tanggal', 'Jumlah Transaksi', 'Subtotal', 'Pajak', 'Total Penjualan'],
                $harian->map(fn ($h) => [
                    $h['tanggal']->format('d/m/Y'), $h['jumlah'],
                    $this->rp($h['subtotal']), $this->rp($h['pajak']), $this->rp($h['omset']),
                ])->all(),
                [
                    ['TOTAL', $ringkasan['jumlah'], $this->rp($ringkasan['subtotal']), $this->rp($ringkasan['pajak']), $this->rp($ringkasan['omset'])],
                    [],
                    ['Transaksi dibatalkan', $ringkasan['batal']],
                    ['Rata-rata per transaksi', $this->rp($ringkasan['rata'])],
                ]
            );
        }

        return view('laporan.penjualan-bulanan', [
            'tabs'      => $this->tabs($request->user()),
            'awal'      => $awal,
            'ringkasan' => $ringkasan,
            'harian'    => $harian,
            'metode'    => $metode,
            'menu'      => $menu,
        ]);
    }

    // ===================== TRANSAKSI (fitur 57) =====================

    public function transaksi(Request $request, PenjualanService $p)
    {
        [$dari, $sampai] = $p->rentang(
            $request->query('dari'),
            $request->query('sampai'),
            today()->startOfMonth(),
            today()
        );

        $dasar = Transaksi::query()
            ->whereBetween('tanggal_transaksi', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('kasir'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('metode'), fn ($q, $v) => $q->whereHas('pembayaran', fn ($b) => $b->where('metode', $v)));

        $ringkasan = [
            'jumlah' => (clone $dasar)->count(),
            'omset'  => (clone $dasar)->where('status', 'selesai')->sum('total'),
            'batal'  => (clone $dasar)->where('status', 'batal')->count(),
        ];

        if ($this->ekspor($request)) {
            $semua = (clone $dasar)->with(['user', 'pembayaran'])->orderBy('tanggal_transaksi')->orderBy('id')->get();

            return $this->unduh(
                'transaksi-' . $dari->toDateString() . '-sd-' . $sampai->toDateString(),
                [['Laporan Transaksi'], ['Periode', $dari->translatedFormat('d F Y') . ' - ' . $sampai->translatedFormat('d F Y')]],
                ['No. Invoice', 'Tanggal', 'Kasir', 'Customer', 'Tipe', 'Metode', 'Subtotal', 'Pajak', 'Total', 'Status'],
                $semua->map(fn ($t) => [
                    $t->no_invoice,
                    $t->tanggal_transaksi->format('d/m/Y H:i'),
                    $t->user->nama,
                    $t->nama_customer ?: '-',
                    self::TIPE[$t->tipe_pesanan] ?? $t->tipe_pesanan,
                    self::METODE[$t->pembayaran->first()?->metode] ?? '-',
                    $this->rp($t->subtotal), $this->rp($t->pajak), $this->rp($t->total),
                    $t->status === 'batal' ? 'Batal' : 'Selesai',
                ])->all(),
                [
                    [],
                    ['Jumlah transaksi', $ringkasan['jumlah']],
                    ['Total penjualan (selesai)', $this->rp($ringkasan['omset'])],
                    ['Dibatalkan', $ringkasan['batal']],
                ]
            );
        }

        // "semua=1" menampilkan seluruh baris (maksimal 2000) supaya bisa dicetak utuh
        $query = (clone $dasar)->with(['user', 'pembayaran'])->latest('tanggal_transaksi')->latest('id');

        $daftar = $request->boolean('semua')
            ? $query->limit(2000)->get()
            : $query->paginate(25)->withQueryString();

        return view('laporan.transaksi', [
            'tabs'        => $this->tabs($request->user()),
            'dari'        => $dari,
            'sampai'      => $sampai,
            'ringkasan'   => $ringkasan,
            'daftar'      => $daftar,
            'semua'       => $request->boolean('semua'),
            'daftarKasir' => User::where('role', 'kasir')->orderBy('nama')->get(),
        ]);
    }

    // ===================== PENGGUNAAN BAHAN BAKU (fitur 58) =====================

    public function penggunaanBahan(Request $request, PenjualanService $p)
    {
        [$dari, $sampai] = $p->rentang(
            $request->query('dari'),
            $request->query('sampai'),
            today()->startOfMonth(),
            today()
        );

        $gerak = DB::table('pergerakan_stok')
            ->whereBetween('created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->selectRaw("bahan_baku_id,
                SUM(CASE WHEN tipe_pergerakan = 'masuk' THEN jumlah ELSE 0 END) AS masuk,
                SUM(CASE WHEN tipe_pergerakan = 'keluar' THEN jumlah ELSE 0 END) AS keluar,
                SUM(CASE WHEN tipe_pergerakan = 'pembatalan' THEN jumlah ELSE 0 END) AS pembatalan,
                SUM(CASE WHEN tipe_pergerakan = 'rusak' THEN jumlah ELSE 0 END) AS rusak,
                SUM(CASE WHEN tipe_pergerakan = 'kadaluarsa' THEN jumlah ELSE 0 END) AS kadaluarsa")
            ->groupBy('bahan_baku_id')
            ->get()
            ->keyBy('bahan_baku_id');

        $baris = BahanBaku::orderBy('nama')->get()->map(function ($b) use ($gerak) {
            $g = $gerak->get($b->id);

            $terpakai   = (float) ($g->keluar ?? 0) - (float) ($g->pembatalan ?? 0);   // penjualan bersih
            $rusak      = (float) ($g->rusak ?? 0);
            $kadaluarsa = (float) ($g->kadaluarsa ?? 0);

            return [
                'bahan'      => $b,
                'masuk'      => (float) ($g->masuk ?? 0),
                'terpakai'   => $terpakai,
                'rusak'      => $rusak,
                'kadaluarsa' => $kadaluarsa,
                'total'      => $terpakai + $rusak + $kadaluarsa,
            ];
        })->sortByDesc('total')->values();

        if ($this->ekspor($request)) {
            return $this->unduh(
                'penggunaan-bahan-' . $dari->toDateString() . '-sd-' . $sampai->toDateString(),
                [['Laporan Penggunaan Bahan Baku'], ['Periode', $dari->translatedFormat('d F Y') . ' - ' . $sampai->translatedFormat('d F Y')]],
                ['Bahan', 'Satuan', 'Masuk', 'Terpakai (Penjualan)', 'Rusak', 'Kadaluarsa', 'Total Berkurang', 'Stok Sekarang'],
                $baris->map(fn ($r) => [
                    $r['bahan']->nama, $r['bahan']->satuan,
                    $this->qty($r['masuk']), $this->qty($r['terpakai']), $this->qty($r['rusak']),
                    $this->qty($r['kadaluarsa']), $this->qty($r['total']), $this->qty($r['bahan']->stok),
                ])->all()
            );
        }

        return view('laporan.penggunaan-bahan', [
            'tabs'   => $this->tabs($request->user()),
            'dari'   => $dari,
            'sampai' => $sampai,
            'baris'  => $baris,
        ]);
    }

    // ===================== PERSEDIAAN (fitur 59) =====================

    public function persediaan(Request $request)
    {
        $status = $request->query('status');
        $cari   = $request->query('q');

        // Harga beli terakhir tiap bahan, dipakai untuk memperkirakan nilai persediaan
        $hargaTerakhir = DB::table('detail_barang_masuk')
            ->whereIn('id', fn ($q) => $q->selectRaw('MAX(id)')->from('detail_barang_masuk')->groupBy('bahan_baku_id'))
            ->pluck('harga_beli', 'bahan_baku_id');

        $bahan = BahanBaku::query()
            ->when($cari, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->when($status === 'menipis', fn ($q) => $q->whereColumn('stok', '<=', 'stok_minimum'))
            ->when($status === 'habis', fn ($q) => $q->where('stok', '<=', 0))
            ->orderBy('nama')
            ->get()
            ->map(function ($b) use ($hargaTerakhir) {
                $harga = (float) ($hargaTerakhir[$b->id] ?? 0);

                return [
                    'bahan' => $b,
                    'harga' => $harga,
                    'nilai' => $harga * (float) $b->stok,
                ];
            });

        $ringkasan = [
            'total'   => BahanBaku::count(),
            'menipis' => BahanBaku::whereColumn('stok', '<=', 'stok_minimum')->count(),
            'habis'   => BahanBaku::where('stok', '<=', 0)->count(),
        ];

        if ($this->ekspor($request)) {
            $label = fn (BahanBaku $b) => (float) $b->stok <= 0 ? 'Habis' : ($b->menipis() ? 'Menipis' : 'Aman');

            return $this->unduh(
                'persediaan-' . today()->toDateString(),
                [['Laporan Persediaan Bahan Baku'], ['Per tanggal', now()->translatedFormat('d F Y H:i')]],
                ['Bahan', 'Satuan', 'Stok', 'Stok Minimum', 'Status', 'Harga Beli Terakhir', 'Perkiraan Nilai'],
                $bahan->map(fn ($r) => [
                    $r['bahan']->nama, $r['bahan']->satuan, $this->qty($r['bahan']->stok),
                    $this->qty($r['bahan']->stok_minimum), $label($r['bahan']),
                    $this->rp($r['harga']), $this->rp($r['nilai']),
                ])->all(),
                [['TOTAL PERKIRAAN NILAI', '', '', '', '', '', $this->rp($bahan->sum('nilai'))]]
            );
        }

        return view('laporan.persediaan', [
            'tabs'      => $this->tabs($request->user()),
            'bahan'     => $bahan,
            'ringkasan' => $ringkasan,
            'status'    => $status,
            'cari'      => $cari,
        ]);
    }

    // ===================== BANTUAN =====================

    // Tab laporan: admin melihat lima laporan, staff gudang dua laporan bahan baku
    private function tabs(User $user): array
    {
        if ($user->role === 'admin') {
            return [
                ['label' => 'Penjualan Harian',  'route' => 'laporan.penjualan-harian'],
                ['label' => 'Penjualan Bulanan', 'route' => 'laporan.penjualan-bulanan'],
                ['label' => 'Transaksi',         'route' => 'laporan.transaksi'],
                ['label' => 'Penggunaan Bahan',  'route' => 'laporan.penggunaan-bahan'],
                ['label' => 'Persediaan',        'route' => 'laporan.persediaan'],
            ];
        }

        return [
            ['label' => 'Persediaan',       'route' => 'laporan.bahan-baku'],
            ['label' => 'Penggunaan Bahan', 'route' => 'laporan.bahan-baku.penggunaan'],
        ];
    }

    private function bulan(?string $nilai): Carbon
    {
        if ($nilai && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $nilai)) {
            return Carbon::createFromFormat('Y-m-d', $nilai . '-01')->startOfDay();
        }

        return today()->startOfMonth();
    }

    private function ekspor(Request $request): bool
    {
        return $request->query('export') === 'csv';
    }

    private function rp($n): int
    {
        return (int) round((float) $n);
    }

    // Angka desimal berkoma agar terbaca benar di Excel berbahasa Indonesia
    private function qty($n): string
    {
        return str_replace('.', ',', (string) round((float) $n, 2));
    }

    // Unduh CSV. Pemisah titik koma dan BOM UTF-8 supaya Excel membukanya dengan rapi.
    private function unduh(string $nama, array $atas, array $header, array $baris, array $bawah = [])
    {
        AktivitasService::catat('ekspor', 'laporan', "Mengekspor laporan {$nama}");

        return response()->streamDownload(function () use ($atas, $header, $baris, $bawah) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $tulis = fn (array $r) => fputcsv($out, $r, ';', '"', '\\');

            foreach ($atas as $r) {
                $tulis($r);
            }
            $tulis([]);
            $tulis($header);
            foreach ($baris as $r) {
                $tulis($r);
            }
            foreach ($bawah as $r) {
                $tulis($r);
            }

            fclose($out);
        }, $nama . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}