<?php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\BarangMasuk;
use App\Models\Menu;
use App\Models\PergerakanStok;
use App\Models\Supplier;
use App\Services\AktivitasService;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BarangMasukController extends Controller
{
    public function index(Request $request)
    {
        $barangMasuk = BarangMasuk::with(['supplier', 'user', 'detail.bahanBaku'])
            ->when($request->supplier, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->dari, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
            ->when($request->sampai, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))
            ->latest('tanggal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $supplier = Supplier::orderBy('nama')->get();

        // Dipakai panel catat barang masuk di kanan
        $bahan = BahanBaku::orderBy('nama')->get(['id', 'nama', 'satuan']);

        // Popup yang dibuka dari tautan (mis. dari Pergerakan Stok): datanya mungkin tidak ada di halaman ini
        $bukaId     = $request->integer('buka') ?: null;
        $barangBuka = $bukaId
            ? BarangMasuk::with(['supplier', 'user', 'detail.bahanBaku'])->find($bukaId)
            : null;

        return view('barang-masuk.index', compact('barangMasuk', 'supplier', 'bahan', 'bukaId', 'barangBuka'));
    }

    // Form ada di panel kanan halaman index
    public function create()
    {
        return redirect()->route('barang-masuk.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id'          => ['required', 'exists:supplier,id'],
            'tanggal'              => ['required', 'date', 'before_or_equal:now'],
            'catatan'              => ['nullable', 'string', 'max:500'],
            'item'                 => ['required', 'array', 'min:1'],
            'item.*.bahan_baku_id' => ['required', 'distinct', 'exists:bahan_baku,id'],
            'item.*.jumlah'        => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'item.*.harga_beli'    => ['required', 'numeric', 'min:0', 'max:999999999'],
        ], [
            'supplier_id.required'          => 'Pilih supplier.',
            'tanggal.before_or_equal'       => 'Tanggal tidak boleh melebihi waktu sekarang.',
            'item.required'                 => 'Tambahkan minimal satu bahan.',
            'item.min'                      => 'Tambahkan minimal satu bahan.',
            'item.*.bahan_baku_id.required' => 'Pilih bahan untuk setiap baris.',
            'item.*.bahan_baku_id.distinct' => 'Satu bahan tidak boleh dipilih dua kali.',
            'item.*.jumlah.required'        => 'Isi jumlah untuk setiap bahan.',
            'item.*.jumlah.gt'              => 'Jumlah harus lebih dari 0.',
            'item.*.harga_beli.required'    => 'Isi harga beli untuk setiap bahan.',
        ]);

        $idBahan = collect($data['item'])->pluck('bahan_baku_id');

        // Porsi tiap menu sebelum stok bertambah (untuk notifikasi "menu tersedia kembali")
        $porsiSebelum = Menu::with(['kategori', 'resep.bahanBaku'])
            ->whereHas('resep', fn ($q) => $q->whereIn('bahan_baku_id', $idBahan))
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $m->ringkasan()['porsi']]);

        $diterima = [];   // nama bahan yang tadinya menipis

        $barangMasuk = DB::transaction(function () use ($data, &$diterima) {
            $bm = BarangMasuk::create([
                'supplier_id' => $data['supplier_id'],
                'user_id'     => auth()->id(),
                'tanggal'     => Carbon::parse($data['tanggal']),
                'catatan'     => $data['catatan'] ?? null,
            ]);

            foreach ($data['item'] as $row) {
                // Kunci baris bahan supaya stok tidak bentrok kalau ada proses lain bersamaan
                $bahan   = BahanBaku::lockForUpdate()->findOrFail($row['bahan_baku_id']);
                $sebelum = (float) $bahan->stok;
                $sesudah = round($sebelum + (float) $row['jumlah'], 2);

                if ($sebelum <= (float) $bahan->stok_minimum * 1.5) {
                    $diterima[] = $bahan->nama;
                }

                $bm->detail()->create([
                    'bahan_baku_id' => $bahan->id,
                    'jumlah'        => $row['jumlah'],
                    'harga_beli'    => $row['harga_beli'],
                ]);

                PergerakanStok::create([
                    'bahan_baku_id'   => $bahan->id,
                    'user_id'         => auth()->id(),
                    'barang_masuk_id' => $bm->id,
                    'tipe_pergerakan' => 'masuk',
                    'jumlah'          => $row['jumlah'],
                    'stok_sebelum'    => $sebelum,
                    'stok_sesudah'    => $sesudah,
                    'catatan'         => 'Barang masuk ' . $bm->kode,
                ]);

                $bahan->update(['stok' => $sesudah]);
            }

            return $bm;
        });

        $barangMasuk->load('supplier');
        AktivitasService::catat(
            'tambah',
            'barang masuk',
            "Mencatat barang masuk {$barangMasuk->kode} dari {$barangMasuk->supplier->nama} (" . count($data['item']) . ' bahan)'
        );

        // Kasir: restock yang diminta sudah diterima
        if ($diterima) {
            NotifikasiService::kirimKeRole(
                'kasir',
                'menu_tersedia',
                'Restock diterima',
                implode(', ', $diterima) . ' sudah ditambah.',
                route('kasir.ketersediaan')
            );
        }

        // Kasir: menu yang tadinya habis (porsi 0) kini bisa dijual lagi
        $kembali = Menu::with(['kategori', 'resep.bahanBaku'])
            ->whereIn('id', $porsiSebelum->keys())
            ->get()
            ->filter(fn ($m) => ($porsiSebelum[$m->id] ?? 0) === 0 && $m->ringkasan()['porsi'] > 0)
            ->pluck('nama');

        if ($kembali->isNotEmpty()) {
            NotifikasiService::kirimKeRole(
                'kasir',
                'menu_tersedia',
                'Menu tersedia kembali',
                $kembali->implode(', '),
                route('kasir.ketersediaan')
            );
        }

        return redirect()->route('barang-masuk.index')
            ->with('sukses', "Barang masuk {$barangMasuk->kode} berhasil dicatat dan stok sudah bertambah.");
    }

    // Detail ada di popup halaman index
    public function show(BarangMasuk $barangMasuk)
    {
        return redirect()->route('barang-masuk.index', ['buka' => $barangMasuk->id]);
    }
}