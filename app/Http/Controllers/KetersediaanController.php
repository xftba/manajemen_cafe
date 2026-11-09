<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\LogAktivitas;
use App\Models\Menu;
use App\Services\AktivitasService;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KetersediaanController extends Controller
{
    private const FAKTOR_WASPADA = 1.5;   // bahan masuk panel kalau stok <= minimum x faktor ini

    public function index()
    {
        $menu = Menu::with(['kategori', 'resep.bahanBaku'])
            ->orderBy('nama')
            ->get()
            ->map(fn ($m) => $m->ringkasan())
            ->values();

        $kategori = Kategori::orderBy('id')->get(['id', 'nama']);

        // Stok tertinggi yang pernah tercatat tiap bahan, dipakai sebagai pembagi bar
        $puncak = DB::table('pergerakan_stok')
            ->selectRaw('bahan_baku_id, MAX(stok_sesudah) AS puncak')
            ->groupBy('bahan_baku_id')
            ->pluck('puncak', 'bahan_baku_id');

        $bahanMenipis = $this->bahanWaspada(6)->map(function ($b) use ($puncak) {
            $stok    = (float) $b->stok;
            $minimum = (float) $b->stok_minimum;
            $penuh   = max((float) ($puncak[$b->id] ?? 0), $stok, $minimum, 1);

            return [
                'nama'    => $b->nama,
                'satuan'  => $b->satuan,
                'stok'    => $stok,
                'penuh'   => $penuh,
                'minimum' => $minimum,
                'persen'  => (int) min(100, round($stok / $penuh * 100)),
                'kritis'  => $stok <= $minimum,
            ];
        });

        $lainnya = max(0, $this->bahanWaspadaQuery()->count() - $bahanMenipis->count());

        $konfig = [
            'urlToggle'  => route('kasir.ketersediaan.toggle', ['menu' => '__ID__']),
            'urlRestock' => route('kasir.restock'),
            'urlStatus'  => route('kasir.ketersediaan.status'),
            'csrf'       => csrf_token(),
        ];

        return view('kasir.ketersediaan', compact('menu', 'kategori', 'bahanMenipis', 'lainnya', 'konfig'));
    }

    public function toggle(Request $request, Menu $menu)
    {
        $menu->load(['kategori', 'resep.bahanBaku']);
        $ringkas = $menu->ringkasan();

        // Status yang diminta dikirim dari layar, jadi permintaan berulang hasilnya sama
        $baru = $request->boolean('is_active', ! $ringkas['tersedia']);

        // Tidak bisa diaktifkan kalau resep belum ada atau bahan tidak cukup
        if ($baru && $ringkas['porsi'] === 0) {
            $pesan = "Menu {$menu->nama} tidak bisa diaktifkan: {$ringkas['catatan']}.";

            return $request->expectsJson()
                ? response()->json(['message' => $pesan, 'menu' => $ringkas], 422)
                : back()->with('error', $pesan);
        }

        if ($menu->is_active !== $baru) {
            $menu->update(['is_active' => $baru]);

            AktivitasService::catat(
                'ubah',
                'menu',
                "Mengubah status menu {$menu->nama} menjadi " . ($baru ? 'tersedia' : 'habis')
            );
        }

        $ringkas = $menu->ringkasan();
        $pesan   = "Menu {$menu->nama} sekarang " . ($ringkas['tersedia'] ? 'tersedia' : 'habis') . '.';

        return $request->expectsJson()
            ? response()->json(['message' => $pesan, 'menu' => $ringkas])
            : back()->with('sukses', $pesan);
    }

    // Kasir meminta restock: dicatat di log aktivitas dan dikirim sebagai notifikasi ke staff gudang
    public function restock(Request $request)
    {
        $bahan = $this->bahanWaspada(10);

        if ($bahan->isEmpty()) {
            return response()->json(['message' => 'Tidak ada bahan yang perlu di-restock saat ini.'], 422);
        }

        // Cegah pengiriman berulang dalam waktu singkat
        $terakhir = LogAktivitas::where('user_id', $request->user()->id)
            ->where('aksi', 'minta restock')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest('created_at')
            ->first();

        if ($terakhir) {
            $menit = max(1, abs((int) $terakhir->created_at->diffInMinutes(now())));

            return response()->json([
                'message' => "Permintaan restock sudah dikirim {$menit} menit lalu. Tunggu staff gudang menindaklanjuti.",
            ], 429);
        }

        $daftar = $bahan
            ->map(fn ($b) => $b->nama . ' (' . BahanBaku::angka($b->stok) . ' ' . $b->satuan . ')')
            ->implode(', ');

        AktivitasService::catat('minta restock', 'persediaan menu', 'Kasir meminta restock: ' . $daftar);

        NotifikasiService::kirimKeRole(
            'staff_gudang',
            'restock',
            $request->user()->nama . ' minta restock',
            $daftar,
            route('barang-masuk.index')
        );

        return response()->json(['message' => 'Permintaan restock terkirim ke staff gudang.']);
    }

    // Dipakai polling halaman kasir dan admin
    public function status(Request $request)
    {
        $ids = array_filter(explode(',', (string) $request->query('ids')));

        return Menu::with(['kategori', 'resep.bahanBaku'])->whereIn('id', $ids)->get()
            ->mapWithKeys(fn ($m) => [$m->id => $m->ringkasan()]);
    }

    // ---------------------------------------------------------------

    private function bahanWaspadaQuery()
    {
        return BahanBaku::whereRaw('stok <= stok_minimum * ?', [self::FAKTOR_WASPADA]);
    }

    private function bahanWaspada(int $batas)
    {
        return $this->bahanWaspadaQuery()
            ->orderByRaw('(stok - stok_minimum) asc')
            ->limit($batas)
            ->get();
    }
}