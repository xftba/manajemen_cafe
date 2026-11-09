<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\AktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $supplier = Supplier::query()
            ->when($request->q, fn ($q, $v) => $q->where('nama', 'like', "%{$v}%"))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('supplier.index', compact('supplier'));
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

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama'    => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat'  => ['nullable', 'string', 'max:500'],
        ]);
    }
}