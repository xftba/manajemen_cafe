<?php

namespace App\Http\Controllers;

use App\Exceptions\TransaksiGagal;
use App\Models\Kategori;
use App\Models\Menu;
use App\Models\PengaturanCafe;
use App\Models\Transaksi;
use App\Services\AktivitasService;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\BahanBaku;

class KasirController extends Controller
{
    public function pos()
    {
        $kategori = Kategori::orderBy('id')->get(['id', 'nama']);

        $menu = Menu::with('resep')->orderBy('nama')->get()->map(fn ($m) => [
            'id'              => $m->id,
            'kategori_id'     => $m->kategori_id,
            'nama'            => $m->nama,
            'deskripsi'       => $m->deskripsi,
            'harga'           => (float) $m->harga,
            'tambahan_medium' => (float) $m->tambahan_medium,
            'tambahan_large'  => (float) $m->tambahan_large,
            'pakai_varian'    => (bool) $m->pakai_varian,
            'gambar_url'      => $m->gambar ? asset('storage/' . $m->gambar) : null,
            
            // [ukuran => [{bahan_id, takaran}, ...]]
            'resep'           => $m->resep->groupBy('ukuran')
                ->map(fn ($g) => $g->map(fn ($r) => [
                    'bahan_id' => $r->bahan_baku_id,
                    'takaran'  => (float) $r->takaran,
                    'temperatur' => $r->temperatur,
                ])->values())
                ->toArray(),
        ])->values();

        $cafe = PengaturanCafe::first();

        $konfig = [
            'pajakPersen' => (float) config('cafe.pajak_persen'),
            'nomorOrder'  => Transaksi::whereDate('tanggal_transaksi', today())->count() + 1,
            'qrisUrl'     => $cafe?->qris_gambar ? asset('storage/' . $cafe->qris_gambar) : null,
            'namaCafe'    => $cafe?->nama_cafe ?? config('app.name'),
            'urlSimpan'   => route('kasir.transaksi.store'),
            'csrf'        => csrf_token(),
            'opsi'        => [
                'temperatur' => config('cafe.temperatur'),
                'ukuran'     => config('cafe.ukuran'),
                'gula'       => config('cafe.gula'),
            ],
            'bahan'       => BahanBaku::get(['id', 'stok'])
                ->map(fn ($b) => ['id' => $b->id, 'stok' => (float) $b->stok])
                ->values(),
        ];

        return view('kasir.pos', compact('kategori', 'menu', 'konfig'));
    }

    public function proses(Request $request, TransaksiService $service)
    {
        $data = $request->validate([
            'tipe_pesanan'      => ['required', Rule::in(['takeaway', 'dine_in'])],
            'nama_customer'     => ['nullable', 'string', 'max:100'],
            'metode'            => ['required', Rule::in(['tunai', 'qris'])],
            'jumlah_bayar'      => ['required_if:metode,tunai', 'nullable', 'numeric', 'min:0', 'max:999999999'],
            'no_referensi'      => ['nullable', 'string', 'max:100'],
            'item'              => ['required', 'array', 'min:1', 'max:50'],
            'item.*.menu_id'    => ['required', 'exists:menu,id'],
            'item.*.jumlah'     => ['required', 'integer', 'min:1', 'max:99'],
            'item.*.temperatur' => ['nullable', Rule::in(array_keys(config('cafe.temperatur')))],
            'item.*.ukuran'     => ['nullable', Rule::in(array_keys(config('cafe.ukuran')))],
            'item.*.gula'       => ['nullable', Rule::in(array_keys(config('cafe.gula')))],
            'item.*.catatan'    => ['nullable', 'string', 'max:100'],
        ], [
            'item.required'            => 'Pesanan masih kosong.',
            'jumlah_bayar.required_if' => 'Isi uang yang diterima.',
        ]);

        try {
            $transaksi = $service->buat($data, $request->user());
        } catch (TransaksiGagal $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $pembayaran = $transaksi->pembayaran()->first();

        AktivitasService::catat(
            'tambah',
            'transaksi',
            "Transaksi {$transaksi->no_invoice} total Rp " . number_format($transaksi->total, 0, ',', '.') . " ({$pembayaran->metode})"
        );

        return response()->json([
            'invoice'   => $transaksi->no_invoice,
            'total'     => (float) $transaksi->total,
            'kembalian' => (float) $pembayaran->kembalian,
            'metode'    => $pembayaran->metode,
            'struk_url' => route('kasir.struk', $transaksi),
        ]);
    }
}