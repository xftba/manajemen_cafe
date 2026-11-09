<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\BarangMasuk;
use App\Models\PenyesuaianStok;
use App\Models\PergerakanStok;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\PenjualanService;
use Illuminate\Http\Request;
use App\Models\LogAktivitas;

class DashboardController extends Controller
{
    public function index(Request $request, PenjualanService $penjualan)
    {
        /** @var User $user */
        $user = $request->user();

        // Kasir tidak punya Dashboard, halaman utamanya Pos Kasir
        if ($user->role === 'kasir') {
            return redirect()->route('kasir.pos');
        }

        return $user->role === 'admin' ? $this->admin($penjualan) : $this->gudang();
    }

    private function admin(PenjualanService $p)
    {
        $hariIni   = today();
        $awalBulan = today()->startOfMonth();

        return view('dashboard.admin', [
            'hariIni'    => $p->ringkasan($hariIni, $hariIni),
            'bulanIni'   => $p->ringkasan($awalBulan, $hariIni),
            'harian'     => $p->perHari(today()->subDays(6), $hariIni),
            'kategori'   => $p->perKategori($awalBulan, $hariIni),
            'terlaris'   => $p->menuTerlaris($awalBulan, $hariIni, null, 5),
            'metode'     => $p->perMetode($awalBulan, $hariIni),
            'terbaru'    => Transaksi::with(['user', 'pembayaran'])->latest('tanggal_transaksi')->latest('id')->limit(6)->get(),
            'persediaan' => $this->persediaan(),
            'menipis'    => $this->daftarMenipis(6),
        ]);
    }

private function gudang()
{
    $awalBulan = today()->startOfMonth();

    return view('dashboard.gudang', [
        'persediaan'  => $this->persediaan(),
        'menipis'     => $this->daftarMenipis(8),
        'barangMasuk' => BarangMasuk::where('tanggal', '>=', $awalBulan)->count(),
        'rusak'       => PergerakanStok::whereIn('tipe_pergerakan', ['rusak', 'kadaluarsa'])
                            ->where('created_at', '>=', $awalBulan)->count(),
        'menunggu'    => PenyesuaianStok::where('status', 'pending')->count(),
        'pergerakan'  => PergerakanStok::with(['bahanBaku', 'user'])->latest('created_at')->latest('id')->limit(6)->get(),
        'permintaan'  => LogAktivitas::with('user')
                            ->where('aksi', 'minta restock')
                            ->where('created_at', '>=', now()->subDays(3))
                            ->latest('created_at')
                            ->limit(3)
                            ->get(),
    ]);
}

    // Statistik persediaan (fitur 10). "Menipis" sudah termasuk yang habis.
    private function persediaan(): array
    {
        return [
            'total'   => BahanBaku::count(),
            'menipis' => BahanBaku::whereColumn('stok', '<=', 'stok_minimum')->count(),
            'habis'   => BahanBaku::where('stok', '<=', 0)->count(),
        ];
    }

    // Notifikasi stok menipis (fitur 11), yang paling kritis di urutan teratas
    private function daftarMenipis(int $batas)
    {
        return BahanBaku::whereColumn('stok', '<=', 'stok_minimum')
            ->orderByRaw('(stok - stok_minimum) asc')
            ->limit($batas)
            ->get();
    }
}