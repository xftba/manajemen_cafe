<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Services\AktivitasService;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Kategori::withCount('menu')->orderBy('nama')->paginate(10);

        return view('kategori.index', compact('kategori'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori,nama'],
        ]);

        $kategori = Kategori::create($data);
        AktivitasService::catat('tambah', 'kategori', "Menambah kategori {$kategori->nama}");

        return back()->with('sukses', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori)
    {
        return view('kategori.edit', compact('kategori'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:kategori,nama,' . $kategori->id],
        ]);

        $lama = $kategori->nama;
        $kategori->update($data);
        AktivitasService::catat('ubah', 'kategori', "Mengubah kategori {$lama} menjadi {$kategori->nama}");

    }

    public function destroy(Kategori $kategori)
    {
        // Menu punya foreign key ke kategori, jadi kategori yang masih dipakai tidak boleh dihapus
        if ($kategori->menu()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih dipakai oleh menu.');
        }

        $nama = $kategori->nama;
        $kategori->delete();
        AktivitasService::catat('hapus', 'kategori', "Menghapus kategori {$nama}");

        return back()->with('sukses', 'Kategori berhasil dihapus.');
    }
}