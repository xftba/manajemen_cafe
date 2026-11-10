<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AktivitasService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    // Sesuaikan dengan tabel detail barang masuk Anda
    private const DETAIL        = 'detail_barang_masuk';   // sebelumnya 'barang_masuk_detail'
    private const KOLOM_BAHAN   = 'bahan_baku_id';
    private const KOLOM_JUMLAH  = 'jumlah';
    private const KOLOM_HARGA   = 'harga_beli';

    public function index(Request $request)
    {
        [$bulan, $awal, $akhir] = $this->periode($request);

        $supplier = Supplier::query()
            ->when($request->q, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $ringkasan = DB::table('barang_masuk as bm')
            ->join(self::DETAIL . ' as d', 'd.barang_masuk_id', '=', 'bm.id')
            ->whereBetween('bm.tanggal', [$awal, $akhir])
            ->whereIn('bm.supplier_id', $supplier->pluck('id'))
            ->groupBy('bm.supplier_id')
            ->selectRaw('bm.supplier_id,
                         COUNT(DISTINCT bm.id) as kali_kirim,
                         SUM(d.' . self::KOLOM_JUMLAH . ' * d.' . self::KOLOM_HARGA . ') as total_nilai')
            ->get()
            ->keyBy('supplier_id');

        return view('supplier.index', compact('supplier', 'ringkasan', 'bulan'));
    }

    public function show(Request $request, Supplier $supplier)
    {
        [$bulan, $awal, $akhir] = $this->periode($request);

        // Pengiriman pada bulan terpilih, beserta nilainya
        $riwayat = DB::table('barang_masuk as bm')
            ->join(self::DETAIL . ' as d', 'd.barang_masuk_id', '=', 'bm.id')
            ->where('bm.supplier_id', $supplier->id)
            ->whereBetween('bm.tanggal', [$awal, $akhir])
            ->groupBy('bm.id', 'bm.tanggal', 'bm.catatan')
            ->orderByDesc('bm.tanggal')
            ->selectRaw('bm.id, bm.tanggal, bm.catatan,
                         COUNT(*) as jenis_bahan,
                         SUM(d.' . self::KOLOM_JUMLAH . ' * d.' . self::KOLOM_HARGA . ') as nilai')
            ->get();

        // Bahan yang dipasok pada bulan terpilih
        $bahan = DB::table('barang_masuk as bm')
            ->join(self::DETAIL . ' as d', 'd.barang_masuk_id', '=', 'bm.id')
            ->join('bahan_baku as b', 'b.id', '=', 'd.' . self::KOLOM_BAHAN)
            ->where('bm.supplier_id', $supplier->id)
            ->whereBetween('bm.tanggal', [$awal, $akhir])
            ->groupBy('b.id', 'b.nama', 'b.satuan')
            ->orderBy('b.nama')
            ->selectRaw('b.nama, b.satuan,
                         SUM(d.' . self::KOLOM_JUMLAH . ') as total_jumlah,
                         SUM(d.' . self::KOLOM_JUMLAH . ' * d.' . self::KOLOM_HARGA . ') as total_nilai')
            ->get();

        $ringkasan = [
            'kali_kirim'  => $riwayat->count(),
            'total_nilai' => (float) $riwayat->sum('nilai'),
            'jenis_bahan' => $bahan->count(),
        ];

        return view('supplier.show', compact('supplier', 'riwayat', 'bahan', 'ringkasan', 'bulan'));
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($this->validasi($request));
        AktivitasService::catat('tambah', 'supplier', "Menambah supplier {$supplier->nama}");

        return redirect()->route('supplier.index')->with('sukses', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validasi($request));
        AktivitasService::catat('ubah', 'supplier', "Mengubah supplier {$supplier->nama}");

        return redirect()->route('supplier.index')->with('sukses', 'Supplier berhasil diubah.');
    }

    public function destroy(Supplier $supplier)
    {
        if (DB::table('barang_masuk')->where('supplier_id', $supplier->id)->exists()) {
            return back()->with('error', 'Supplier tidak bisa dihapus karena sudah punya riwayat barang masuk.');
        }

        $nama = $supplier->nama;
        $supplier->delete();
        AktivitasService::catat('hapus', 'supplier', "Menghapus supplier {$nama}");

        return back()->with('sukses', 'Supplier berhasil dihapus.');
    }

    // Bulan dari ?bulan=YYYY-MM, bawaan bulan ini
    private function periode(Request $request): array
    {
        try {
            $awal = Carbon::createFromFormat('Y-m', (string) $request->query('bulan', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            $awal = now()->startOfMonth();
        }

        return [$awal->format('Y-m'), $awal, $awal->copy()->endOfMonth()];
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat'  => ['nullable', 'string', 'max:500'],
        ]);
    }
}